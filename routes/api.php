<?php

use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SymptomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Password Reset Routes
Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);
Route::post('/password/verify-token', [PasswordResetController::class, 'verifyToken']);

Route::get('/posts/{post}', [PostController::class, 'show']);

//retrieve all posts
Route::get('/posts', [PostController::class, 'index']);

//retrieve all hospitals
Route::get('/hospitals', [HospitalController::class, 'index']);

//diseases routes
Route::get('/diseases', [DiseaseController::class, 'index']);

//retrievee all disease categories
Route::get('/diseases/categories', [CategoryController::class, 'index']);

// Public drug routes (e-pharmacy)
Route::get('/drugs', [DrugController::class, 'index']);
Route::get('/drugs/search', [DrugController::class, 'search']);
Route::get('/drugs/categories', [DrugController::class, 'categories']);
Route::get('/drugs/category/{categorySlug}', [DrugController::class, 'byCategory']);
Route::get('/drugs/{slug}', [DrugController::class, 'show']);

// Public delivery information routes
Route::prefix('delivery')->group(function () {
    Route::get('/areas', [DeliveryController::class, 'getDeliveryAreas']);
    Route::post('/calculate-fee', [DeliveryController::class, 'calculateDeliveryFee']);
});

// Payment webhook and callback (no authentication required)
Route::post('/payments/webhook/paystack', [PaymentController::class, 'webhook']);
Route::get('/payments/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
Route::post('/payments/redirect', [PaymentController::class, 'initializeAndRedirect'])->name('payment.redirect');

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    // create a new route for logout
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response(['user' => $request->user()], 200);
    });

    Route::get('/users', [AuthController::class, 'index']);

    // E-commerce: Orders and Payments (items submitted from frontend cart)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']); // User's orders
        Route::post('/', [OrderController::class, 'store']); // Create order with items array
        Route::get('/statistics', [OrderController::class, 'statistics']); // Order statistics
        Route::get('/{order}', [OrderController::class, 'show']); // Order details
        Route::post('/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']); // Confirm delivery
    });

    Route::prefix('payments')->group(function () {
        Route::post('/initialize', [PaymentController::class, 'initialize']);
        Route::post('/verify', [PaymentController::class, 'verify']);
        Route::post('/calculate-fees', [PaymentController::class, 'calculateFees']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
    });


    //posts routes
    Route::post('/posts/store', [PostController::class, 'store']);
    Route::put('/posts/{post}/update', [PostController::class, 'update']);
    Route::delete('/posts/{post}/destroy', [PostController::class, 'destroy']);

    //comments routes
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}/destroy', [CommentController::class, 'destroy']);

    //reply routes
    Route::post('/comments/{comment}/replies', [ReplyController::class, 'store']);
    Route::delete('/replies/{reply}/destroy', [ReplyController::class, 'destroy']);

    //records
    Route::get('/records', [RecordController::class, 'index']);
    Route::get('/month-disease-data', [RecordController::class, 'monthDiseaseData']);
    Route::get('/day-disease-data', [RecordController::class, 'dayDiseaseData']);
    Route::get('/year-disease-data', [RecordController::class, 'yearDiseaseData']);
    Route::post('/records/{patient}/store', [RecordController::class, 'store']);

    // symptoms
    Route::get('/symptoms', [SymptomController::class, 'index']);

    //admin drugs
    Route::get('/admin/drugs', [DrugController::class, 'adminIndex']);

    // Admin inventory management routes
    Route::middleware(['admin'])->prefix('admin/inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/statistics', [InventoryController::class, 'statistics']);
        Route::get('/low-stock-alerts', [InventoryController::class, 'lowStockAlerts']);
        Route::get('/report', [InventoryController::class, 'report']);
        Route::put('/{drug}/stock', [InventoryController::class, 'updateStock']);
        Route::post('/bulk-update-stock', [InventoryController::class, 'bulkUpdateStock']);
    });

    // Admin order management routes
    Route::middleware(['admin'])->prefix('admin/orders')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index']);
        Route::get('/analytics', [OrderManagementController::class, 'analytics']);
        Route::get('/requires-attention', [OrderManagementController::class, 'requiresAttention']);
        Route::get('/{order}', [OrderManagementController::class, 'show']);
        Route::put('/{order}/status', [OrderManagementController::class, 'updateStatus']);
        Route::post('/{order}/mark-delivering', [OrderManagementController::class, 'markAsDelivering']);
    });

    // Admin delivery management routes
    Route::middleware(['admin'])->prefix('admin/delivery')->group(function () {
        Route::get('/statistics', [DeliveryController::class, 'getDeliveryStatistics']);
        Route::get('/routes', [DeliveryController::class, 'getDeliveryRoutes']);
        Route::get('/area/{area}', [DeliveryController::class, 'getOrdersByArea']);
        Route::post('/areas', [DeliveryController::class, 'createDeliveryArea']);
        Route::put('/areas/{areaCode}', [DeliveryController::class, 'updateDeliveryArea']);
        Route::patch('/areas/{areaCode}/toggle', [DeliveryController::class, 'toggleDeliveryAreaStatus']);
    });

    //general analytics
    Route::get('/records/analytics/general/{year}/months', [RecordController::class, 'monthsVsDiseasesPerYearSelected']);
    Route::get('/records/analytics/general/{start}/{end}/years', [RecordController::class, 'yearsVsDiseaseData']);
});
