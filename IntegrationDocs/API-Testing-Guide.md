# API Testing & Validation Guide

## Health Nexus E-Pharmacy Backend

### Table of Contents

1. [Overview](#overview)
2. [API Testing Tools](#api-testing-tools)
3. [Authentication Testing](#authentication-testing)
4. [Product Catalog Testing](#product-catalog-testing)
5. [Cart System Testing](#cart-system-testing)
6. [Order Management Testing](#order-management-testing)
7. [Payment System Testing](#payment-system-testing)
8. [Admin Dashboard Testing](#admin-dashboard-testing)
9. [Error Scenarios Testing](#error-scenarios-testing)
10. [Performance Testing](#performance-testing)
11. [Security Testing](#security-testing)

---

## Overview

This guide provides comprehensive API testing procedures for the Health Nexus E-Pharmacy backend. Use this guide to validate API functionality before frontend integration.

### Base URL

```
Development: http://localhost:8000/api
Production: https://api.health-nexus.com/api
```

### Required Tools

-   **Postman** (recommended) or **Insomnia**
-   **cURL** for command-line testing
-   **Browser DevTools** for debugging

---

## API Testing Tools

### Postman Collection Setup

#### 1. Environment Variables

Create a Postman environment with these variables:

```json
{
    "base_url": "http://localhost:8000/api",
    "auth_token": "",
    "user_id": "",
    "cart_session": "",
    "order_id": "",
    "payment_reference": ""
}
```

#### 2. Pre-request Script for Authentication

Add this script to authenticated requests:

```javascript
// Pre-request Script
pm.test("Check if token exists", function () {
    const token = pm.environment.get("auth_token");
    if (!token) {
        console.log("No auth token found. Please login first.");
    }
});
```

#### 3. Test Script Template

```javascript
// Test Script Template
pm.test("Status code is successful", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});

pm.test("Response has required structure", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property("status");
    pm.expect(jsonData).to.have.property("data");
});

pm.test("Response time is acceptable", function () {
    pm.expect(pm.response.responseTime).to.be.below(2000);
});
```

---

## Authentication Testing

### 1. User Registration

#### Request

```http
POST {{base_url}}/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Expected Response

```json
{
    "status": "success",
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Test User",
            "email": "test@example.com",
            "role": {
                "id": 2,
                "name": "Customer",
                "slug": "customer"
            }
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

#### Test Script

```javascript
pm.test("Registration successful", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("user");
    pm.expect(jsonData.data).to.have.property("token");

    // Store token for subsequent requests
    pm.environment.set("auth_token", jsonData.data.token);
    pm.environment.set("user_id", jsonData.data.user.id);
});
```

### 2. User Login

#### Request

```http
POST {{base_url}}/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

#### Test Script

```javascript
pm.test("Login successful", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");

    // Store authentication data
    pm.environment.set("auth_token", jsonData.data.token);
    pm.environment.set("user_id", jsonData.data.user.id);
});
```

### 3. Get Current User

#### Request

```http
GET {{base_url}}/user
Authorization: Bearer {{auth_token}}
```

#### Test Script

```javascript
pm.test("User data retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.user).to.have.property("id");
    pm.expect(jsonData.user).to.have.property("email");
});
```

---

## Product Catalog Testing

### 1. Get All Drugs

#### Request

```http
GET {{base_url}}/drugs?page=1&per_page=20
```

#### Test Script

```javascript
pm.test("Drugs list retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("data");
    pm.expect(jsonData.data.data).to.be.an("array");

    if (jsonData.data.data.length > 0) {
        const drug = jsonData.data.data[0];
        pm.expect(drug).to.have.property("id");
        pm.expect(drug).to.have.property("name");
        pm.expect(drug).to.have.property("price");
        pm.expect(drug).to.have.property("stock");
    }
});
```

### 2. Search Drugs

#### Request

```http
GET {{base_url}}/drugs/search?query=paracetamol&page=1
```

#### Test Script

```javascript
pm.test("Search results returned", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data.data).to.be.an("array");
});
```

### 3. Get Drug Categories

#### Request

```http
GET {{base_url}}/drugs/categories
```

#### Test Script

```javascript
pm.test("Categories retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.be.an("array");

    if (jsonData.data.length > 0) {
        const category = jsonData.data[0];
        pm.expect(category).to.have.property("id");
        pm.expect(category).to.have.property("name");
        pm.expect(category).to.have.property("slug");
    }
});
```

### 4. Get Drug by Slug

#### Request

```http
GET {{base_url}}/drugs/paracetamol-500mg
```

#### Test Script

```javascript
pm.test("Drug details retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("id");
    pm.expect(jsonData.data).to.have.property("name");
    pm.expect(jsonData.data).to.have.property("description");
    pm.expect(jsonData.data).to.have.property("categories");
});
```

---

## Cart System Testing

### 1. Get Cart (Guest User)

#### Request

```http
GET {{base_url}}/cart
```

#### Test Script

```javascript
pm.test("Cart retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("items");
    pm.expect(jsonData.data).to.have.property("totals");

    // Store session for guest cart
    const sessionId = pm.response.headers.get("X-Cart-Session");
    if (sessionId) {
        pm.environment.set("cart_session", sessionId);
    }
});
```

### 2. Add Item to Cart

#### Request

```http
POST {{base_url}}/cart/add
Content-Type: application/json

{
  "drug_id": 1,
  "quantity": 2
}
```

#### Test Script

```javascript
pm.test("Item added to cart", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("cart_item");
});
```

### 3. Update Cart Item

#### Request

```http
PUT {{base_url}}/cart/item/1
Content-Type: application/json

{
  "quantity": 3
}
```

#### Test Script

```javascript
pm.test("Cart item updated", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data.cart_item.quantity).to.eql(3);
});
```

### 4. Remove Cart Item

#### Request

```http
DELETE {{base_url}}/cart/item/1
```

#### Test Script

```javascript
pm.test("Cart item removed", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
});
```

### 5. Get Cart Totals

#### Request

```http
GET {{base_url}}/cart/totals
```

#### Test Script

```javascript
pm.test("Cart totals retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("subtotal");
    pm.expect(jsonData.data).to.have.property("tax");
    pm.expect(jsonData.data).to.have.property("total");
    pm.expect(jsonData.data).to.have.property("items_count");
});
```

---

## Order Management Testing

### 1. Create Order (Authenticated User)

#### Request

```http
POST {{base_url}}/orders
Authorization: Bearer {{auth_token}}
Content-Type: application/json

