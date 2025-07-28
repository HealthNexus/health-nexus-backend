# Authentication Required Cart System

## Overview

The cart system has been updated to **require user authentication**. No guest/session-based carts are supported anymore. Users must be logged in to add items to cart and create orders.

## Changes Made

### 1. Cart Service (CartService.php)
- ✅ Removed `getSessionCart()` method
- ✅ Updated `getOrCreateCart()` to only accept `User $user` parameter
- ✅ Updated `addToCart()` to require `User $user` parameter
- ✅ Replaced `syncCartOnLogin()` with `getUserCart()` method

### 2. Cart Model (Cart.php)
- ✅ Removed `session_id` from fillable fields
- ✅ Cart now requires `user_id` (authentication required)

### 3. Cart Controller (CartController.php)
- ✅ All methods updated to use authenticated user only
- ✅ Removed guest cart support from all endpoints

### 4. API Routes (routes/api.php)
- ✅ Moved cart routes from public to protected section
- ✅ Cart routes now require `auth:sanctum` middleware

### 5. Database Migration
- ✅ Created migration: `2025_07_26_081701_update_carts_table_remove_session_id.php`
- ✅ Removes `session_id` column and index
- ✅ Makes `user_id` required (not nullable)

## API Endpoints

All cart endpoints now require authentication:

```
POST   /api/login                    # User must login first
GET    /api/cart                     # Get authenticated user's cart
POST   /api/cart/add                 # Add item to cart
PUT    /api/cart/item/{cartItem}     # Update cart item
DELETE /api/cart/item/{cartItem}     # Remove cart item
DELETE /api/cart/clear               # Clear entire cart
GET    /api/cart/totals              # Get cart totals
GET    /api/cart/validate            # Validate cart items
```

## Frontend Impact

### Before (Guest Cart Support)
```javascript
// Could add to cart without login
fetch('/api/cart/add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ drug_id: 1, quantity: 2 })
});
```

### After (Authentication Required)
```javascript
// Must include auth token
fetch('/api/cart/add', {
    method: 'POST',
    headers: { 
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
    },
    body: JSON.stringify({ drug_id: 1, quantity: 2 })
});
```

## User Flow

1. **Browse Products** → Public (no auth required)
2. **Add to Cart** → **MUST LOGIN** ✅
3. **View Cart** → **MUST LOGIN** ✅
4. **Checkout** → **MUST LOGIN** ✅
5. **Create Order** → **MUST LOGIN** ✅

## Database Schema

### Carts Table (Updated)
```sql
CREATE TABLE carts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,              -- Required (no more nullable)
    -- session_id removed                 -- No more guest support
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    total_items INT DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_carts_user_id (user_id)
);
```

## Benefits

1. **Simplified Logic**: No more guest/user cart merging complexity
2. **Better Security**: All cart operations require authentication
3. **Persistent Carts**: User carts persist across devices and sessions
4. **Order Tracking**: Direct relationship between users and their orders
5. **Cleaner Code**: Removed session-based cart complexity

## Running the Migration

To apply the database changes:

```bash
php artisan migrate
```

This will:
- Remove `session_id` column from carts table
- Make `user_id` required (not nullable)
- Remove session_id index

## Error Handling

If frontend tries to access cart without authentication:

```json
{
    "message": "Unauthenticated.",
    "status": 401
}
```

## Validation Rules

Cart operations now validate:
- ✅ User is authenticated
- ✅ Drug exists and is available
- ✅ Sufficient stock
- ✅ Valid quantity (1-100)
- ✅ Cart item belongs to authenticated user

This change ensures better security and a more streamlined user experience for the e-pharmacy platform.
