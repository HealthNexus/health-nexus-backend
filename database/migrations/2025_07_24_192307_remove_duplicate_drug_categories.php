<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\DrugCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all duplicate category names
        $duplicateNames = DB::table('drug_categories')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            // Get all categories with this name, ordered by ID
            $categories = DrugCategory::where('name', $name)->orderBy('id')->get();

            if ($categories->count() > 1) {
                // Keep the first category (lowest ID)
                $keepCategory = $categories->first();
                $duplicates = $categories->slice(1);

                foreach ($duplicates as $duplicate) {
                    // Transfer all drug relationships to the category we're keeping
                    $drugIds = $duplicate->drugs()->pluck('drugs.id')->toArray();
                    if (!empty($drugIds)) {
                        // Use syncWithoutDetaching to avoid duplicate relationships
                        $keepCategory->drugs()->syncWithoutDetaching($drugIds);
                    }

                    // Delete the duplicate category
                    $duplicate->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as we've deleted duplicate data
        // We would need to restore from backup if reversal is needed
    }
};