{
  "delivery_address": "123 Main Street, Lagos, Nigeria",
  "phone": "+2347012345678",
  "notes": "Please call before delivery"
}
```

#### Test Script

```javascript
pm.test("Order created successfully", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("order");
    pm.expect(jsonData.data.order).to.have.property("order_number");
    pm.expect(jsonData.data.order.status).to.eql("placed");

    // Store order ID for subsequent tests
    pm.environment.set("order_id", jsonData.data.order.id);
});
```

### 2. Get Order History

#### Request

```http
GET {{base_url}}/orders?page=1
Authorization: Bearer {{auth_token}}
```

#### Test Script

```javascript
pm.test("Order history retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("data");
    pm.expect(jsonData.data.data).to.be.an("array");
});
```

### 3. Get Order Details

#### Request

```http
GET {{base_url}}/orders/{{order_id}}
Authorization: Bearer {{auth_token}}
```

#### Test Script

```javascript
pm.test("Order details retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("id");
    pm.expect(jsonData.data).to.have.property("order_number");
    pm.expect(jsonData.data).to.have.property("items");
    pm.expect(jsonData.data).to.have.property("payment");
});
```

### 4. Confirm Delivery

#### Request

```http
POST {{base_url}}/orders/{{order_id}}/confirm-delivery
Authorization: Bearer {{auth_token}}
```

#### Test Script

```javascript
pm.test("Delivery confirmed", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.message).to.include("confirmed");
});
```

---

## Payment System Testing

### 1. Initialize Payment

#### Request

```http
POST {{base_url}}/payment/initialize
Authorization: Bearer {{auth_token}}
Content-Type: application/json

