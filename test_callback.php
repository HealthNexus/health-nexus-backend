<?php

// Simple test script to verify callback functionality
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;

// Create a test request
$request = new Request([
    'reference' => 'b7jh7YLjx57Nav4md6WC6KLlq'
]);

echo "Testing callback with reference: " . $request->query('reference') . "\n";

// Test if reference is properly extracted
$reference = $request->query('reference') ?? $request->input('reference');

if (!$reference) {
    echo "ERROR: No reference found\n";
} else {
    echo "SUCCESS: Reference found: " . $reference . "\n";
}

echo "Test completed.\n";
