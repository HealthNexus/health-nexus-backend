# Frontend Payment Integration Guide

## Overview of Payment Routes

You have three payment routes that serve different integration patterns:

1. **`POST /payments/webhook/paystack`** - PayStack webhook handler (backend-only)
2. **`GET /payments/callback`** - Payment callback handler (user returns from PayStack)
3. **`POST /payments/redirect`** - Direct payment initialization with redirect

## Route 1: PayStack Webhook (`POST /payments/webhook/paystack`)

### Purpose

This route handles automatic notifications from PayStack when payment status changes. **No frontend integration needed** - this is purely backend-to-backend communication.

### What it does

-   Receives notifications from PayStack about payment events
-   Validates webhook signature for security
-   Updates payment status in your database automatically
-   Handles `charge.success` and `charge.failed` events

### PayStack Configuration Required

In your PayStack dashboard, set your webhook URL to:

```
https://yourdomain.com/api/payments/webhook/paystack
```

---

## Route 2: Payment Callback (`GET /payments/callback`)

### Purpose

Handles users returning from PayStack payment page back to your application.

### Frontend Integration

#### For React/Vue SPA Applications:

```javascript
// 1. Handle the callback in your frontend router
// React Router example
import { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";

function PaymentCallback() {
    const location = useLocation();
    const navigate = useNavigate();

    useEffect(() => {
        const handleCallback = async () => {
            try {
                // The callback route will handle payment verification
                // and redirect to your frontend with status
                const params = new URLSearchParams(location.search);
                const reference = params.get("reference");
                const status = params.get("status");

                if (status === "success") {
                    // Show success message and redirect to order details
                    navigate(`/orders/success?reference=${reference}`);
                } else {
                    // Show error message
                    navigate(
                        `/orders/failed?error=${
                            params.get("error") || "Payment failed"
                        }`
                    );
                }
            } catch (error) {
                console.error("Payment callback error:", error);
                navigate("/orders/failed?error=processing_error");
            }
        };

        handleCallback();
    }, [location, navigate]);

    return (
        <div className="payment-processing">
            <h2>Processing Payment...</h2>
            <p>Please wait while we verify your payment.</p>
        </div>
    );
}
```

#### Set up your frontend routes:

```javascript
// React Router
<Route path="/payment/callback" component={PaymentCallback} />
<Route path="/orders/success" component={PaymentSuccess} />
<Route path="/orders/failed" component={PaymentFailed} />

// Vue Router
{
  path: '/payment/callback',
  component: PaymentCallback
},
{
  path: '/orders/success',
  component: PaymentSuccess
},
{
  path: '/orders/failed',
  component: PaymentFailed
}
```

#### Configure callback URL in PayStack initialization:

When using the regular `/payments/initialize` endpoint, ensure you set the callback URL:

```javascript
const initializePayment = async (orderId) => {
    const response = await fetch("/api/payments/initialize", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            order_id: orderId,
            callback_url: `${window.location.origin}/payment/callback`,
        }),
    });

    const result = await response.json();
    if (result.status === "success") {
        // Redirect user to PayStack
        window.location.href = result.data.authorization_url;
    }
};
```

---

## Route 3: Direct Payment Redirect (`POST /payments/redirect`)

### Purpose

Provides a seamless payment flow where users are immediately redirected to PayStack without custom frontend handling.

### Frontend Integration

#### Method 1: Form Submission (Recommended for traditional web apps)

```html
<!-- Simple HTML form -->
<form action="/api/payments/redirect" method="POST" id="payment-form">
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    <input type="hidden" name="order_id" value="123" />
    <button type="submit" class="btn btn-primary">Pay Now</button>
</form>
```

#### Method 2: JavaScript Form Submission (For SPA with authentication)