{
  "order_id": "{{order_id}}"
}
```

#### Test Script

```javascript
pm.test("Payment initialized", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("authorization_url");
    pm.expect(jsonData.data).to.have.property("reference");
    pm.expect(jsonData.data).to.have.property("access_code");

    // Store payment reference
    pm.environment.set("payment_reference", jsonData.data.reference);
});
```

### 2. Verify Payment

#### Request

```http
GET {{base_url}}/payment/verify/{{payment_reference}}
Authorization: Bearer {{auth_token}}
```

#### Test Script

```javascript
pm.test("Payment verification response", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("status");
    pm.expect(jsonData.data).to.have.property("reference");
});
```

### 3. Test Webhook (Simulated)

#### Request

```http
POST {{base_url}}/payment/webhook
Content-Type: application/json
X-Paystack-Signature: t=1234567890,v1=mock_signature

{
  "event": "charge.success",
  "data": {
    "id": 12345,
    "reference": "{{payment_reference}}",
    "amount": 10000,
    "currency": "NGN",
    "status": "success",
    "customer": {
      "email": "test@example.com"
    }
  }
}
```

---

## Admin Dashboard Testing

### 1. Admin Login

#### Request

```http
POST {{base_url}}/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "admin123"
}
```

#### Test Script

```javascript
pm.test("Admin login successful", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data.user.role.slug).to.eql("admin");
    pm.environment.set("admin_token", jsonData.data.token);
});
```

### 2. Get Inventory Overview

#### Request

```http
GET {{base_url}}/admin/inventory
Authorization: Bearer {{admin_token}}
```

#### Test Script

```javascript
pm.test("Inventory overview retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("data");
    pm.expect(jsonData.data.data).to.be.an("array");
});
```

### 3. Get Inventory Statistics

#### Request

```http
GET {{base_url}}/admin/inventory/statistics
Authorization: Bearer {{admin_token}}
```

#### Test Script

```javascript
pm.test("Inventory statistics retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("total_drugs");
    pm.expect(jsonData.data).to.have.property("active_drugs");
    pm.expect(jsonData.data).to.have.property("out_of_stock_drugs");
    pm.expect(jsonData.data).to.have.property("total_stock_value");
});
```

### 4. Update Drug Stock

#### Request

```http
PUT {{base_url}}/admin/inventory/1/stock
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "quantity": 50,
  "operation": "add",
  "reason": "New stock delivery"
}
```

#### Test Script

```javascript
pm.test("Stock updated successfully", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data).to.have.property("old_quantity");
    pm.expect(jsonData.data).to.have.property("new_quantity");
});
```

### 5. Get Admin Orders

#### Request

```http
GET {{base_url}}/admin/orders
Authorization: Bearer {{admin_token}}
```

#### Test Script

```javascript
pm.test("Admin orders retrieved", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property("data");
    pm.expect(jsonData.data.data).to.be.an("array");
});
```

### 6. Update Order Status

#### Request

```http
PUT {{base_url}}/admin/orders/{{order_id}}/status
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "status": "delivering"
}
```

#### Test Script

```javascript
pm.test("Order status updated", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.status).to.eql("success");
    pm.expect(jsonData.data.order.status).to.eql("delivering");
});
```

---

## Error Scenarios Testing

### 1. Unauthorized Access

#### Request

```http
GET {{base_url}}/orders
```

#### Expected Response

```json
{
    "message": "Unauthenticated."
}
```

### 2. Invalid Data

#### Request

```http
POST {{base_url}}/cart/add
Content-Type: application/json

{
  "drug_id": "invalid",
  "quantity": -1
}
```

#### Expected Response

```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "drug_id": ["The drug id must be an integer."],
        "quantity": ["The quantity must be at least 1."]
    }
}
```

### 3. Resource Not Found

#### Request

```http
GET {{base_url}}/drugs/non-existent-drug
```

#### Expected Response

```json
{
    "status": "error",
    "message": "Drug not found"
}
```

### 4. Admin Access Required

#### Request

```http
GET {{base_url}}/admin/inventory
Authorization: Bearer {{auth_token}}
```

#### Expected Response (for non-admin user)

```json
{
    "status": "error",
    "message": "Unauthorized - Admin access required"
}
```

---

## Performance Testing

### 1. Load Testing with cURL

#### Basic Load Test

```bash
# Test 100 concurrent requests to drugs endpoint
for i in {1..100}; do
  curl -s -w "%{time_total}\n" -o /dev/null "http://localhost:8000/api/drugs" &
