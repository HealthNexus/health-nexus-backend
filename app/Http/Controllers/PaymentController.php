<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    /**
     * Initialize payment for an order (Alternative SDK pattern with immediate redirect)
     */
    public function initializeAndRedirect(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with('items.drug')
            ->where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order has already been paid'
                ], 400);
            }
            return redirect()->away(config('app.frontend_url') . '/orders/' . $order->id . '?error=already_paid');
        }

        try {
            // Create payment record with PayStack generated reference
            $reference = Paystack::genTranxRef();

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'payment_reference' => $reference,
                'amount' => $order->total_amount,
                'currency' => 'GHS',
                'status' => 'pending',
            ]);

            // Prepare payment data for PayStack
            $paymentData = [
                'email' => auth()->user()->email,
                'amount' => $order->total_amount * 100, // Convert to pesewas
                'reference' => $reference,
                'currency' => 'GHS',
                'orderID' => $order->id,
                'callback_url' => route('payment.callback'),
                'metadata' => json_encode([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id(),
                    'order_number' => $order->order_number,
                    'custom_fields' => [
                        [
                            'display_name' => 'Order Number',
                            'variable_name' => 'order_number',
                            'value' => $order->order_number
                        ]
                    ]
                ])
            ];

            // Use SDK's redirect method for seamless flow
            $authorizationUrl = Paystack::getAuthorizationUrl($paymentData);
            
            if (!isset($authorizationUrl->status) || !$authorizationUrl->status) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment initialization failed',
                        'details' => $authorizationUrl->message ?? 'Unknown error from payment gateway'
                    ], 400);
                }
                return redirect()->away(config('app.frontend_url') . '/orders/' . $order->id . '?error=payment_failed');
            }
            
            return $authorizationUrl->redirectNow();
        } catch (\Exception $e) {
            Log::error('Payment initialization failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed',
                    'details' => 'An unexpected error occurred'
                ], 500);
            }

            return redirect()->away(config('app.frontend_url') . '/payment/failed?error=initialization_failed');
        }
    }

    /**
     * Initialize payment for an order
     */
    public function initialize(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with('items.drug')
            ->where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order has already been paid'
            ], 400);
        }

        try {
            // Create payment record with PayStack generated reference
            $reference = Paystack::genTranxRef();

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'payment_reference' => $reference,
                'amount' => $order->total_amount,
                'currency' => 'GHS',
                'status' => 'pending',
            ]);

            // Prepare payment data for PayStack
            $paymentData = [
                'email' => auth()->user()->email,
                'amount' => $order->total_amount * 100, // Convert to pesewas
                'reference' => $reference,
                'currency' => 'GHS',
                'orderID' => $order->id,
                'metadata' => json_encode([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'user_id' => auth()->id(),
                    'order_number' => $order->order_number,
                    'custom_fields' => [
                        [
                            'display_name' => 'Order Number',
                            'variable_name' => 'order_number',
                            'value' => $order->order_number
                        ]
                    ]
                ])
            ];

            Log::info('Prepared payment data for Paystack', ['payment_data' => $paymentData]);

            // Initialize payment using PayStack SDK
            $authorizationUrl = Paystack::getAuthorizationUrl($paymentData);

            Log::info('Paystack response received', [
                'has_status' => isset($authorizationUrl->status),
                'status' => $authorizationUrl->status ?? 'no status',
                'has_url' => isset($authorizationUrl->url),
                'response_type' => gettype($authorizationUrl),
                'response_keys' => is_object($authorizationUrl) ? array_keys(get_object_vars($authorizationUrl)) : []
            ]);

            // Check if the response is valid - either has status=true OR has a checkout URL
            $hasValidResponse = (isset($authorizationUrl->status) && $authorizationUrl->status) || 
                               (isset($authorizationUrl->url) && $authorizationUrl->url);

            if (!$hasValidResponse) {
                Log::error('Paystack authorization failed', [
                    'response' => is_object($authorizationUrl) ? get_object_vars($authorizationUrl) : $authorizationUrl
                ]);
                
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => [
                        'error' => true,
                        'message' => $authorizationUrl->message ?? 'Unknown error from payment gateway',
                        'response' => is_object($authorizationUrl) ? get_object_vars($authorizationUrl) : $authorizationUrl
                    ]
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed',
                    'details' => $authorizationUrl->message ?? 'Unknown error from payment gateway'
                ], 400);
            }

            Log::info('Paystack authorization successful');

            // Update payment with PayStack response
            $payment->update([
                'paystack_reference' => $reference,
                'access_code' => $authorizationUrl->data['access_code'] ?? null,
                'gateway_response' => is_object($authorizationUrl) ? get_object_vars($authorizationUrl) : $authorizationUrl
            ]);

            // Get the authorization URL - it might be in different places depending on response format
            $checkoutUrl = $authorizationUrl->url ?? 
                          ($authorizationUrl->data['authorization_url'] ?? null);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'authorization_url' => $checkoutUrl,
                    'access_code' => $authorizationUrl->data['access_code'] ?? null,
                    'amount' => config('payment.currency_symbol') . number_format($order->total_amount, 2)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Payment initialization failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment initialization failed',
                'details' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Verify payment status
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string'
        ]);

        $payment = Payment::where('payment_reference', $request->reference)
            ->orWhere('paystack_reference', $request->reference)
            ->firstOrFail();

        // Check if payment belongs to authenticated user
        if ($payment->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        try {
            // Verify payment using PayStack SDK
            $paymentDetails = Paystack::getPaymentData();

            if (!$paymentDetails['status']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment verification failed',
                    'details' => $paymentDetails['message'] ?? 'Unknown error'
                ], 400);
            }

            $paystackData = $paymentDetails['data'];

            // Check if the payment was successful
            $status = $paystackData['status'] === 'success' ? 'success' : 'failed';

            // Update payment status
            $payment->update([
                'status' => $status,
                'paystack_reference' => $paystackData['reference'],
                'gateway_response' => $paystackData,
                'paid_at' => $status === 'success' ? now() : null,
                'failed_at' => $status === 'failed' ? now() : null,
                'payment_method' => $paystackData['channel'] ?? null,
                'authorization_code' => $paystackData['authorization']['authorization_code'] ?? null,
                'last4' => $paystackData['authorization']['last4'] ?? null,
                'exp_month' => $paystackData['authorization']['exp_month'] ?? null,
                'exp_year' => $paystackData['authorization']['exp_year'] ?? null,
                'card_type' => $paystackData['authorization']['card_type'] ?? null,
                'bank' => $paystackData['authorization']['bank'] ?? null,
            ]);

            // Update order payment status if payment successful
            if ($status === 'success') {
                $payment->order->update([
                    'payment_status' => 'paid'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment' => $payment->fresh(),
                    'order' => $payment->order->fresh(),
                    'verified' => $status === 'success'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'reference' => $request->reference,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed'
            ], 500);
        }
    }

    /**
     * Handle payment callback from Paystack
     * This should be called when user returns from Paystack payment page
     */
    public function handleCallback(Request $request)
    {
        try {
            // Get transaction reference from request
            $reference = $request->query('reference') ?? $request->input('reference');
            
            if (!$reference) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No transaction reference provided'
                    ], 400);
                }
                return redirect()->away(config('app.frontend_url') . '/payment/failed?error=' . urlencode('No transaction reference provided'));
            }

            // Verify the transaction using the reference
            // Set the reference in the request for Paystack to use
            $request->merge(['reference' => $reference]);
            
            try {
                $paymentDetails = Paystack::getPaymentData();
            } catch (\Exception $paystackError) {
                Log::error('Paystack verification failed', [
                    'reference' => $reference,
                    'error' => $paystackError->getMessage()
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment verification failed with Paystack',
                        'details' => $paystackError->getMessage()
                    ], 400);
                }
                
                return redirect()->away(config('app.frontend_url') . '/payment/failed?error=' . urlencode('Payment verification failed'));
            }

            if (!$paymentDetails['status']) {
                // If this is a web callback, redirect to frontend with error
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment verification failed',
                        'details' => $paymentDetails['message'] ?? 'Unknown error'
                    ], 400);
                }

                // For web redirects, you might want to redirect to frontend
                return redirect()->away(config('app.frontend_url') . '/payment/failed?error=' . urlencode($paymentDetails['message'] ?? 'Payment failed'));
            }

            // Extract transaction data
            $data = $paymentDetails['data'];
            $reference = $data['reference'];

            $data = $paymentDetails['data'];
            $reference = $data['reference'];

            // Find payment record
            $payment = Payment::where('payment_reference', $reference)
                ->orWhere('paystack_reference', $reference)
                ->first();

            if (!$payment) {
                Log::warning('Payment not found for callback', ['reference' => $reference]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment record not found'
                    ], 404);
                }

                return redirect()->away(config('app.frontend_url') . '/payment/failed?error=Payment+not+found');
            }

            // Update payment with callback data
            $this->updatePaymentFromPaystackData($payment, $data);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'payment' => $payment->fresh(),
                        'order' => $payment->order->fresh(),
                        'transaction_successful' => $data['status'] === 'success'
                    ]
                ]);
            }

            // For web redirects, redirect to frontend with success/failure
            $status = $data['status'] === 'success' ? 'success' : 'failed';
            return redirect()->away(config('app.frontend_url') . "/payment/{$status}?reference={$reference}");
        } catch (\Exception $e) {
            Log::error('Payment callback handling failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment callback processing failed'
                ], 500);
            }

            return redirect()->away(config('app.frontend_url') . '/payment/failed?error=Processing+failed');
        }
    }

    /**
     * Calculate payment fees
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        $amount = $request->amount;

        // PayStack fee calculation for GHS
        // 3.9% + GH₵2.95 for transactions above GH₵10
        // 3.9% for transactions GH₵10 and below
        $feePercentage = 0.039; // 3.9%
        $fixedFee = $amount > 10 ? 2.95 : 0;

        $fee = ($amount * $feePercentage) + $fixedFee;
        $total = $amount + $fee;

        return response()->json([
            'status' => 'success',
            'data' => [
                'amount' => $amount,
                'fee' => round($fee, 2),
                'total' => round($total, 2),
                'formatted_amount' => config('payment.currency_symbol') . number_format($amount, 2),
                'formatted_fee' => config('payment.currency_symbol') . number_format($fee, 2),
                'formatted_total' => config('payment.currency_symbol') . number_format($total, 2)
            ]
        ]);
    }

    /**
     * Handle PayStack webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('X-Paystack-Signature');
        $body = $request->getContent();

        // Verify the signature
        $computedSignature = hash_hmac('sha512', $body, config('paystack.secretKey'));

        if (!hash_equals($signature, $computedSignature)) {
            Log::warning('Invalid PayStack webhook signature', [
                'signature' => $signature,
                'computed' => $computedSignature
            ]);

            return response()->json(['status' => 'error'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        try {
            switch ($event) {
                case 'charge.success':
                    $this->handleSuccessfulCharge($data);
                    break;

                case 'charge.failed':
                    $this->handleFailedCharge($data);
                    break;

                default:
                    Log::info('Unhandled PayStack webhook event: ' . $event, $data);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('PayStack webhook handling failed: ' . $e->getMessage(), [
                'event' => $event,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Update payment record with PayStack data
     */
    private function updatePaymentFromPaystackData(Payment $payment, array $data): void
    {
        $status = $data['status'] === 'success' ? 'success' : 'failed';

        $updateData = [
            'status' => $status,
            'paystack_reference' => $data['reference'],
            'gateway_response' => $data,
            'payment_method' => $data['channel'] ?? null,
            'gateway_metadata' => $data['metadata'] ?? [],
            'fees' => isset($data['fees']) ? $data['fees'] / 100 : null, // Convert from pesewas
        ];

        if ($status === 'success') {
            $updateData['paid_at'] = now();

            // Add authorization details if available
            if (isset($data['authorization'])) {
                $auth = $data['authorization'];
                $updateData['authorization_code'] = $auth['authorization_code'] ?? null;
                $updateData['last4'] = $auth['last4'] ?? null;
                $updateData['exp_month'] = $auth['exp_month'] ?? null;
                $updateData['exp_year'] = $auth['exp_year'] ?? null;
                $updateData['card_type'] = $auth['card_type'] ?? null;
                $updateData['bank'] = $auth['bank'] ?? null;
            }
        } else {
            $updateData['failed_at'] = now();
        }

        $payment->update($updateData);

        // Update order payment status if payment successful
        if ($status === 'success') {
            $payment->order->update([
                'payment_status' => 'paid'
            ]);
        }
    }

    /**
     * Handle successful charge webhook
     */
    private function handleSuccessfulCharge(array $data): void
    {
        $reference = $data['reference'];
        $payment = Payment::where('paystack_reference', $reference)
            ->orWhere('payment_reference', $reference)
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for successful charge', ['reference' => $reference]);
            return;
        }

        // Update payment using the helper method
        $this->updatePaymentFromPaystackData($payment, $data);

        Log::info('Payment completed via webhook', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $payment->amount
        ]);
    }

    /**
     * Handle failed charge webhook
     */
    private function handleFailedCharge(array $data): void
    {
        $reference = $data['reference'];
        $payment = Payment::where('paystack_reference', $reference)
            ->orWhere('payment_reference', $reference)
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for failed charge', ['reference' => $reference]);
            return;
        }

        // Update payment using the helper method
        $this->updatePaymentFromPaystackData($payment, $data);

        Log::info('Payment failed via webhook', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'reason' => $data['gateway_response'] ?? 'Unknown'
        ]);
    }

    /**
     * Get payment history for authenticated user
     */
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::with(['order.items.drug'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total()
            ]
        ]);
    }

    /**
     * Get specific payment details
     */
    public function show($paymentId): JsonResponse
    {
        $payment = Payment::with(['order.items.drug'])
            ->where('id', $paymentId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }
}
