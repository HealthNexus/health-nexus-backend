# Frontend Cart System Documentation

## Overview

The cart system has been completely moved to **frontend-only** using browser storage (localStorage/sessionStorage). The backend no longer manages cart state and only processes orders when submitted with an items array.

## Frontend Cart Management

### Cart Storage Structure
Store cart in `localStorage` or `sessionStorage`:

```javascript
// Example cart structure in browser storage
const cart = {
  items: [
    {
      drug_id: 1,
      quantity: 2,
      drug: {
        id: 1,
        name: "Paracetamol 500mg",
        slug: "paracetamol-500mg", 
        price: 5.50,
        formatted_price: "₵5.50",
        image: "https://example.com/image.jpg",
        stock: 100
      }
    },
    {
      drug_id: 3,
      quantity: 1,
      drug: {
        id: 3,
        name: "Ibuprofen 400mg",
        slug: "ibuprofen-400mg",
        price: 3.25,
        formatted_price: "₵3.25",
        image: "https://example.com/image2.jpg",
        stock: 50
      }
    }
  ],
  totals: {
    subtotal: 14.25,
    delivery_fee: 1.50,
    total: 15.75,
    total_items: 3,
    items_count: 2
  }
};

// Save to localStorage
localStorage.setItem('cart', JSON.stringify(cart));
```

### Frontend Cart Operations

```javascript
class CartManager {
  constructor() {
    this.storageKey = 'healthnexus_cart';
  }

  // Load cart from storage
  getCart() {
    const cartData = localStorage.getItem(this.storageKey);
    return cartData ? JSON.parse(cartData) : { items: [], totals: this.getEmptyTotals() };
  }

  // Save cart to storage
  saveCart(cart) {
    localStorage.setItem(this.storageKey, JSON.stringify(cart));
  }

  // Add item to cart
  addItem(drug, quantity = 1) {
    const cart = this.getCart();
    const existingItemIndex = cart.items.findIndex(item => item.drug_id === drug.id);

    if (existingItemIndex >= 0) {
      // Update quantity if item exists
      cart.items[existingItemIndex].quantity += quantity;
    } else {
      // Add new item
      cart.items.push({
        drug_id: drug.id,
        quantity: quantity,
        drug: drug
      });
    }

    cart.totals = this.calculateTotals(cart.items);
    this.saveCart(cart);
    return cart;
  }

  // Update item quantity
  updateItemQuantity(drugId, quantity) {
    const cart = this.getCart();
    const itemIndex = cart.items.findIndex(item => item.drug_id === drugId);

    if (itemIndex >= 0) {
      if (quantity <= 0) {
        cart.items.splice(itemIndex, 1);
      } else {
        cart.items[itemIndex].quantity = quantity;
      }
    }

    cart.totals = this.calculateTotals(cart.items);
    this.saveCart(cart);
    return cart;
  }

  // Remove item from cart
  removeItem(drugId) {
    const cart = this.getCart();
    cart.items = cart.items.filter(item => item.drug_id !== drugId);
    cart.totals = this.calculateTotals(cart.items);
    this.saveCart(cart);
    return cart;
  }

  // Clear entire cart
  clearCart() {
    const emptyCart = { items: [], totals: this.getEmptyTotals() };
    this.saveCart(emptyCart);
    return emptyCart;
  }

  // Calculate cart totals
  calculateTotals(items, deliveryFee = 0) {
    const subtotal = items.reduce((sum, item) => {
      return sum + (item.drug.price * item.quantity);
    }, 0);

    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);

    return {
      subtotal: subtotal,
      delivery_fee: deliveryFee,
      total: subtotal + deliveryFee,
      total_items: totalItems,
      items_count: items.length,
      formatted_subtotal: `₵${subtotal.toFixed(2)}`,
      formatted_delivery_fee: `₵${deliveryFee.toFixed(2)}`,
      formatted_total: `₵${(subtotal + deliveryFee).toFixed(2)}`
    };
  }

  getEmptyTotals() {
    return {
      subtotal: 0,
      delivery_fee: 0,
      total: 0,
      total_items: 0,
      items_count: 0,
      formatted_subtotal: "₵0.00",
      formatted_delivery_fee: "₵0.00",
      formatted_total: "₵0.00"
    };
  }
}
```

