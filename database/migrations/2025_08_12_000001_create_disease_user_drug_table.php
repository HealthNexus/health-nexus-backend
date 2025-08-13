<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_user_drug', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_user_id')->constrained('disease_user')->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->cascadeOnDelete();
            $table->string('dosage')->nullable();
            $table->text('directions')->nullable();
            $table->integer('quantity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['disease_user_id']);
            $table->index(['drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_user_drug');
    }
};