```javascript
const redirectToPayment = async (orderId) => {
    try {
        // Create a form dynamically
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "/api/payments/redirect";

        // Add order ID
        const orderInput = document.createElement("input");
        orderInput.type = "hidden";
        orderInput.name = "order_id";
        orderInput.value = orderId;
        form.appendChild(orderInput);

        // Add auth token if using Sanctum
        const token = localStorage.getItem("auth_token");
        if (token) {
            const authInput = document.createElement("input");
            authInput.type = "hidden";
            authInput.name = "Authorization";
            authInput.value = `Bearer ${token}`;
            form.appendChild(authInput);
        }

        // Submit form
        document.body.appendChild(form);
        form.submit();
    } catch (error) {
        console.error("Payment redirect error:", error);
    }
};

// Usage in React component
function PaymentButton({ orderId }) {
    const handlePayment = () => {
        redirectToPayment(orderId);
    };

    return (
        <button onClick={handlePayment} className="btn btn-primary">
            Pay Now
        </button>
    );
}
```

#### Method 3: Fetch with Window Navigation

```javascript
const redirectToPayment = async (orderId) => {
    try {
        const response = await fetch("/api/payments/redirect", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ order_id: orderId }),
            redirect: "follow",
        });

        if (response.redirected) {
            // If the response is a redirect, navigate to it
            window.location.href = response.url;
        } else {
            const result = await response.json();
            if (result.authorization_url) {
                window.location.href = result.authorization_url;
            }
        }
    } catch (error) {
        console.error("Payment redirect error:", error);
    }
};
```

---

## Complete Integration Examples

### React Component with All Payment Methods

```jsx
import React, { useState } from "react";
import { useNavigate } from "react-router-dom";

function PaymentMethods({ orderId, orderTotal }) {
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    // Method 1: API-based payment (custom UI control)
    const handleApiPayment = async () => {
        setLoading(true);
        try {
            const response = await fetch("/api/payments/initialize", {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${localStorage.getItem(
                        "auth_token"
                    )}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    order_id: orderId,
                    callback_url: `${window.location.origin}/payment/callback`,
                }),
            });

            const result = await response.json();

            if (result.status === "success") {
                // Option A: Open in new tab
                window.open(result.data.authorization_url, "_blank");

                // Option B: Redirect current window
                // window.location.href = result.data.authorization_url;

                // Start polling for payment status (optional)
                startPaymentPolling(result.data.payment_reference);
            } else {
                alert("Payment initialization failed: " + result.message);
            }
        } catch (error) {
            console.error("Payment error:", error);
            alert("Payment initialization failed");
        } finally {
            setLoading(false);
        }
    };

    // Method 2: Direct redirect payment (seamless)
    const handleDirectPayment = async () => {
        setLoading(true);
        try {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "/api/payments/redirect";

            const orderInput = document.createElement("input");
            orderInput.type = "hidden";
            orderInput.name = "order_id";
            orderInput.value = orderId;
            form.appendChild(orderInput);

            const tokenInput = document.createElement("input");
            tokenInput.type = "hidden";
            tokenInput.name = "Authorization";
            tokenInput.value = `Bearer ${localStorage.getItem("auth_token")}`;
            form.appendChild(tokenInput);

            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error("Redirect payment error:", error);
            setLoading(false);
        }
    };

    // Optional: Poll payment status for API method
    const startPaymentPolling = (reference) => {
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch("/api/payments/verify", {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "auth_token"
                        )}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ reference }),
                });

                const result = await response.json();

                if (result.status === "success" && result.data.verified) {
                    clearInterval(pollInterval);
                    navigate(`/orders/success?reference=${reference}`);
                }
            } catch (error) {
                console.error("Payment verification error:", error);
            }
        }, 3000); // Check every 3 seconds

        // Stop polling after 5 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
        }, 300000);
    };

    return (
        <div className="payment-methods">
            <h3>Payment Options for Order #{orderId}</h3>
            <p>Total: ₦{orderTotal.toLocaleString()}</p>

            <div className="payment-buttons">
                <button
                    onClick={handleApiPayment}
                    disabled={loading}
                    className="btn btn-primary"
                >
                    {loading ? "Processing..." : "Pay with API Method"}
                </button>

                <button
                    onClick={handleDirectPayment}
                    disabled={loading}
                    className="btn btn-success"
                >
                    {loading ? "Redirecting..." : "Pay with Direct Redirect"}
                </button>
            </div>
        </div>
    );
}

export default PaymentMethods;
```

