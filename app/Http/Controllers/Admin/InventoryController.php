<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Get inventory overview
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Drug::with(['categories']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('low_stock')) {
            $lowStockThreshold = $request->get('threshold', 10);
            $query->where('stock', '<=', $lowStockThreshold);
        }

        if ($request->filled('out_of_stock')) {
            $query->where('stock', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'stock');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = ['name', 'stock', 'price', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $drugs = $query->paginate($request->get('per_page', 20));

        return DrugResource::collection($drugs);
    }

    /**
     * Get inventory statistics
     */
    public function statistics(): JsonResponse
    {
        $totalDrugs = Drug::count();
        $activeDrugs = Drug::where('status', 'active')->count();
        $inStockDrugs = Drug::where('stock', '>', 0)->count();
        $outOfStockDrugs = Drug::where('stock', 0)->count();
        $lowStockDrugs = Drug::where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->count();

        $totalStockValue = Drug::where('status', 'active')
            ->selectRaw('SUM(stock * price) as total')
            ->value('total') ?? 0;

        $topSellingDrugs = OrderItem::select('drug_id', 'drug_name')
            ->selectRaw('SUM(quantity) as total_sold')
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->groupBy('drug_id', 'drug_name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_drugs' => $totalDrugs,
                'active_drugs' => $activeDrugs,
                'in_stock_drugs' => $inStockDrugs,
                'out_of_stock_drugs' => $outOfStockDrugs,
                'low_stock_drugs' => $lowStockDrugs,
                'total_stock_value' => $totalStockValue,
                'formatted_stock_value' => '₵' . number_format($totalStockValue, 2),
                'top_selling_drugs' => $topSellingDrugs,
            ]
        ], 200);
    }

    /**
     * Update drug stock
     */
    public function updateStock(Request $request, Drug $drug): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQuantity = $drug->stock;
        $newQuantity = 0;

        switch ($request->operation) {
            case 'set':
                $newQuantity = $request->quantity;
                break;
            case 'add':
                $newQuantity = $oldQuantity + $request->quantity;
                break;
            case 'subtract':
                $newQuantity = max(0, $oldQuantity - $request->quantity);
                break;
        }

        $drug->update(['stock' => $newQuantity]);

        // Update status based on stock
        if ($newQuantity === 0) {
            $drug->update(['status' => 'out_of_stock']);
        } elseif ($drug->status === 'out_of_stock' && $newQuantity > 0) {
            $drug->update(['status' => 'active']);
        }

        // Log the stock change
        Log::info('Stock updated', [
            'drug_id' => $drug->id,
            'drug_name' => $drug->name,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'operation' => $request->operation,
            'quantity' => $request->quantity,
            'reason' => $request->reason,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock updated successfully',
            'data' => [
                'drug' => new DrugResource($drug->fresh()),
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ]
        ], 200);
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 10);

        $lowStockDrugs = Drug::where('status', 'active')
            ->where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->with('categories')
            ->orderBy('stock', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'threshold' => $threshold,
                'count' => $lowStockDrugs->count(),
                'drugs' => DrugResource::collection($lowStockDrugs),
            ]
        ], 200);
    }

    /**
     * Generate inventory report
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'in:summary,detailed',
            'include_inactive' => 'boolean',
        ]);

        $query = Drug::with(['categories']);

        if (!$request->boolean('include_inactive')) {
            $query->where('status', 'active');
        }

        $drugs = $query->get();

        $report = [
            'generated_at' => now(),
            'total_items' => $drugs->count(),
            'summary' => [
                'total_stock_value' => $drugs->sum(function ($drug) {
                    return $drug->stock * $drug->price;
                }),
                'total_quantity' => $drugs->sum('stock'),
                'categories' => $drugs->groupBy('categories.0.name')->map(function ($items, $category) {
                    return [
                        'count' => $items->count(),
                        'total_quantity' => $items->sum('stock'),
                        'total_value' => $items->sum(function ($drug) {
                            return $drug->stock * $drug->price;
                        }),
                    ];
                }),
            ],
        ];

        if ($request->format === 'detailed') {
            $report['items'] = DrugResource::collection($drugs);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report
        ], 200);
    }

    /**
     * Bulk stock update
     */
    public function bulkUpdateStock(Request $request): JsonResponse
    {
        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.drug_id' => 'required|exists:drugs,id',
            'updates.*.quantity' => 'required|integer|min:0',
            'updates.*.operation' => 'required|in:set,add,subtract',
        ]);

        $results = [];
        $errors = [];

        DB::transaction(function () use ($request, &$results, &$errors) {
            foreach ($request->updates as $update) {
                try {
                    $drug = Drug::findOrFail($update['drug_id']);
                    $oldQuantity = $drug->stock;

                    $newQuantity = match ($update['operation']) {
                        'set' => $update['quantity'],
                        'add' => $oldQuantity + $update['quantity'],
                        'subtract' => max(0, $oldQuantity - $update['quantity']),
                    };

                    $drug->update(['stock' => $newQuantity]);

                    // Update status based on stock
                    if ($newQuantity === 0) {
                        $drug->update(['status' => 'out_of_stock']);
                    } elseif ($drug->status === 'out_of_stock' && $newQuantity > 0) {
                        $drug->update(['status' => 'active']);
                    }

                    $results[] = [
                        'drug_id' => $drug->id,
                        'drug_name' => $drug->name,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'status' => 'success',
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'drug_id' => $update['drug_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'status' => empty($errors) ? 'success' : 'partial_success',
            'message' => 'Bulk stock update completed',
            'data' => [
                'successful_updates' => count($results),
                'failed_updates' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]
        ], 200);
    }
}
