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
        Schema::table('carts', function (Blueprint $table) {
            // Drop session_id column and index
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
            
            // Make user_id required (not nullable)
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Add back session_id column
            $table->string('session_id')->nullable();
            $table->index(['session_id']);
            
            // Make user_id nullable again
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
