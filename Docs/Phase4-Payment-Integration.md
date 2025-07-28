# Phase 4: Payment Integration

## Overview

This phase implements payment processing capabilities with PayStack integration. The system handles payment initiation, verification, webhook processing, and payment status management.

## Objectives

-   Integrate PayStack payment gateway
-   Create payment tracking and logging system
-   Implement payment verification and webhook handling
-   Build secure payment processing API
-   Handle payment status updates and order completion
-   Implement refund processing capabilities

## Tasks

### Task 4.1: Install PayStack Package

#### Install Laravel PayStack Package

Already Installed

#### Publish PayStack Configuration

Configuration already published

### Task 4.2: Database Schema

#### Create Payments Migration

**File**: `database/migrations/2025_07_24_000006_create_payments_table.php`

```php
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
            $table->string('currency', 3)->default('NGN');

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
```

#### Create Payment Logs Migration

**File**: `database/migrations/2025_07_24_000007_create_payment_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // initialize, verify, webhook, etc.
            $table->string('status');
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['payment_id']);
            $table->index(['event_type']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
```

### Task 4.3: Models

#### Create Payment Model

**File**: `app/Models/Payment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_reference',
        'paystack_reference',
        'access_code',
        'amount',
        'currency',
        'status',
        'payment_method',
        'channel',
        'gateway_response',
        'gateway_metadata',
        'fees',
        'authorization_code',
        'last4',
        'exp_month',
        'exp_year',
        'card_type',
        'bank',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'gateway_metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    // Methods
    public function generatePaymentReference(): string
    {
        do {
            $reference = 'HN-PAY-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    public function markAsPaid(array $gatewayData = []): bool
    {
        $updateData = [
            'status' => 'success',
            'paid_at' => now(),
            'gateway_response' => $gatewayData['gateway_response'] ?? null,
            'gateway_metadata' => $gatewayData,
            'fees' => $gatewayData['fees'] ?? null,
            'authorization_code' => $gatewayData['authorization']['authorization_code'] ?? null,
            'last4' => $gatewayData['authorization']['last4'] ?? null,
            'exp_month' => $gatewayData['authorization']['exp_month'] ?? null,
            'exp_year' => $gatewayData['authorization']['exp_year'] ?? null,
            'card_type' => $gatewayData['authorization']['card_type'] ?? null,
            'bank' => $gatewayData['authorization']['bank'] ?? null,
            'channel' => $gatewayData['channel'] ?? null,
        ];

        return $this->update($updateData);
    }

    public function markAsFailed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_response' => $reason,
        ]);
    }

    public function markAsRefunded(): bool
    {
        return $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    public function getFormattedFeesAttribute(): string
    {
        return $this->fees ? '₦' . number_format($this->fees, 2) : '₦0.00';
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
```

#### Create Payment Log Model

**File**: `app/Models/PaymentLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'event_type',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
```

### Task 4.4: Configuration

#### Update PayStack Configuration

**File**: `config/paystack.php`

```php
<?php

return [
    /**
     * Public Key From Paystack Dashboard
     */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /**
     * Secret Key From Paystack Dashboard
     */
    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /**
     * Paystack Payment URL
     */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /**
     * Optional email to merchant
     */
    'merchantEmail' => env('MERCHANT_EMAIL'),

    /**
     * Webhook URL
     */
    'webhookUrl' => env('PAYSTACK_WEBHOOK_URL'),

    /**
     * Currency
     */
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),

    /**
     * Transaction Charges
     */
    'charges' => [
        'percentage' => env('PAYSTACK_CHARGE_PERCENTAGE', 1.5),
        'threshold' => env('PAYSTACK_CHARGE_THRESHOLD', 2500),
        'cap' => env('PAYSTACK_CHARGE_CAP', 2000),
    ],
];
```

#### Create Payment Configuration

**File**: `config/payment.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway used by the application.
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Configuration for supported payment gateways.
    |
    */
    'gateways' => [
        'paystack' => [
            'name' => 'PayStack',
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
            'currency' => 'NGN',
            'test_mode' => env('PAYSTACK_TEST_MODE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment configuration settings.
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'NGN'),
    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', '₦'),

    /*
    |--------------------------------------------------------------------------
    | Payment Timeout
    |--------------------------------------------------------------------------
    |
    | How long to wait for payment completion (in minutes).
    |
    */
    'timeout' => env('PAYMENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment webhook handling.
    |
    */
    'webhook' => [
        'verify_signature' => env('PAYMENT_WEBHOOK_VERIFY_SIGNATURE', true),
        'allowed_ips' => [
            '52.31.139.75',
            '52.49.173.169',
            '52.214.14.220',
        ], // PayStack webhook IPs
    ],
];
```

