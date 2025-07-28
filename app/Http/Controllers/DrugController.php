<?php

namespace App\Http\Controllers;

use App\Http\Resources\DrugResource;
use App\Http\Resources\DrugCategoryResource;
use App\Models\Drug;
use App\Models\DrugCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DrugController extends Controller
{
    /**
     * Display a listing of drugs for public access
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Drug::with(['categories', 'diseases'])
            ->available();

        // Apply filters
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [$request->min_price, $request->max_price]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = ['name', 'price', 'created_at', 'stock'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $drugs = $query->paginate($request->get('per_page', 15));

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
