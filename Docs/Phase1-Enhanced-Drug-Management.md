# Phase 1: Enhanced Drug Management

## Overview

This phase focuses on extending the existing drug model to support e-commerce functionality. We'll enhance the database schema, update models, and create customer-facing API endpoints.

## Current State

-   ✅ Basic Drug model with `id`, `name`, `slug`, `created_at`, `updated_at`
-   ✅ DrugCategory model and many-to-many relationships
-   ✅ Drug-Disease relationship mapping
-   ✅ Basic drug listing (admin/doctor only)

## Objectives

-   Add e-commerce fields to drugs table
-   Create public drug browsing endpoints
-   Implement search and filtering capabilities
-   Add inventory management
-   Create drug resource transformations

## Tasks

### Task 1.1: Database Schema Enhancement

#### Migration: Enhance Drugs Table

**File**: `database/migrations/2025_07_24_000001_enhance_drugs_table_for_ecommerce.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('slug');
            $table->integer('stock')->default(0)->after('price');
            $table->date('expiry_date')->nullable()->after('price');
            $table->string('image')->nullable()->after('expiry_date');
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active')->after('image');

            // Add indexes for performance
            $table->index(['status', 'stock']);;
            $table->index(['price']);
        });
    }

    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropColumn([
                'price', 'stock', 'image', 'status', 'expiry_date'
            ]);
            $table->dropIndex(['status', 'stock']);
            $table->dropIndex(['price']);
        });
    }
};
```

### Task 1.2: Model Enhancement

#### Update Drug Model

**File**: `app/Models/Drug.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Drug extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class)->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DrugCategory::class)->withTimestamps();
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->inStock();
    }



    // Methods
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stock > 0;
    }

    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity): bool
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);

            // Update status if out of stock
            if ($this->stock <= 0) {
                $this->update(['status' => 'out_of_stock']);
            }

            return true;
        }

        return false;
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);

        // Reactivate if was out of stock
        if ($this->status === 'out_of_stock' && $this->stock > 0) {
            $this->update(['status' => 'active']);
        }
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'GHC' . number_format($this->price, 2);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
```

### Task 1.3: API Resource

#### Create Drug Resource

**File**: `app/Http/Resources/DrugResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'stock' => $this->stock,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'image' => $this->image,
            'status' => $this->status,
            'is_available' => $this->isAvailable(),
            'is_expired' => $this->is_expired,
            'categories' => DrugCategoryResource::collection($this->whenLoaded('categories')),
            'diseases' => DiseaseResource::collection($this->whenLoaded('diseases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### Create Drug Category Resource

**File**: `app/Http/Resources/DrugCategoryResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### Create Disease Resource (if not exists)

**File**: `app/Http/Resources/DiseaseResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Task 1.4: Enhanced Controller

#### Update Drug Controller

**File**: `app/Http/Controllers/DrugController.php`

```php
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
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('prescription_required')) {
            $query->prescriptionRequired($request->boolean('prescription_required'));
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
                  ->orWhere('manufacturer', 'like', "%{$query}%")
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
            $query->available();
        }])
        ->having('drugs_count', '>', 0)
        ->orderBy('name')
        ->get();

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
```

### Task 1.5: Update Routes

#### Add New Routes to API

**File**: `routes/api.php` (add these routes)

```php
// Public drug routes (replace existing /drugs route)
Route::get('/drugs', [DrugController::class, 'index']);
Route::get('/drugs/search', [DrugController::class, 'search']);
Route::get('/drugs/categories', [DrugController::class, 'categories']);
Route::get('/drugs/category/{categorySlug}', [DrugController::class, 'byCategory']);
Route::get('/drugs/{slug}', [DrugController::class, 'show']);

// Admin drug routes (replace existing admin drug route)
Route::middleware(['auth:sanctum'])->group(function () {
    // Admin drug management
    Route::get('/admin/drugs', [DrugController::class, 'adminIndex']);
    // Additional admin routes will be added in Phase 6
});
```

### Task 1.6: Factory Updates

#### Update Drug Factory

**File**: `database/factories/DrugFactory.php`

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DrugFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Paracetamol', 'Ibuprofen', 'Amoxicillin', 'Omeprazole', 'Aspirin',
            'Metformin', 'Atorvastatin', 'Amlodipine', 'Cetirizine', 'Loratadine',
            'Diclofenac', 'Azithromycin', 'Ciprofloxacin', 'Prednisone', 'Insulin'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name . '-' . $this->faker->randomNumber(3)),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'stock' => $this->faker->numberBetween(0, 1000),
            'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+3 years'),
            'image' => $this->faker->imageUrl(400, 400, 'medicine'),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']), // 75% active
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'stock' => $this->faker->numberBetween(10, 1000),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_of_stock',
            'stock' => 0,
        ]);
    }
}
```

### Task 1.7: Seeder Updates

#### Update Database Seeder

**File**: `database/seeders/DatabaseSeeder.php` (update the drugs section)

```php
// Replace the existing drugs seeding section with:
// Create drugs with proper e-commerce data
foreach ($drugs as $drugName) {
    Drug::factory()->active()->create(['name' => $drugName]);
}

// Create some out-of-stock drugs
Drug::factory(5)->outOfStock()->create();
```

## Testing

### API Endpoints to Test

1. **GET /api/drugs** - List all available drugs

    - Test pagination
    - Test filtering by category
    - Test search functionality
    - Test sorting options

2. **GET /api/drugs/{slug}** - Get drug details

    - Test with valid slug
    - Test with invalid slug
    - Test with inactive drug

3. **GET /api/drugs/search?q={query}** - Search drugs

    - Test with various search terms
    - Test minimum query length validation
    - Test search ranking

4. **GET /api/drugs/categories** - List categories

    - Test only categories with available drugs are returned

5. **GET /api/drugs/category/{categorySlug}** - Drugs by category
    - Test with valid category
    - Test with invalid category

### Test Cases

```php
// Feature test example
public function test_can_list_available_drugs()
{
    // Arrange
    Drug::factory(10)->active()->create();
    Drug::factory(5)->outOfStock()->create();

    // Act
    $response = $this->getJson('/api/drugs');

    // Assert
    $response->assertStatus(200);
    $response->assertJsonCount(10, 'data');
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id', 'name', 'slug', 'price', 'is_available'
            ]
        ],
        'meta' => ['current_page', 'last_page', 'total']
    ]);
}
```

## Success Criteria

-   [ ] All new fields added to drugs table
-   [ ] Drug model enhanced with e-commerce methods
-   [ ] Public API endpoints return available drugs only
-   [ ] Search functionality works with ranking
-   [ ] Filtering and sorting work correctly
-   [ ] API responses use proper resource transformations
-   [ ] Database is seeded with realistic e-commerce data
-   [ ] All endpoints have proper error handling
-   [ ] Performance is acceptable (< 200ms for drug listing)

## Next Phase

Once Phase 1 is complete, proceed to **Phase 2: Shopping Cart System**.