### Task 4.5: Payment Service

#### Create Payment Service

**File**: `app/Http/Services/PaymentService.php`

```php
<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentService
{
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->secretKey = config('paystack.secretKey');
        $this->publicKey = config('paystack.publicKey');
    }

    public function initializePayment(Order $order): array
    {
        try {
            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_reference' => (new Payment())->generatePaymentReference(),
                'amount' => $order->total_amount,
                'currency' => config('payment.currency', 'NGN'),
                'status' => 'pending',
            ]);

            // Prepare PayStack payment data
            $paymentData = [
                'email' => $order->user->email,
                'amount' => $payment->amount * 100, // Convert to kobo
                'reference' => $payment->payment_reference,
                'currency' => $payment->currency,
                'callback_url' => route('payment.callback'),
                'metadata' => [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'customer_name' => $order->user->name,
                    'order_number' => $order->order_number,
                ],
            ];

            // Log the request
            $this->logPaymentEvent($payment, 'initialize', 'pending', $paymentData);

            // Initialize payment with PayStack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $paymentData);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                // Update payment with PayStack response
                $payment->update([
                    'paystack_reference' => $responseData['data']['reference'],
                    'access_code' => $responseData['data']['access_code'],
                ]);

                // Log successful initialization
                $this->logPaymentEvent($payment, 'initialize', 'success', $paymentData, $responseData);

                return [
                    'status' => 'success',
                    'data' => [
                        'payment_id' => $payment->id,
                        'payment_reference' => $payment->payment_reference,
                        'authorization_url' => $responseData['data']['authorization_url'],
                        'access_code' => $responseData['data']['access_code'],
                        'amount' => $payment->formatted_amount,
                    ],
                ];
            } else {
                // Log failed initialization
                $this->logPaymentEvent($payment, 'initialize', 'failed', $paymentData, $responseData);

                $payment->markAsFailed($responseData['message'] ?? 'Payment initialization failed');

                return [
                    'status' => 'error',
                    'message' => $responseData['message'] ?? 'Payment initialization failed',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Payment initialization error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment initialization failed. Please try again.',
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $payment = Payment::where('payment_reference', $reference)
                ->orWhere('paystack_reference', $reference)
                ->first();

            if (!$payment) {
                return [
                    'status' => 'error',
                    'message' => 'Payment not found',
                ];
            }

            // Verify with PayStack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $responseData = $response->json();

            // Log verification attempt
            $this->logPaymentEvent($payment, 'verify', $response->successful() ? 'success' : 'failed',
                ['reference' => $reference], $responseData);

            if ($response->successful() && $responseData['status'] && $responseData['data']['status'] === 'success') {
                // Payment is successful
                $gatewayData = $responseData['data'];
                $payment->markAsPaid($gatewayData);

                // Update order payment status
                $payment->order->update(['payment_status' => 'paid']);

                return [
                    'status' => 'success',
                    'data' => [
                        'payment' => $payment,
                        'order' => $payment->order,
                        'gateway_data' => $gatewayData,
                    ],
                ];
            } else {
                // Payment failed
                $payment->markAsFailed($responseData['data']['gateway_response'] ?? 'Payment verification failed');

                return [
                    'status' => 'error',
                    'message' => $responseData['data']['gateway_response'] ?? 'Payment verification failed',
                    'data' => ['payment' => $payment],
                ];
            }

        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment verification failed. Please try again.',
            ];
        }
    }

    public function handleWebhook(Request $request): array
    {
        try {
            // Verify webhook signature
            if (config('payment.webhook.verify_signature')) {
                $signature = $request->header('x-paystack-signature');
                $body = $request->getContent();
                $expectedSignature = hash_hmac('sha512', $body, config('paystack.secretKey'));

                if (!hash_equals($expectedSignature, $signature)) {
                    Log::warning('Invalid webhook signature', [
                        'expected' => $expectedSignature,
                        'received' => $signature,
                    ]);

                    return [
                        'status' => 'error',
                        'message' => 'Invalid signature',
                    ];
                }
            }

            $event = $request->json()->all();
            $eventType = $event['event'] ?? null;
            $data = $event['data'] ?? [];

            // Handle charge.success event
            if ($eventType === 'charge.success') {
                $reference = $data['reference'] ?? null;

                if ($reference) {
                    $result = $this->verifyPayment($reference);

                    if ($result['status'] === 'success') {
                        Log::info('Webhook payment processed successfully', [
                            'reference' => $reference,
                            'event' => $eventType,
                        ]);
                    }

                    return $result;
                }
            }

            // Log unhandled webhook events
            Log::info('Unhandled webhook event', [
                'event' => $eventType,
                'data' => $data,
            ]);

            return [
                'status' => 'success',
                'message' => 'Webhook received',
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Webhook processing failed',
            ];
        }
    }

    public function getPaymentStatus(Payment $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'reference' => $payment->payment_reference,
            'status' => $payment->status,
            'amount' => $payment->formatted_amount,
            'order_number' => $payment->order->order_number,
            'paid_at' => $payment->paid_at,
            'failed_at' => $payment->failed_at,
        ];
    }

    protected function logPaymentEvent(Payment $payment, string $eventType, string $status,
        array $requestData = [], array $responseData = []): void
    {
        PaymentLog::create([
            'payment_id' => $payment->id,
            'event_type' => $eventType,
            'status' => $status,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function calculatePaymentFees(float $amount): array
    {
        $percentage = config('paystack.charges.percentage', 1.5);
        $threshold = config('paystack.charges.threshold', 2500);
        $cap = config('paystack.charges.cap', 2000);

        $percentageFee = ($amount * $percentage) / 100;

        // Apply cap if amount is above threshold
        if ($amount >= $threshold) {
            $percentageFee = min($percentageFee, $cap);
        }

        $totalFees = $percentageFee;

        return [
            'amount' => $amount,
            'fees' => round($totalFees, 2),
            'total' => round($amount + $totalFees, 2),
            'formatted_fees' => '₦' . number_format($totalFees, 2),
            'formatted_total' => '₦' . number_format($amount + $totalFees, 2),
        ];
    }
}
```

