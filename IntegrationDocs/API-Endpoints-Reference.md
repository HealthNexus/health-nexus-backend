# API Endpoints Reference

## Complete Backend API Documentation

This document provides a comprehensive reference for all available API endpoints in the Health Nexus E-Pharmacy backend system.

## Base URL

```
Development: http://localhost:8000/api
Production: https://api.health-nexus.com/api
```

## Authentication

All authenticated endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## Response Format

All API responses follow this standard format:

### Success Response

```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    }
}
```

### Error Response

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Paginated Response

```json
{
    "data": [
        // Array of items
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "to": 15,
        "total": 75
    }
}
```

## Authentication Endpoints

### Register User

```http
POST /register
```

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+234801234567" // Optional
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": {
                "id": 2,
                "name": "Customer",
                "slug": "customer"
            }
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_at": "2024-07-24T12:00:00.000000Z"
    }
}
```

### Login User

```http
POST /login
```

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:** Same as registration response

### Logout User

```http
POST /logout
```

_Requires authentication_

**Response:**

```json
{
    "status": "success",
    "message": "Logged out successfully"
}
```

### Get Current User

```http
GET /user
```

_Requires authentication_

**Response:**

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": {
            "id": 2,
            "name": "Customer",
            "slug": "customer"
        }
    }
}
```

## Drug Management Endpoints

### List Drugs (Public)

```http
GET /drugs
```

**Query Parameters:**

-   `category` (string): Filter by category slug
-   `search` (string): Search in name and description
-   `min_price` (number): Minimum price filter
-   `max_price` (number): Maximum price filter
-   `sort_by` (string): Sort field (name, price, created_at, stock)
-   `sort_direction` (string): Sort direction (asc, desc)
-   `per_page` (number): Items per page (default: 15)
-   `page` (number): Page number

**Example Request:**