done
wait
```

#### Response Time Test

```bash
# Test average response time
time curl -s "http://localhost:8000/api/drugs" > /dev/null
```

### 2. Database Query Optimization

#### Check Query Performance

```sql
-- Enable query logging
SET general_log = 'ON';
SET general_log_file = '/tmp/mysql-query.log';

-- Run API requests, then check slow queries
SHOW PROCESSLIST;
```

---

## Security Testing

### 1. SQL Injection Testing

#### Test Input Sanitization

```http
GET {{base_url}}/drugs/search?query='; DROP TABLE drugs; --
```

Should return safe results, not execute the SQL injection.

### 2. XSS Prevention

#### Test Script Injection

```http
POST {{base_url}}/cart/add
Content-Type: application/json

{
  "drug_id": "<script>alert('xss')</script>",
  "quantity": 1
}
```

Should return validation error, not execute script.

### 3. Authentication Bypass

#### Test Token Manipulation

```http
GET {{base_url}}/orders
Authorization: Bearer invalid_token
```

Should return authentication error.

### 4. Rate Limiting

#### Test Request Limits

```bash
# Send rapid requests to test rate limiting
for i in {1..100}; do
  curl -s "http://localhost:8000/api/drugs" &
done
```

Should eventually return rate limit error.

---

## Test Automation

### Postman Collection Runner

#### Setup Collection

1. Import all test requests into a Postman collection
2. Set up environment variables
3. Add pre-request scripts for authentication
4. Add test scripts for validation

#### Run Collection

```bash
# Install Newman (Postman CLI)
npm install -g newman

# Run collection
newman run Health-Nexus-API-Tests.postman_collection.json \
  -e Health-Nexus-Environment.postman_environment.json \
  --reporters cli,json,html \
  --reporter-html-export report.html
```

### Continuous Integration

#### GitHub Actions Example

```yaml
# .github/workflows/api-tests.yml
name: API Tests

on: [push, pull_request]

jobs:
    api-tests:
        runs-on: ubuntu-latest

        steps:
            - uses: actions/checkout@v2

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.2"

            - name: Install dependencies
              run: composer install

            - name: Setup database
              run: |
                  php artisan migrate
                  php artisan db:seed

            - name: Start server
              run: php artisan serve &

            - name: Install Newman
              run: npm install -g newman

            - name: Run API tests
              run: newman run tests/postman/collection.json
```

---

## Testing Checklist

### Pre-Integration Testing

-   [ ] All authentication endpoints work correctly
-   [ ] Product catalog returns proper data structure
-   [ ] Cart operations function for both guest and authenticated users
-   [ ] Order creation and management work properly
-   [ ] Payment initialization returns correct PayStack data
-   [ ] Admin endpoints require proper authentication
-   [ ] Error responses follow consistent format

### Integration Testing

-   [ ] Frontend can authenticate successfully
-   [ ] Product data displays correctly in frontend
-   [ ] Cart synchronization works on user login
-   [ ] Order creation triggers proper inventory deduction
-   [ ] Payment flow completes successfully
-   [ ] Admin dashboard displays real-time data

### Production Readiness

-   [ ] All API endpoints use HTTPS
-   [ ] Rate limiting is configured
-   [ ] Error logging is enabled
-   [ ] Performance meets requirements
-   [ ] Security headers are set
-   [ ] CORS is properly configured

---

## Conclusion

This testing guide ensures comprehensive validation of the Health Nexus E-Pharmacy backend API. Use these tests to verify functionality before frontend integration and to maintain API quality throughout development.

**Remember to:**

1. Test all endpoints before frontend integration begins
2. Validate error scenarios and edge cases
3. Monitor performance under load
4. Verify security measures are working
5. Update tests as new features are added

The success of frontend integration depends on a thoroughly tested and reliable backend API.
