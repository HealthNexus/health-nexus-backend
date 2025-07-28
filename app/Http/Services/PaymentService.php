<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentService
{
    public function initializePayment(Order $order): array
    {
        try {
            // Generate PayStack reference
            $reference = Paystack::genTranxRef();

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_reference' => $reference,
                'amount' => $order->total_amount,
                'currency' => config('paystack.currency', 'NGN'),
                'status' => 'pending',
            ]);

            // Prepare PayStack payment data
            $paymentData = [
                'email' => $order->user->email,
                'amount' => $payment->amount * 100, // Convert to kobo
                'reference' => $reference,
                'currency' => $payment->currency,
                'orderID' => $order->id,
                'metadata' => json_encode([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'customer_name' => $order->user->name,
                    'order_number' => $order->order_number,
                ]),
            ];

            // Log the request
            $this->logPaymentEvent($payment, 'initialize', 'pending', $paymentData);

            // Initialize payment with PayStack SDK
            $authorizationUrl = Paystack::getAuthorizationUrl($paymentData);

            if (!$authorizationUrl->status) {
                // Log failed initialization
                $this->logPaymentEvent($payment, 'initialize', 'failed', $paymentData, $authorizationUrl);

                $payment->markAsFailed($authorizationUrl->message ?? 'Payment initialization failed');

                return [
                    'status' => 'error',
                    'message' => $authorizationUrl->message ?? 'Payment initialization failed',
                ];
            }

            // Update payment with PayStack response
            $payment->update([
                'paystack_reference' => $reference,
                'access_code' => $authorizationUrl->data['access_code'] ?? null,
            ]);

            // Log successful initialization
            $this->logPaymentEvent($payment, 'initialize', 'success', $paymentData, $authorizationUrl->data);

            return [
                'status' => 'success',
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'authorization_url' => $authorizationUrl->data['authorization_url'],
                    'access_code' => $authorizationUrl->data['access_code'],
                    'amount' => $payment->formatted_amount,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Payment initialization error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
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

            // Verify payment using PayStack SDK
            $paymentDetails = Paystack::getPaymentData();

            // Log verification attempt
            $this->logPaymentEvent(
                $payment,
                'verify',
                $paymentDetails['status'] ? 'success' : 'failed',
                ['reference' => $reference],
                $paymentDetails
            );

            if ($paymentDetails['status'] && $paymentDetails['data']['status'] === 'success') {
                // Payment is successful
                $gatewayData = $paymentDetails['data'];
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
                $payment->markAsFailed($paymentDetails['data']['gateway_response'] ?? 'Payment verification failed');

                return [
                    'status' => 'error',
                    'message' => $paymentDetails['data']['gateway_response'] ?? 'Payment verification failed',
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
                'message' => 'Webhook processed',
            ];
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Webhook processing failed',
            ];
        }
    }

    protected function logPaymentEvent(Payment $payment, string $eventType, string $status, array $requestData = [], array $responseData = []): void
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
}