```http
GET /drugs?category=analgesics&search=paracetamol&sort_by=price&sort_direction=asc&per_page=20
```

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Paracetamol 500mg",
            "slug": "paracetamol-500mg",
            "description": "Pain relief and fever reduction",
            "price": 250.0,
            "formatted_price": "₵250.00",
            "stock": 100,
            "expiry_date": "2025-12-31",
            "image": "/storage/drugs/paracetamol.jpg",
            "status": "active",
            "is_available": true,
            "is_expired": false,
            "categories": [
                {
                    "id": 1,
                    "name": "Analgesics",
                    "slug": "analgesics"
                }
            ],
            "diseases": [
                {
                    "id": 1,
                    "name": "Headache",
                    "slug": "headache"
                }
            ],
            "created_at": "2024-07-20T10:00:00.000000Z",
            "updated_at": "2024-07-24T10:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 95
    }
}
```

### Search Drugs

```http
GET /drugs/search?q={query}
```

**Query Parameters:**

-   `q` (string, required): Search query

**Response:** Same as list drugs with search ranking

### Get Drug Categories

```http
GET /drugs/categories
```

**Response:**

```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "Analgesics",
            "slug": "analgesics",
            "description": "Pain relief medications",
            "drugs_count": 25
        }
    ]
}
```

### Get Drugs by Category

```http
GET /drugs/category/{categorySlug}
```

**Query Parameters:** Same as list drugs

**Response:** Same as list drugs filtered by category

### Get Single Drug

```http
GET /drugs/{slug}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Paracetamol 500mg",
        "slug": "paracetamol-500mg"
        // ... full drug details with relationships
    }
}
```

## Shopping Cart Endpoints

### Get Cart

```http
GET /cart
```

_Can be used by guests (session-based) or authenticated users_

**Response:**

```json
{
    "status": "success",
    "data": {
        "id": 1,
        "user_id": 1,
        "session_id": null,
        "subtotal": 750.0,
        "tax_amount": 56.25,
        "total_amount": 806.25,
        "formatted_subtotal": "₵750.00",
        "formatted_tax_amount": "₵56.25",
        "formatted_total_amount": "₵806.25",
        "items": [
            {
                "id": 1,
                "cart_id": 1,
                "drug_id": 1,
                "quantity": 3,
                "unit_price": 250.0,
                "total_price": 750.0,
                "drug": {
                    "id": 1,
                    "name": "Paracetamol 500mg",
                    "slug": "paracetamol-500mg",
                    "price": 250.0,
                    "stock": 100,
                    "is_available": true
                },
                "created_at": "2024-07-24T10:00:00.000000Z",
                "updated_at": "2024-07-24T10:00:00.000000Z"
            }
        ],
        "items_count": 1,
        "is_empty": false,
        "created_at": "2024-07-24T10:00:00.000000Z",
        "updated_at": "2024-07-24T10:00:00.000000Z"
    }
}
```

### Add Item to Cart

```http
POST /cart/add
```

**Request Body:**

```json
{
    "drug_id": 1,
    "quantity": 2
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Item added to cart successfully",
    "data": {
        "cart": {
            // Full cart object
        },
        "added_item": {
            // Cart item object
        }
    }
}
```

### Update Cart Item

```http
PUT /cart/item/{itemId}
```

**Request Body:**

```json
{
    "quantity": 5
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Cart item updated successfully",
    "data": {
        "cart": {
            // Updated cart object
        },
        "updated_item": {
            // Updated cart item object
        }
    }
}
```

### Remove Cart Item

```http
DELETE /cart/item/{itemId}
```

**Response:**

```json
{
    "status": "success",
    "message": "Item removed from cart successfully",
    "data": {
        "cart": {
            // Updated cart object
        }
    }
}
```

### Clear Cart

```http
DELETE /cart/clear
```

**Response:**

```json
{
    "status": "success",
    "message": "Cart cleared successfully"
}
```

### Get Cart Totals

```http
GET /cart/totals
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "subtotal": 750.0,
        "tax_amount": 56.25,
        "tax_rate": 7.5,
        "total_amount": 806.25,
        "formatted_subtotal": "₵750.00",
        "formatted_tax_amount": "₵56.25",
        "formatted_total_amount": "₵806.25",
        "items_count": 3
    }
}
```

### Validate Cart

```http
GET /cart/validate
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "is_valid": true,
        "issues": [],
        "warnings": [
            {
                "type": "price_change",
                "message": "Price of Paracetamol has changed",
                "item_id": 1,
                "old_price": 250.0,
                "new_price": 275.0
            }
        ]
    }
}
```

## Order Management Endpoints

### List User Orders

```http
GET /orders
```

_Requires authentication_

**Query Parameters:**

-   `status` (string): Filter by order status (placed, delivering, delivered)
-   `from_date` (date): Filter orders from date (YYYY-MM-DD)
-   `to_date` (date): Filter orders to date (YYYY-MM-DD)
-   `per_page` (number): Items per page (default: 15)

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "order_number": "ORD-20240724-0001",
            "status": "placed",
            "subtotal": 750.0,
            "tax_amount": 56.25,
            "total_amount": 806.25,
            "shipping_address": {
                "line1": "123 Main Street",
                "line2": "Apt 4B",
                "city": "Lagos",
                "state": "Lagos",
                "postal_code": "101001"
            },
            "phone_number": "+234801234567",
            "delivery_notes": "Call when you arrive",
            "payment_status": "paid",
            "placed_at": "2024-07-24T10:00:00.000000Z",
            "delivering_at": null,
            "delivered_at": null,
            "items": [
                {
                    "id": 1,
                    "order_id": 1,
                    "drug_id": 1,
                    "drug_name": "Paracetamol 500mg",
                    "quantity": 3,
                    "unit_price": 250.0,
                    "total_price": 750.0,
                    "current_drug": {
                        // Current drug data if still exists
                    }
                }
            ],
            "created_at": "2024-07-24T10:00:00.000000Z",
            "updated_at": "2024-07-24T10:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 35
    }
}
```

### Create Order

```http
POST /orders
```

_Requires authentication_

**Request Body:**

```json
{
    "shipping_address": {
        "line1": "123 Main Street",
        "line2": "Apt 4B",
        "city": "Lagos",
        "state": "Lagos",
        "postal_code": "101001"
    },
    "phone_number": "+234801234567",
    "delivery_notes": "Call when you arrive"
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Order created successfully",
    "data": {
        "order": {
            // Full order object
        }
    }
}
```

### Get Single Order