### Vue.js Component Example

```vue
<template>
    <div class="payment-methods">
        <h3>Payment Options</h3>
        <p>Total: ₦{{ orderTotal.toLocaleString() }}</p>

        <div class="payment-buttons">
            <button
                @click="handleApiPayment"
                :disabled="loading"
                class="btn btn-primary"
            >
                {{ loading ? "Processing..." : "Pay with API Method" }}
            </button>

            <button
                @click="handleDirectPayment"
                :disabled="loading"
                class="btn btn-success"
            >
                {{ loading ? "Redirecting..." : "Pay with Direct Redirect" }}
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "PaymentMethods",
    props: {
        orderId: {
            type: Number,
            required: true,
        },
        orderTotal: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            loading: false,
        };
    },
    methods: {
        async handleApiPayment() {
            this.loading = true;
            try {
                const response = await fetch("/api/payments/initialize", {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${this.$store.state.auth.token}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        order_id: this.orderId,
                        callback_url: `${window.location.origin}/payment/callback`,
                    }),
                });

                const result = await response.json();

                if (result.status === "success") {
                    window.location.href = result.data.authorization_url;
                } else {
                    this.$toast.error(
                        "Payment initialization failed: " + result.message
                    );
                }
            } catch (error) {
                console.error("Payment error:", error);
                this.$toast.error("Payment initialization failed");
            } finally {
                this.loading = false;
            }
        },

        async handleDirectPayment() {
            this.loading = true;
            try {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "/api/payments/redirect";

                const orderInput = document.createElement("input");
                orderInput.type = "hidden";
                orderInput.name = "order_id";
                orderInput.value = this.orderId;
                form.appendChild(orderInput);

                const tokenInput = document.createElement("input");
                tokenInput.type = "hidden";
                tokenInput.name = "Authorization";
                tokenInput.value = `Bearer ${this.$store.state.auth.token}`;
                form.appendChild(tokenInput);

                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error("Redirect payment error:", error);
                this.loading = false;
            }
        },
    },
};
</script>
```

---

## Best Practices & Recommendations

### 1. Choose the Right Method

**Use Direct Redirect (`/payments/redirect`) when:**

-   You want the simplest implementation
-   You don't need custom payment UI
-   You're okay with page redirects
-   Building traditional web applications

**Use API Method (`/payments/initialize`) when:**

-   You need custom payment UI/UX
-   You want to handle payment in modals/popups
-   You're building SPAs with complex state management
-   You need to track payment progress

### 2. Security Considerations

```javascript
// Always validate on backend, but also add frontend validation
const validatePayment = (orderId, amount) => {
    if (!orderId || orderId <= 0) {
        throw new Error("Invalid order ID");
    }
    if (!amount || amount <= 0) {
        throw new Error("Invalid amount");
    }
};

// Use HTTPS in production
const apiUrl =
    process.env.NODE_ENV === "production"
        ? "https://yourdomain.com/api"
        : "http://localhost:8000/api";
```

### 3. Error Handling

```javascript
const handlePaymentError = (error, orderId) => {
    console.error(`Payment error for order ${orderId}:`, error);

    // Log to your error tracking service
    if (window.Sentry) {
        window.Sentry.captureException(error, {
            tags: { payment: true, order_id: orderId },
        });
    }

    // Show user-friendly message
    alert("Payment failed. Please try again or contact support.");
};
```

### 4. Environment Configuration

```javascript
// config.js
export const config = {
    apiUrl: process.env.REACT_APP_API_URL || "http://localhost:8000/api",
    paymentCallbackUrl:
        process.env.REACT_APP_PAYMENT_CALLBACK_URL ||
        `${window.location.origin}/payment/callback`,
    environment: process.env.NODE_ENV || "development",
};
```

This comprehensive guide covers all three payment routes and provides multiple integration approaches for different frontend frameworks and use cases!
