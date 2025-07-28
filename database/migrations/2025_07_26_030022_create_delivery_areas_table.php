<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // e.g., 'ayeduase', 'bomso'
            $table->string('name'); // e.g., 'Ayeduase', 'Bomso'
            $table->text('description')->nullable(); // Area description
            $table->decimal('base_fee', 8, 2); // Delivery fee for this area
            $table->boolean('is_active')->default(true); // Can be temporarily disabled
            $table->integer('sort_order')->default(0); // For ordering in frontend
            $table->json('landmarks')->nullable(); // Notable landmarks in the area
            $table->timestamps();

            // Indexes
            $table->index(['is_active']);
            $table->index(['code']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_areas');
    }
};
