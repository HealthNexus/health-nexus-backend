<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('drug_id')->constrained();

            // Product details at time of order
            $table->string('drug_name'); // Store name in case drug is deleted
            $table->string('drug_slug');
            $table->text('drug_description')->nullable();

            // Pricing and quantity
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
            $table->index(['drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
