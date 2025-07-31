<?php

namespace App\Http\Controllers;

use App\Http\Resources\DrugResource;
use App\Http\Resources\DrugCategoryResource;
use App\Models\Drug;
use App\Models\DrugCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\StoreDrugRequest;
use Illuminate\Support\Str;

class DrugController extends Controller
{

    /**
     * Store a newly created drug in storage.
     */
    public function store(StoreDrugRequest $request)
    {
        $data = $request->validated();

        // Handle image upload if present using saveImage infrastructure
        if ($request->hasFile('image')) {
            $imagePath = $this->saveImage($request->image, 'drugs');
            logger()->info('Image uploaded successfully', ['path' => $imagePath]);
            $data['image'] = $imagePath;
        }

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $categoryIds = $data['category_ids'] ?? [];
        $diseaseIds = $data['disease_ids'] ?? [];
        // Remove category_ids from data to avoid mass assignment issues
        unset($data['disease_ids']);
        unset($data['category_ids']);
        $drug = Drug::create($data);

        // Attach categories
        if (!empty($categoryIds)) {
            $drug->categories()->sync($categoryIds);
        }

        // Attach diseases if provided
        if (!empty($diseaseIds)) {
            $drug->diseases()->sync($diseaseIds);
        }

        return new DrugResource($drug->load(['categories', 'diseases']));
    }
    /**
     * Display a listing of drugs for public access
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $drugs = Drug::with(['categories', 'diseases'])
            ->orderByDesc('created_at')->get();

        return DrugResource::collection($drugs);
    }

    /**
     * Display the specified drug
     */
    public function show(string $slug): DrugResource|JsonResponse
    {
        $drug = Drug::with(['categories', 'diseases'])
            ->where('slug', $slug)
            ->available()
            ->first();

        if (!$drug) {
            return response()->json([
                'status' => 'error',
                'message' => 'Drug not found or not available'
            ], 404);
        }

        return new DrugResource($drug);
    }

    /**
     * Search drugs
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->get('q');

        $drugs = Drug::with(['categories', 'diseases'])
            ->available()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('categories', function ($categoryQuery) use ($query) {
                        $categoryQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->orderByRaw("
                CASE
                    WHEN name LIKE '{$query}%' THEN 1
                    WHEN name LIKE '%{$query}%' THEN 2
                    WHEN description LIKE '%{$query}%' THEN 3
                    ELSE 4
                END
            ")
            ->paginate($request->get('per_page', 15));

        return DrugResource::collection($drugs);
    }

    /**
     * Get drugs by category
     */
    public function byCategory(string $categorySlug): AnonymousResourceCollection|JsonResponse
    {
        $category = DrugCategory::where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found'
            ], 404);
        }

        $drugs = Drug::with(['categories', 'diseases'])
            ->available()
            ->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            })
            ->paginate(15);

        return DrugResource::collection($drugs);
    }

    /**
     * Get all drug categories
     */
    public function categories(): AnonymousResourceCollection
    {
        $categories = DrugCategory::withCount(['drugs' => function ($query) {
            $query->where('status', 'active')->where('stock', '>', 0);
        }])
            ->orderBy('name')
            ->get()
            ->filter(function ($category) {
                return $category->drugs_count > 0;
            });

        return DrugCategoryResource::collection($categories);
    }

    /**
     * Admin only - Get all drugs including inactive
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        if (!auth()->user() || !in_array(auth()->user()->role->slug, ['admin', 'doctor'])) {
            abort(403, 'Unauthorized');
        }

        $query = Drug::with(['categories', 'diseases']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $drugs = $query->paginate($request->get('per_page', 15));

        return DrugResource::collection($drugs);
    }
}
