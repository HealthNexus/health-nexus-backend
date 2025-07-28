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
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');

            // Add indexes for performance
            $table->index(['status', 'stock']);
            $table->index(['price']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