```http
GET /orders/{orderId}
```

_Requires authentication_

**Response:**

```json
{
    "status": "success",
    "data": {
        // Full order object with items and relationships
    }
}
```

### Confirm Delivery

```http
POST /orders/{orderId}/confirm-delivery
```

_Requires authentication_

**Response:**

```json
{
    "status": "success",
    "message": "Delivery confirmed successfully",
    "data": {
        "order": {
            // Updated order object with delivered status
        }
    }
}
```

### Get Order Statistics

```http
GET /orders/statistics
```

_Requires authentication_

**Response:**

```json
{
    "status": "success",
    "data": {
        "total_orders": 15,
        "placed_orders": 5,
        "delivering_orders": 7,
        "delivered_orders": 3,
        "total_spent": 12500.0,
        "formatted_total_spent": "₵12,500.00",
        "average_order_value": 833.33,
        "formatted_average_order_value": "₵833.33"
    }
}
```

## Payment Endpoints

### Initialize Payment

```http
POST /payments/initialize
```

_Requires authentication_

**Request Body:**

```json
{
    "order_id": 1
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "payment_id": 1,
        "payment_reference": "PAY_20240724_001",
        "authorization_url": "https://checkout.paystack.com/xyz123",
        "access_code": "xyz123abc",
        "amount": "₵806.25"
    }
}
```

### Calculate Payment Fees

```http
POST /payments/calculate-fees
```

_Requires authentication_

**Request Body:**

```json
{
    "amount": 1000.0
}
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "amount": 1000.0,
        "fee": 15.0,
        "total": 1015.0,
        "formatted_amount": "₵1,000.00",
        "formatted_fee": "₵15.00",
        "formatted_total": "₵1,015.00"
    }
}
```

### PayStack Webhook

```http
POST /payments/webhook/paystack
```

_No authentication required - webhook endpoint_

**Headers:**

```
X-Paystack-Signature: {webhook_signature}
```

## Admin Endpoints

### Admin Inventory Management

#### List Inventory

```http
GET /admin/inventory
```

_Requires admin authentication_

**Query Parameters:**

-   `status` (string): Filter by drug status (active, inactive, out_of_stock)
-   `low_stock` (boolean): Filter low stock items
-   `threshold` (number): Low stock threshold (default: 10)
-   `out_of_stock` (boolean): Filter out of stock items
-   `search` (string): Search in drug names
-   `category` (string): Filter by category slug
-   `sort_by` (string): Sort field (name, stock, price, created_at)
-   `sort_direction` (string): Sort direction (asc, desc)
-   `per_page` (number): Items per page (default: 20)

**Response:**

```json
{
    "data": [
        {
            // Full drug objects with admin-specific data
        }
    ],
    "meta": {
        // Pagination metadata
    }
}
```

#### Get Inventory Statistics

```http
GET /admin/inventory/statistics
```

_Requires admin authentication_

**Response:**

```json
{
    "status": "success",
    "data": {
        "total_drugs": 150,
        "active_drugs": 140,
        "in_stock_drugs": 135,
        "out_of_stock_drugs": 15,
        "low_stock_drugs": 12,
        "total_stock_value": 125000.0,
        "formatted_stock_value": "₵125,000.00",
        "top_selling_drugs": [
            {
                "drug_id": 1,
                "drug_name": "Paracetamol 500mg",
                "total_sold": 150
            }
        ]
    }
}
```

#### Update Drug Stock

```http
PUT /admin/inventory/{drugId}/stock
```

_Requires admin authentication_

**Request Body:**

```json
{
    "quantity": 50,
    "operation": "add", // "set", "add", "subtract"
    "reason": "New stock arrival"
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Stock updated successfully",
    "data": {
        "drug": {
            // Updated drug object
        },
        "old_quantity": 25,
        "new_quantity": 75
    }
}
```

#### Get Low Stock Alerts

```http
GET /admin/inventory/low-stock-alerts
```

_Requires admin authentication_

**Query Parameters:**

-   `threshold` (number): Low stock threshold (default: 10)

**Response:**

```json
{
    "status": "success",
    "data": {
        "threshold": 10,
        "count": 12,
        "drugs": [
            {
                // Drug objects with low stock
            }
        ]
    }
}
```