### Task 4.6: API Resources

#### Create Payment Resource

**File**: `app/Http/Resources/PaymentResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_reference' => $this->payment_reference,
            'paystack_reference' => $this->paystack_reference,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'channel' => $this->channel,
            'gateway_response' => $this->gateway_response,
            'fees' => $this->fees,
            'formatted_fees' => $this->formatted_fees,
            'authorization' => $this->when($this->authorization_code, [
                'authorization_code' => $this->authorization_code,
                'last4' => $this->last4,
                'exp_month' => $this->exp_month,
                'exp_year' => $this->exp_year,
                'card_type' => $this->card_type,
                'bank' => $this->bank,
            ]),
            'order' => new OrderResource($this->whenLoaded('order')),
            'timestamps' => [
                'paid_at' => $this->paid_at,
                'failed_at' => $this->failed_at,
                'refunded_at' => $this->refunded_at,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}
```

### Task 4.7: Payment Controller

#### Create Payment Controller

**File**: `app/Http/Controllers/PaymentController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Services\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initialize payment for an order
     */
    public function initialize(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with('user')->findOrFail($request->order_id);

        // Ensure user can only pay for their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if order can be paid for
        if ($order->payment_status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order has already been paid for'
            ], 400);
        }

        $result = $this->paymentService->initializePayment($order);

        return response()->json($result, $result['status'] === 'success' ? 200 : 400);
    }

    /**
     * Verify payment
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $result = $this->paymentService->verifyPayment($request->reference);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment' => new PaymentResource($result['data']['payment']),
                    'order' => new \App\Http\Resources\OrderResource($result['data']['order']),
                ]
            ], 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Get payment status
     */
    public function status(Request $request, Payment $payment): JsonResponse
    {
        // Ensure user can only check their own payments
        if ($payment->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        $status = $this->paymentService->getPaymentStatus($payment);

        return response()->json([
            'status' => 'success',
            'data' => $status
        ], 200);
    }

    /**
     * Handle PayStack webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleWebhook($request);

        return response()->json($result, $result['status'] === 'success' ? 200 : 400);
    }

    /**
     * Payment callback (for frontend redirect)
     */
    public function callback(Request $request): JsonResponse
    {
        $reference = $request->get('reference');

        if (!$reference) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment reference not provided'
            ], 400);
        }

        $result = $this->paymentService->verifyPayment($reference);

        return response()->json($result, $result['status'] === 'success' ? 200 : 400);
    }

    /**
     * Get user's payment history
     */
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::with(['order'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => PaymentResource::collection($payments)
        ], 200);
    }

    /**
     * Calculate payment fees
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $fees = $this->paymentService->calculatePaymentFees($request->amount);

        return response()->json([
            'status' => 'success',
            'data' => $fees
        ], 200);
    }
}
```

