# Laravel Paystack SDK Implementation Guide

## Overview

Your Health Nexus backend is already correctly implementing the Laravel Paystack SDK! The implementation follows the official documentation patterns and best practices.

## ✅ What's Already Correct

### 1. SDK Installation & Configuration

-   ✅ Using `unicodeveloper/laravel-paystack` package
-   ✅ Proper facade import: `use Unicodeveloper\Paystack\Facades\Paystack;`
-   ✅ Configuration file at `config/paystack.php`
-   ✅ Environment variables properly set

### 2. Payment Initialization

```php
// Using SDK's genTranxRef() for reference generation
$reference = Paystack::genTranxRef();

// Using SDK's getAuthorizationUrl() method
$authorizationUrl = Paystack::getAuthorizationUrl($paymentData);
```

### 3. Payment Verification

```php
// Using SDK's getPaymentData() method
$paymentDetails = Paystack::getPaymentData();
```

### 4. Webhook Handling

```php
// Proper signature verification
$computedSignature = hash_hmac('sha512', $body, config('paystack.secretKey'));
```

## 🚀 Available Payment Flows

### Flow 1: API-Based (Current Implementation)

**Best for: SPA/Mobile Apps with custom UI**

```
1. POST /api/payments/initialize
   → Returns authorization_url for frontend to open

2. User completes payment on Paystack

3. POST /api/payments/verify
   → Frontend calls this to verify payment
```

### Flow 2: Direct Redirect (New Addition)

**Best for: Traditional web flow with seamless UX**

```
1. POST /api/payments/redirect
   → Directly redirects user to Paystack

2. User completes payment

3. GET /api/payments/callback
   → Automatically redirects back to frontend
```

## 📋 Environment Variables Required

```env
# Paystack Configuration
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxx
PAYSTACK_PAYMENT_URL=https://api.paystack.co
PAYSTACK_WEBHOOK_URL=your_webhook_secret
PAYSTACK_CURRENCY=NGN

# Frontend URL for redirects
FRONTEND_URL=http://localhost:9000
```

## 🔄 API Endpoints

### Authentication Required Routes

```
POST   /api/payments/initialize       - Initialize payment (returns URL)
POST   /api/payments/redirect         - Initialize & redirect immediately
POST   /api/payments/verify           - Verify payment status
POST   /api/payments/calculate-fees   - Calculate Paystack fees
GET    /api/payments/history          - User payment history
GET    /api/payments/{payment}        - Get specific payment
```

### Public Routes (No Auth)

```
POST   /api/payments/webhook/paystack - Paystack webhook
GET    /api/payments/callback         - Payment callback handler
```

## 💡 Frontend Integration Examples

### React/JavaScript - API Flow

```javascript
// Initialize Payment
const initializePayment = async (orderId) => {
    const response = await fetch("/api/payments/initialize", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ order_id: orderId }),
    });

    const data = await response.json();

    if (data.status === "success") {
        // Open Paystack payment page
        window.open(data.data.authorization_url, "_blank");
    }
};

// Verify Payment
const verifyPayment = async (reference) => {
    const response = await fetch("/api/payments/verify", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ reference }),
    });

    return await response.json();
};
```

### Direct Redirect Flow

```javascript
// Simple redirect to payment
const redirectToPayment = (orderId) => {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/api/payments/redirect";

    const orderInput = document.createElement("input");
    orderInput.type = "hidden";
    orderInput.name = "order_id";
    orderInput.value = orderId;

    const tokenInput = document.createElement("input");
    tokenInput.type = "hidden";
    tokenInput.name = "_token";
    tokenInput.value = csrfToken;

    form.appendChild(orderInput);
    form.appendChild(tokenInput);
    document.body.appendChild(form);
    form.submit();
};
```

## 🔧 Advanced Configuration

### Custom Fee Calculation

```php
// Already implemented in PaymentController
public function calculateFees(Request $request): JsonResponse
{
    $amount = $request->amount;
    $feePercentage = 0.015; // 1.5%
    $fixedFee = $amount > 2500 ? 100 : 0;
    $fee = ($amount * $feePercentage) + $fixedFee;

    return response()->json([
        'amount' => $amount,
        'fee' => round($fee, 2),
        'total' => round($amount + $fee, 2)
    ]);
}
```

### Webhook Security

```php
// Automatic signature verification in webhook handler
$signature = $request->header('X-Paystack-Signature');
$computedSignature = hash_hmac('sha512', $body, config('paystack.secretKey'));

if (!hash_equals($signature, $computedSignature)) {
    return response()->json(['status' => 'error'], 400);
}
```

## 🎯 Best Practices Implemented

1. **Reference Generation**: Using SDK's `genTranxRef()` for unique references
2. **Amount Conversion**: Properly converting Naira to Kobo (× 100)
3. **Error Handling**: Comprehensive try-catch blocks with logging
4. **Security**: Webhook signature verification
5. **Database Integrity**: Proper payment status tracking
6. **Metadata**: Rich metadata for payment tracking
7. **Authorization**: User-specific payment validation

## 🚨 Important Notes

1. **Currency**: Set to NGN (Nigerian Naira) in config
2. **Webhook Secret**: Use your Paystack secret key for webhook verification
3. **Frontend URL**: Configure `FRONTEND_URL` for proper redirects
4. **HTTPS**: Use HTTPS in production for webhook endpoints
5. **Testing**: Use test keys during development

## 📱 Mobile App Integration

For mobile apps, use the API flow with custom UI:

```dart
// Flutter example
Future<String?> initializePayment(int orderId) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/payments/initialize'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'order_id': orderId}),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    return data['data']['authorization_url'];
  }
  return null;
}
```

Your implementation is already production-ready and follows Laravel Paystack SDK best practices! 🎉