#### Bulk Update Stock

```http
POST /admin/inventory/bulk-update-stock
```

_Requires admin authentication_

**Request Body:**

```json
{
    "updates": [
        {
            "drug_id": 1,
            "quantity": 50,
            "operation": "add"
        },
        {
            "drug_id": 2,
            "quantity": 25,
            "operation": "set"
        }
    ]
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Bulk stock update completed",
    "data": {
        "successful_updates": 2,
        "failed_updates": 0,
        "results": [
            {
                "drug_id": 1,
                "drug_name": "Paracetamol 500mg",
                "old_quantity": 25,
                "new_quantity": 75,
                "status": "success"
            }
        ],
        "errors": []
    }
}
```

### Admin Order Management

#### List All Orders

```http
GET /admin/orders
```

_Requires admin authentication_

**Query Parameters:**

-   `status` (string): Filter by order status
-   `payment_status` (string): Filter by payment status
-   `from_date` (date): Filter orders from date
-   `to_date` (date): Filter orders to date
-   `search` (string): Search in order number or customer name
-   `per_page` (number): Items per page

**Response:**

```json
{
    "data": [
        {
            // Full order objects with customer information
        }
    ],
    "meta": {
        // Pagination metadata
    }
}
```

#### Get Order Analytics

```http
GET /admin/orders/analytics
```

_Requires admin authentication_

**Response:**

```json
{
    "status": "success",
    "data": {
        "total_orders": 250,
        "pending_orders": 15,
        "delivering_orders": 25,
        "delivered_orders": 210,
        "total_revenue": 125000.0,
        "average_order_value": 500.0,
        "orders_by_status": {
            "placed": 15,
            "delivering": 25,
            "delivered": 210
        },
        "monthly_revenue": [
            {
                "month": "January",
                "revenue": 15000.0
            }
        ]
    }
}
```

#### Update Order Status

```http
PUT /admin/orders/{orderId}/status
```

_Requires admin authentication_

**Request Body:**

```json
{
    "status": "delivering", // "placed", "delivering", "delivered"
    "notes": "Package dispatched via courier"
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Order status updated successfully",
    "data": {
        "order": {
            // Updated order object
        }
    }
}
```

#### Mark Order as Delivering

```http
POST /admin/orders/{orderId}/mark-delivering
```

_Requires admin authentication_

**Request Body:**

```json
{
    "tracking_number": "TRK123456789",
    "courier": "DHL",
    "estimated_delivery": "2024-07-26"
}
```

**Response:**

```json
{
    "status": "success",
    "message": "Order marked as delivering",
    "data": {
        "order": {
            // Updated order object
        }
    }
}
```

## Error Codes and Messages

### Common HTTP Status Codes

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request (Validation errors)
-   `401` - Unauthorized (Authentication required)
-   `403` - Forbidden (Insufficient permissions)
-   `404` - Not Found
-   `422` - Unprocessable Entity (Validation failed)
-   `500` - Internal Server Error

### Common Error Messages

```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

```json
{
    "status": "error",
    "message": "Unauthenticated",
    "code": "UNAUTHENTICATED"
}
```

```json
{
    "status": "error",
    "message": "Insufficient stock available",
    "code": "INSUFFICIENT_STOCK",
    "data": {
        "requested": 10,
        "available": 5
    }
}
```

## Rate Limiting

-   **General API**: 100 requests per minute per IP
-   **Authentication**: 5 login attempts per minute per IP
-   **Admin API**: 200 requests per minute per user

## Webhook Security

PayStack webhooks are verified using the signature in the `X-Paystack-Signature` header. The backend automatically validates webhook authenticity using the configured webhook secret.

## Testing with Postman/Insomnia

Import this collection structure for testing:

```json
{
    "info": {
        "name": "Health Nexus E-Pharmacy API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "auth": {
        "type": "bearer",
        "bearer": [
            {
                "key": "token",
                "value": "{{auth_token}}",
                "type": "string"
            }
        ]
    },
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000/api"
        },
        {
            "key": "auth_token",
            "value": ""
        }
    ]
}
```

This completes the comprehensive API reference for the Health Nexus E-Pharmacy backend system.
