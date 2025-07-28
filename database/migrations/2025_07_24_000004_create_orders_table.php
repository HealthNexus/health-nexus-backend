<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Order totals
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->integer('total_items');

            // Order status
            $table->enum('status', OrderStatus::values())->default(OrderStatus::PLACED->value);
            $table->timestamp('status_updated_at')->nullable();
            $table->foreignId('status_updated_by')->nullable()->constrained('users');

            // Contact and delivery information
            $table->string('phone_number');
            $table->text('delivery_notes')->nullable();

            // Delivery information (for in-city delivery)
            $table->string('delivery_area')->nullable(); // Area/district within city
            $table->text('delivery_address'); // Full delivery address
            $table->string('landmark')->nullable(); // Nearby landmark for easy location
            $table->decimal('delivery_fee', 8, 2)->default(0); // Delivery charge

            // Payment information
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            // Timestamps
            $table->timestamp('placed_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['order_number']);
            $table->index(['placed_at']);
            $table->index(['delivery_area']); // For area-based filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