### Task 4.8: Update Order Model

#### Add Payment Relationship to Order Model

**File**: `app/Models/Order.php` (add this relationship)

```php
// Add this to the Order model
public function payments(): HasMany
{
    return $this->hasMany(Payment::class);
}

public function latestPayment(): HasOne
{
    return $this->hasOne(Payment::class)->latestOfMany();
}
```

### Task 4.9: Routes

#### Add Payment Routes

**File**: `routes/api.php` (add these routes)

```php
// Payment routes
Route::middleware(['auth:sanctum'])->prefix('payments')->group(function () {
    Route::post('/initialize', [PaymentController::class, 'initialize']);
    Route::post('/verify', [PaymentController::class, 'verify']);
    Route::get('/history', [PaymentController::class, 'history']);
    Route::get('/{payment}/status', [PaymentController::class, 'status']);
    Route::post('/calculate-fees', [PaymentController::class, 'calculateFees']);
});

// Public webhook route (no authentication required)
Route::post('/payments/webhook/paystack', [PaymentController::class, 'webhook'])->name('payment.webhook.paystack');

// Payment callback route (for frontend redirect)
Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payment.callback');
```

### Task 4.10: Environment Variables

#### Add Required Environment Variables

**File**: `.env` (add these variables)

```env
# PayStack Configuration
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key_here
PAYSTACK_SECRET_KEY=sk_test_your_secret_key_here
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret_here
PAYSTACK_TEST_MODE=true

# Payment Configuration
PAYMENT_DEFAULT_GATEWAY=paystack
PAYMENT_CURRENCY=NGN
PAYMENT_CURRENCY_SYMBOL=₦
PAYMENT_TIMEOUT=30
PAYMENT_WEBHOOK_VERIFY_SIGNATURE=true

# PayStack Charges (optional - defaults will be used if not set)
PAYSTACK_CHARGE_PERCENTAGE=1.5
PAYSTACK_CHARGE_THRESHOLD=2500
PAYSTACK_CHARGE_CAP=2000

# Merchant Email (optional)
MERCHANT_EMAIL=admin@healthnexus.com
```

### Task 4.11: Factories

#### Create Payment Factory

**File**: `database/factories/PaymentFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);

        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'payment_reference' => 'HN-PAY-' . date('Ymd') . '-' . strtoupper($this->faker->randomNumber(8)),
            'paystack_reference' => $this->faker->uuid(),
            'amount' => $amount,
            'currency' => 'NGN',
            'status' => $this->faker->randomElement(['pending', 'success', 'failed']),
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'ussd']),
            'fees' => $amount * 0.015, // 1.5% fee
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'paid_at' => now(),
            'authorization_code' => 'AUTH_' . $this->faker->randomNumber(8),
            'last4' => $this->faker->randomNumber(4),
            'card_type' => $this->faker->randomElement(['visa', 'mastercard']),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_response' => 'Transaction failed',
        ]);
    }
}
```

## Testing

### API Endpoints to Test

1. **POST /api/payments/initialize** - Initialize payment
2. **POST /api/payments/verify** - Verify payment
3. **GET /api/payments/{id}/status** - Get payment status
4. **GET /api/payments/history** - Payment history
5. **POST /api/payments/webhook/paystack** - Webhook handler
6. **POST /api/payments/calculate-fees** - Calculate fees

### Test Scenarios

-   Payment initialization with valid order
-   Payment verification with valid/invalid references
-   Webhook processing with valid/invalid signatures
-   Payment status updates and order completion
-   Fee calculations for different amounts
-   Error handling for failed payments

## Success Criteria

-   [ ] PayStack integration works correctly
-   [ ] Payment initialization returns proper URLs
-   [ ] Payment verification updates order status
-   [ ] Webhook handling processes events correctly
-   [ ] Payment logs track all transactions
-   [ ] Fee calculations are accurate
-   [ ] Security measures prevent unauthorized access
-   [ ] Error handling covers all scenarios
-   [ ] All payment states are properly managed

## Next Phase

Once Phase 4 is complete, proceed to **Phase 5: Admin Dashboard APIs**.
