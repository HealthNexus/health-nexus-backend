<?php
/**
 * Script to remove shipping_address column from orders table
 * Run this with: php remove_shipping_column.php
 */

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (Schema::hasColumn('orders', 'shipping_address')) {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_address');
        });
        echo "✅ shipping_address column removed successfully!\n";
    } else {
        echo "ℹ️  shipping_address column does not exist.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
