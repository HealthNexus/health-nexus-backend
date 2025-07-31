# Exact API Response Structure for Order Creation

## POST `/api/orders` - Create Order

### Request Payload

```json
{
    "items": [
        {
            "drug_id": 1,
            "quantity": 2
        },
        {
            "drug_id": 3,
            "quantity": 1
        }
    ],
    "phone_number": "+233241234567",
    "delivery_address": "Room 123, Unity Hall, KNUST Campus",
    "delivery_area": "knust_campus",
    "landmark": "Near Unity Hall",
    "delivery_notes": "Call when you arrive",
    "delivery_fee": 1.5
}
```

### Success Response (201 Created)

```json
{
    "status": "success",
    "message": "Order created successfully",
    "data": {
        "order": {
            "id": 1,
            "order_number": "HN-20250726-ABC123",
            "status": {
                "value": "placed",
                "label": "Placed",
                "description": "Order has been placed and is awaiting processing",
                "color": "#007bff"
            },
            "items": [
                {
                    "id": 1,
                    "drug_id": 1,
                    "drug_name": "Paracetamol 500mg",
                    "drug_slug": "paracetamol-500mg",
                    "drug_description": "Pain relief medication",
                    "quantity": 2,
                    "unit_price": 5.5,
                    "total_price": 11.0,
                    "formatted_unit_price": "₵5.50",
                    "formatted_total_price": "₵11.00",
                    "drug": {
                        "id": 1,
                        "name": "Paracetamol 500mg",
                        "slug": "paracetamol-500mg",
                        "price": 5.5,
                        "image": "https://example.com/image.jpg"
                    }
                },
                {
                    "id": 2,
                    "drug_id": 3,
                    "drug_name": "Ibuprofen 400mg",
                    "drug_slug": "ibuprofen-400mg",
                    "drug_description": "Anti-inflammatory medication",
                    "quantity": 1,
                    "unit_price": 3.25,
                    "total_price": 3.25,
                    "formatted_unit_price": "₵3.25",
                    "formatted_total_price": "₵3.25",
                    "drug": {
                        "id": 3,
                        "name": "Ibuprofen 400mg",
                        "slug": "ibuprofen-400mg",
                        "price": 3.25,
                        "image": "https://example.com/image2.jpg"
                    }
                }
            ],
            "totals": {
                "subtotal": 14.25,
                "tax_amount": 0.0,
                "total_amount": 15.75,
                "total_items": 3,
                "formatted_subtotal": "₵14.25",
                "formatted_tax_amount": "₵0.00",
                "formatted_total_amount": "₵15.75"
            },
            "delivery": {
                "area": "knust_campus",
                "address": "Room 123, Unity Hall, KNUST Campus",
                "landmark": "Near Unity Hall",
                "fee": 1.5,
                "formatted_fee": "₵1.50",
                "notes": "Call when you arrive"
            },
            "phone_number": "+233241234567",
            "payment": {
                "status": "pending",
                "method": null,
                "reference": null
            },
            "dates": {
                "placed_at": "2025-07-26T12:10:12.000000Z",
                "delivered_at": null,
                "status_updated_at": null,
                "days_old": 0
            },
            "status_updated_by": null,
            "can_be_delivered": false,
            "can_be_shipped": true,
            "is_delivered": false,
            "created_at": "2025-07-26T12:10:12.000000Z",
            "updated_at": "2025-07-26T12:10:12.000000Z"
        }
    }
}
```

### Error Response (400 Bad Request)

```json
{
    "status": "error",
    "message": "Insufficient stock for 'Paracetamol 500mg'. Only 1 available"
}
```

### Validation Error Response (422 Unprocessable Entity)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "items": ["The items field is required."],
        "items.0.drug_id": ["The items.0.drug_id field is required."],
        "items.0.quantity": ["The items.0.quantity must be at least 1."],
        "phone_number": ["The phone number field is required."],
        "delivery_address": ["The delivery address field is required."]
    }
}
```

### Authentication Error Response (401 Unauthorized)

```json
{
    "message": "Unauthenticated."
}
```

## Frontend Access Pattern

### Accessing Order Number

```javascript
// After successful order creation
const response = await fetch("/api/orders", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify(orderData),
});

const result = await response.json();

if (response.ok) {
    // Access the order number
    const orderNumber = result.data.order.order_number; // "HN-20250726-ABC123"
    const orderId = result.data.order.id; // 1
    const totalAmount = result.data.order.totals.total_amount; // 15.75
    const formattedTotal = result.data.order.totals.formatted_total_amount; // "₵15.75"

    console.log("Order created:", orderNumber);
} else {
    // Handle error
    console.error("Error:", result.message);
}
```

### Key Response Structure Points

1. **Main Response Wrapper:**

    - `status`: "success" or "error"
    - `message`: Human-readable message
    - `data`: Contains the actual order data

2. **Order Object Location:**

    - The order object is at `result.data.order`
    - NOT at `result.order` (this would be undefined)

3. **Order Properties:**

    - `order_number`: Unique order identifier (e.g., "HN-20250726-ABC123")
    - `id`: Database ID (integer)
    - `totals`: Object with subtotal, tax, total amounts
    - `delivery`: Object with delivery information (NOT `shipping_address`)
    - `items`: Array of order items with drug details
    - `status`: Object with status information
    - `payment`: Object with payment information

4. **Delivery Information:**
    - Changed from `shipping_address` to `delivery` object
    - Contains: `area`, `address`, `landmark`, `fee`, `notes`

## Backend Response Code

The response is generated in `OrderController::store()`:

```php
return response()->json([
    'status' => 'success',
    'message' => 'Order created successfully',
    'data' => [
        'order' => new OrderResource($order)
    ]
], 201);
```

The order object is wrapped in an `OrderResource` that formats all the fields properly.
