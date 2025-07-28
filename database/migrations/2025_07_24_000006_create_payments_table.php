<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Payment details
            $table->string('payment_reference')->unique();
            $table->string('paystack_reference')->nullable();
            $table->string('access_code')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHC');

            // Payment status and metadata
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // card, bank_transfer, ussd, etc.
            $table->string('channel')->nullable(); // paystack channel used
            $table->string('gateway_response')->nullable();
            $table->json('gateway_metadata')->nullable();

            // Transaction details
            $table->decimal('fees', 10, 2)->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('last4')->nullable();
            $table->string('exp_month')->nullable();
            $table->string('exp_year')->nullable();
            $table->string('card_type')->nullable();
            $table->string('bank')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['payment_reference']);
            $table->index(['paystack_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
