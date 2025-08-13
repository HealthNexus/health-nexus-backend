<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_user_symptom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_user_id')->constrained('disease_user')->cascadeOnDelete();
            $table->foreignId('symptom_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['disease_user_id']);
            $table->index(['symptom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_user_symptom');
    }
};
