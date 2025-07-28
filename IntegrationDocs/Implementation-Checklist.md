# Implementation Checklist & Quick Setup Guide

## Health Nexus E-Pharmacy Integration

### Table of Contents

1. [Critical Backend Fixes Required](#critical-backend-fixes-required)
2. [Frontend Setup Steps](#frontend-setup-steps)
3. [Integration Verification](#integration-verification)
4. [Deployment Checklist](#deployment-checklist)
5. [Troubleshooting Guide](#troubleshooting-guide)

---

## Critical Backend Fixes Required

### ⚠️ MUST IMPLEMENT BEFORE FRONTEND INTEGRATION

#### 1. Create Missing PaymentController (CRITICAL)

**File:** `app/Http/Controllers/PaymentController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Services\PaymentService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

        try {
            $order = Order::with(['user', 'items'])->findOrFail($request->order_id);

            // Ensure user owns the order
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            // Check if order is in correct state for payment
            if ($order->payment_status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order is not available for payment'
                ], 400);
            }

            $paymentData = $this->paymentService->initializePayment($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment initialized successfully',
                'data' => $paymentData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'order_id' => $request->order_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize payment. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify payment status
     */
    public function verify(string $reference): JsonResponse
    {
        try {
            $verification = $this->paymentService->verifyPayment($reference);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verification completed',
                'data' => $verification
            ], 200);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment verification failed',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 400);
        }
    }

    /**
     * Handle PayStack webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleWebhook($request);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed'
            ], 400);
        }
    }
}
```

#### 2. Add Payment Routes to API (CRITICAL)

**File:** `routes/api.php`

Add these routes in the authenticated section:

```php
// Add inside the Route::group(['middleware' => ['auth:sanctum']], function () {
// section, around line 90

// Payment routes
Route::prefix('payment')->group(function () {
    Route::post('/initialize', [App\Http\Controllers\PaymentController::class, 'initialize']);
    Route::get('/verify/{reference}', [App\Http\Controllers\PaymentController::class, 'verify']);
});

// Public webhook (outside authenticated group)
// Add this AFTER the closing bracket of the authenticated group
Route::post('/payment/webhook', [App\Http\Controllers\PaymentController::class, 'webhook']);
```

#### 3. Fix PaymentService Configuration (IMPORTANT)

**File:** `config/paystack.php` (Create if not exists)

```php
<?php

return [
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),
    'secretKey' => env('PAYSTACK_SECRET_KEY'),
    'webhookSecret' => env('PAYSTACK_WEBHOOK_SECRET'),
    'baseUrl' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
];
```

**File:** `.env` (Add these variables)

```env
# PayStack Configuration
PAYSTACK_PUBLIC_KEY=pk_test_your_test_key_here
PAYSTACK_SECRET_KEY=sk_test_your_test_secret_here
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret_here

# Payment Configuration
PAYMENT_CURRENCY=NGN
```

#### 4. Update PaymentService Constructor (REQUIRED)

**File:** `app/Http/Services/PaymentService.php`

Update the constructor to use the config file:

```php
public function __construct()
{
    $this->secretKey = config('paystack.secretKey');
    $this->publicKey = config('paystack.publicKey');
}
```

#### 5. Configure CORS for Frontend (IMPORTANT)

**File:** `config/cors.php`

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',    // React dev server
        'http://localhost:5173',    // Vite dev server
        'http://localhost:8080',    // Vue CLI dev server
        'http://127.0.0.1:3000',    // Alternative localhost
        'http://127.0.0.1:5173',    // Alternative localhost
        // Add your production frontend URL here
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

#### 6. Add Route Names for Payment Callbacks (RECOMMENDED)

**File:** `routes/web.php`

```php
// Add these routes for PayStack callbacks
Route::get('/payment/callback', function () {
    return view('payment.callback');
})->name('payment.callback');

Route::get('/payment/success', function () {
    return view('payment.success');
})->name('payment.success');

Route::get('/payment/cancel', function () {
    return view('payment.cancel');
})->name('payment.cancel');
```

---

## Frontend Setup Steps

### 1. Project Initialization

#### React with TypeScript

```bash
npx create-react-app health-nexus-frontend --template typescript
cd health-nexus-frontend

# Install required dependencies
npm install axios react-router-dom @reduxjs/toolkit react-redux
npm install -D @types/node
```

#### Vue 3 with TypeScript

```bash
npm create vue@latest health-nexus-frontend
cd health-nexus-frontend

# Select: TypeScript, Router, Pinia, ESLint
npm install

# Install additional dependencies
npm install axios pinia
```

#### Next.js

```bash
npx create-next-app@latest health-nexus-frontend --typescript --tailwind --eslint
cd health-nexus-frontend

# Install additional dependencies
npm install axios swr
```

### 2. Environment Configuration

**File:** `.env.local` (or `.env`)

```env
# API Configuration
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_BASE_URL=http://localhost:8000

# PayStack Configuration
REACT_APP_PAYSTACK_PUBLIC_KEY=pk_test_your_test_key_here

# App Configuration
REACT_APP_NAME="Health Nexus E-Pharmacy"
REACT_APP_VERSION=1.0.0
```

### 3. API Client Setup

**File:** `src/lib/api.ts`

```typescript
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from "axios";

// Create axios instance
const api = axios.create({
    baseURL: process.env.REACT_APP_API_URL || "http://localhost:8000/api",
    timeout: 30000,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Request interceptor for auth token
api.interceptors.request.use(
    (config: AxiosRequestConfig) => {
        const token = localStorage.getItem("authToken");
        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor for error handling
api.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error: AxiosError) => {
        if (error.response?.status === 401) {
            localStorage.removeItem("authToken");
            localStorage.removeItem("user");
            window.location.href = "/login";
        }
        return Promise.reject(error);
    }
);

export default api;
```

### 4. Authentication Service

**File:** `src/services/auth.service.ts`

```typescript
import api from "../lib/api";

export interface User {
    id: number;
    name: string;
    email: string;
    role: {
        id: number;
        name: string;
        slug: string;
    };
}

export interface AuthResponse {
    user: User;
    token: string;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface RegisterData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

class AuthService {
    async login(credentials: LoginCredentials): Promise<AuthResponse> {
        const response = await api.post("/login", credentials);
        const { user, token } = response.data.data;

        localStorage.setItem("authToken", token);
        localStorage.setItem("user", JSON.stringify(user));

        return { user, token };
    }

    async register(userData: RegisterData): Promise<AuthResponse> {
        const response = await api.post("/register", userData);
        const { user, token } = response.data.data;

        localStorage.setItem("authToken", token);
        localStorage.setItem("user", JSON.stringify(user));

        return { user, token };
    }

    async logout(): Promise<void> {
        try {
            await api.post("/logout");
        } catch (error) {
            console.error("Logout error:", error);
        } finally {
            localStorage.removeItem("authToken");
            localStorage.removeItem("user");
        }
    }

    getCurrentUser(): User | null {
        const userStr = localStorage.getItem("user");
        return userStr ? JSON.parse(userStr) : null;
    }

    getToken(): string | null {
        return localStorage.getItem("authToken");
    }

    isAuthenticated(): boolean {
        return !!this.getToken();
    }

    isAdmin(): boolean {
        const user = this.getCurrentUser();
        return user?.role?.slug === "admin";
    }
}

export default new AuthService();
```

### 5. State Management Setup (Redux Toolkit)

**File:** `src/store/index.ts`

```typescript
import { configureStore } from "@reduxjs/toolkit";
import authReducer from "./slices/authSlice";
import cartReducer from "./slices/cartSlice";
import drugReducer from "./slices/drugSlice";

export const store = configureStore({
    reducer: {
        auth: authReducer,
        cart: cartReducer,
        drugs: drugReducer,
    },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
```

---

## Integration Verification

### 1. Backend Verification Checklist

Run these commands to verify backend setup:

```bash
# 1. Check if all routes are registered
php artisan route:list | grep -E "(payment|cart|order|drug)"

# 2. Test PaymentController exists
php artisan tinker
>>> app(App\Http\Controllers\PaymentController::class);

# 3. Verify PayStack configuration
php artisan tinker
>>> config('paystack.publicKey');
>>> config('paystack.secretKey');

# 4. Test database connection
php artisan migrate:status

# 5. Check if API is accessible
curl http://localhost:8000/api/drugs

# 6. Test authentication endpoint
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### 2. Frontend-Backend Connection Test

Create a test component to verify connection:

**File:** `src/components/ApiTest.tsx`

```typescript
import React, { useEffect, useState } from "react";
import api from "../lib/api";

const ApiTest: React.FC = () => {
    const [apiStatus, setApiStatus] = useState<string>("Testing...");
    const [drugs, setDrugs] = useState<any[]>([]);

    useEffect(() => {
        testApiConnection();
    }, []);

    const testApiConnection = async () => {
        try {
            // Test basic API connection
            const response = await api.get("/drugs");
            setApiStatus("✅ API Connected Successfully");
            setDrugs(response.data.data.data.slice(0, 3)); // Show first 3 drugs
        } catch (error) {
            setApiStatus("❌ API Connection Failed");
            console.error("API Test Error:", error);
        }
    };

    return (
        <div
            style={{
                padding: "20px",
                border: "1px solid #ccc",
                margin: "20px",
            }}
        >
            <h3>API Connection Test</h3>
            <p>Status: {apiStatus}</p>

            {drugs.length > 0 && (
                <div>
                    <h4>Sample Drugs from API:</h4>
                    <ul>
                        {drugs.map((drug) => (
                            <li key={drug.id}>
                                {drug.name} - ₦{drug.price}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

export default ApiTest;
```

### 3. Core Feature Testing

#### Test Authentication

```typescript
// Test login functionality
const testLogin = async () => {
    try {
        const result = await authService.login({
            email: "test@example.com",
            password: "password123",
        });
        console.log("Login successful:", result);
    } catch (error) {
        console.error("Login failed:", error);
    }
};
```

#### Test Cart Operations

```typescript
// Test adding item to cart
const testAddToCart = async () => {
    try {
        const response = await api.post("/cart/add", {
            drug_id: 1,
            quantity: 2,
        });
        console.log("Item added to cart:", response.data);
    } catch (error) {
        console.error("Add to cart failed:", error);
    }
};
```

#### Test Payment Initialization

```typescript
// Test payment initialization
const testPaymentInit = async (orderId: number) => {
    try {
        const response = await api.post("/payment/initialize", {
            order_id: orderId,
        });
        console.log("Payment initialized:", response.data);
    } catch (error) {
        console.error("Payment init failed:", error);
    }
};
```

---

## Deployment Checklist

### Backend Deployment

#### 1. Environment Configuration

```bash
# Production .env settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Database (use MySQL/PostgreSQL in production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=health_nexus_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# PayStack Live Keys
PAYSTACK_PUBLIC_KEY=pk_live_your_live_key
PAYSTACK_SECRET_KEY=sk_live_your_live_secret
PAYSTACK_WEBHOOK_SECRET=your_live_webhook_secret

# CORS
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

#### 2. Production Deployment Commands

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Seed admin user (if needed)
php artisan db:seed --class=AdminUserSeeder
```

### Frontend Deployment

#### 1. Build Configuration

```json
// package.json
{
    "scripts": {
        "build:prod": "REACT_APP_API_URL=https://api.yourdomain.com/api npm run build",
        "build:staging": "REACT_APP_API_URL=https://staging-api.yourdomain.com/api npm run build"
    }
}
```

#### 2. Environment Variables for Production

```env
REACT_APP_API_URL=https://api.yourdomain.com/api
REACT_APP_PAYSTACK_PUBLIC_KEY=pk_live_your_live_key
REACT_APP_ENVIRONMENT=production
```

---

## Troubleshooting Guide

### Common Backend Issues

#### 1. PaymentController Not Found

```bash
# Verify the file exists
ls -la app/Http/Controllers/PaymentController.php

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### 2. CORS Issues

```bash
# Check CORS configuration
php artisan config:show cors

# Common CORS error solutions:
# 1. Add your frontend domain to allowed_origins
# 2. Set supports_credentials to true
# 3. Restart your Laravel server after CORS changes
```

#### 3. Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Run migrations if needed
php artisan migrate:fresh --seed
```

#### 4. PayStack Configuration Issues

```bash
# Verify PayStack configuration
php artisan tinker
>>> config('paystack');

# Check if environment variables are loaded
>>> env('PAYSTACK_PUBLIC_KEY');
```

### Common Frontend Issues

#### 1. API Connection Failed

```javascript
// Check if API URL is correct
console.log('API URL:', process.env.REACT_APP_API_URL);

// Test with curl
curl http://localhost:8000/api/drugs
```

#### 2. CORS Blocked

```javascript
// Error: "blocked by CORS policy"
// Solution: Update backend CORS configuration
// Add your frontend URL to allowed_origins in config/cors.php
```

#### 3. Authentication Token Issues

```javascript
// Check if token is stored correctly
console.log("Token:", localStorage.getItem("authToken"));

// Verify token format (should be a JWT)
// Check if token is being sent in requests
```

#### 4. Payment Integration Issues

```javascript
// PayStack script not loading
// Ensure PayStack script is included in index.html:
// <script src="https://js.paystack.co/v1/inline.js"></script>

// Check public key configuration
console.log("PayStack Key:", process.env.REACT_APP_PAYSTACK_PUBLIC_KEY);
```

### Quick Debugging Commands

#### Backend Debugging

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Check specific route
php artisan route:list | grep payment

# Test specific endpoint
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

#### Frontend Debugging

```bash
# Check build errors
npm run build

# Run development server with debugging
npm start

# Check network requests in browser
# Open DevTools > Network tab
```

---

## Success Indicators

### Backend Ready Indicators ✅

-   [ ] PaymentController responds correctly
-   [ ] All API routes return proper JSON structure
-   [ ] Authentication works with Sanctum tokens
-   [ ] CORS allows frontend domain
-   [ ] Database queries execute successfully
-   [ ] PayStack configuration is valid

### Frontend Ready Indicators ✅

-   [ ] API client connects to backend
-   [ ] Authentication flow works end-to-end
-   [ ] Products load and display correctly
-   [ ] Cart operations function properly
-   [ ] Payment initialization succeeds
-   [ ] Admin dashboard loads (for admin users)

### Integration Complete Indicators ✅

-   [ ] User can register and login
-   [ ] Products load on homepage
-   [ ] Items can be added to cart
-   [ ] Orders can be created and paid for
-   [ ] Admin can manage inventory and orders
-   [ ] All error scenarios are handled gracefully

---

## Final Notes

1. **Start with Backend Fixes**: Implement the PaymentController and routes before beginning frontend work
2. **Test Each Component**: Use the API testing guide to verify each backend endpoint
3. **Follow the Timeline**: Implement features in the order specified in the integration plan
4. **Monitor Logs**: Keep an eye on both Laravel logs and browser console for errors
5. **Security First**: Ensure proper authentication and validation at every step

**Ready to integrate? Begin with the Critical Backend Fixes section above! 🚀**