## Backend Order Creation

### API Endpoint: `POST /api/orders`

**Request Body:**
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
  "delivery_fee": 1.50
}
```

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "HN-20250726-ABC123",
      "user_id": 1,
      "subtotal": 14.25,
      "delivery_fee": 1.50,
      "total_amount": 15.75,
      "total_items": 3,
      "status": "placed",
      "payment_status": "pending",
      "phone_number": "+233241234567",
      "delivery_address": "Room 123, Unity Hall, KNUST Campus",
      "delivery_area": "knust_campus",
      "landmark": "Near Unity Hall",
      "delivery_notes": "Call when you arrive",
      "created_at": "2025-07-26T10:30:00Z",
      "items": [
        {
          "id": 1,
          "drug_id": 1,
          "drug_name": "Paracetamol 500mg",
          "quantity": 2,
          "unit_price": 5.50,
          "total_price": 11.00
        },
        {
          "id": 2,
          "drug_id": 3,
          "drug_name": "Ibuprofen 400mg",
          "quantity": 1,
          "unit_price": 3.25,
          "total_price": 3.25
        }
      ]
    }
  }
}
```

**Error Response (400):**
```json
{
  "status": "error",
  "message": "Insufficient stock for 'Paracetamol 500mg'. Only 1 available"
}
```

### Frontend Checkout Implementation

```javascript
class CheckoutManager {
  constructor() {
    this.cartManager = new CartManager();
  }

  async submitOrder(deliveryData) {
    const cart = this.cartManager.getCart();
    
    if (cart.items.length === 0) {
      throw new Error('Cart is empty');
    }

    // Prepare items array for backend
    const items = cart.items.map(item => ({
      drug_id: item.drug_id,
      quantity: item.quantity
    }));

    const orderData = {
      items: items,
      phone_number: deliveryData.phone_number,
      delivery_address: deliveryData.delivery_address,
      delivery_area: deliveryData.delivery_area,
      landmark: deliveryData.landmark,
      delivery_notes: deliveryData.delivery_notes,
      delivery_fee: deliveryData.delivery_fee
    };

    try {
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.getAuthToken()}`
        },
        body: JSON.stringify(orderData)
      });

      const result = await response.json();

      if (response.ok) {
        // Clear cart on successful order
        this.cartManager.clearCart();
        return result.data.order;
      } else {
        throw new Error(result.message || 'Failed to create order');
      }
    } catch (error) {
      console.error('Order creation failed:', error);
      throw error;
    }
  }

  getAuthToken() {
    return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
  }
}
```

## Complete User Flow

1. **Browse Products** → Public access
2. **Add to Cart** → Frontend manages in localStorage
3. **View Cart** → Frontend displays from localStorage  
4. **Update Cart** → Frontend updates localStorage
5. **Checkout** → User must be authenticated
6. **Submit Order** → Frontend sends items array + delivery info to backend
7. **Payment** → Backend creates order and initiates payment
8. **Clear Cart** → Frontend clears localStorage after successful order

## Benefits

✅ **No Server-Side Cart Complexity** - No cart models, services, or routes
✅ **Faster Performance** - Cart operations happen instantly in browser
✅ **Better Offline Support** - Cart persists even when offline
✅ **Simplified Backend** - Backend only processes final orders
✅ **Better User Experience** - Cart state persists across sessions
✅ **Reduced Database Load** - No cart storage in database

## Important Notes

- **Authentication Required**: Users must login before creating orders
- **Stock Validation**: Backend validates stock and availability during order creation
- **Cart Persistence**: Frontend cart persists in browser storage
- **Payment Integration**: Works seamlessly with Paystack payment flow
- **Error Handling**: Backend provides detailed validation errors

This approach provides a clean separation of concerns with frontend managing cart state and backend processing final orders! 🛒✨
