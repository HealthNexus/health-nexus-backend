# Backend E-Pharmacy Implementation Plan

## Overview

This execution plan outlines the backend development for implementing e-pharmacy functionality in the Health Nexus Laravel API. The plan extends the existing drug management system to include e-commerce capabilities for online pharmacy operations.

## Current State Analysis

-   ✅ Basic Drug model exists with name and slug
-   ✅ DrugCategory model and relationships
-   ✅ Drug-Disease relationship mapping
-   ✅ Basic drug listing API endpoint (admin/doctor only)
-   ❌ No e-commerce functionality (pricing, inventory, orders)
-   ❌ No customer-facing drug endpoints
-   ❌ No shopping cart system
-   ❌ No order management
-   ❌ No payment processing

## Implementation Phases

### Phase 1: Enhanced Drug Management

**Objective**: Extend the drug model to support e-commerce functionality

#### Tasks:

1. **Database Schema Enhancement**

    - Migrate drugs table to include e-commerce fields
    - Add inventory management capabilities
    - Create drug variants (dosage, packaging)

2. **Model Updates**

    - Enhance Drug model with e-commerce attributes
    - Add inventory tracking methods
    - Implement price calculation logic

3. **API Endpoints**
    - Public drug listing with filtering/search
    - Drug details with availability
    - Category-based drug browsing

#### Files to Create/Modify:

-   `database/migrations/xxxx_enhance_drugs_table_for_ecommerce.php`
-   `app/Models/Drug.php` (enhance)
-   `app/Http/Controllers/DrugController.php` (enhance)
-   `app/Http/Resources/DrugResource.php` (new)
-   `routes/api.php` (add new endpoints)

### Phase 2: Shopping Cart System

**Objective**: Implement shopping cart functionality for users

#### Tasks:

1. **Cart Model & Migration**

    - Create cart system for guest and authenticated users
    - Session-based cart for guests
    - Database-backed cart for authenticated users

2. **Cart Operations**

    - Add items to cart
    - Update quantities
    - Remove items
    - Cart synchronization (guest to user)

3. **API Endpoints**
    - Cart CRUD operations
    - Cart item management
    - Cart totals calculation

#### Files to Create:

-   `database/migrations/xxxx_create_carts_table.php`
-   `database/migrations/xxxx_create_cart_items_table.php`
-   `app/Models/Cart.php`
-   `app/Models/CartItem.php`
-   `app/Http/Controllers/CartController.php`
-   `app/Http/Services/CartService.php`
-   `app/Http/Resources/CartResource.php`

### Phase 3: Order Management System

**Objective**: Handle order processing and management

#### Tasks:

1. **Order Models**

    - Create order and order items tables
    - Order status tracking
    - Order history management

2. **Order Processing**

    - Convert cart to order
    - Order validation
    - Inventory deduction
    - Order status updates

3. **API Endpoints**
    - Order creation
    - Order listing (user & admin)
    - Order details
    - Order status updates

#### Files to Create:

-   `database/migrations/xxxx_create_orders_table.php`
-   `database/migrations/xxxx_create_order_items_table.php`
-   `app/Models/Order.php`
-   `app/Models/OrderItem.php`
-   `app/Http/Controllers/OrderController.php`
-   `app/Http/Services/OrderService.php`
-   `app/Http/Resources/OrderResource.php`
-   `app/Enums/OrderStatus.php`

### Phase 4: Payment Integration

**Objective**: Integrate payment processing capabilities

#### Tasks:

1. **Payment Models**

    - Payment tracking table
    - Payment method management
    - Transaction logging

2. **Payment Service**

    - Payment gateway integration with PayStack (check https://github.com/unicodeveloper/laravel-paystack on how to use Paystack)
    - Payment verification
    - Refund processing

3. **API Endpoints**
    - Payment processing
    - Payment verification
    - Payment history

#### Files to Create:

-   `database/migrations/xxxx_create_payments_table.php`
-   `app/Models/Payment.php`
-   `app/Http/Controllers/PaymentController.php`
-   `app/Http/Services/PaymentService.php`
-   `app/Http/Resources/PaymentResource.php`
-   `config/payment.php`

### Phase 5: Prescription Management

**Objective**: Handle prescription-based drug ordering

#### Tasks:

1. **Prescription Models**

    - Prescription upload and verification
    - Doctor verification system
    - Prescription-drug linking

2. **Prescription Validation**

    - Image/PDF upload handling
    - Prescription verification workflow
    - Admin approval system

3. **API Endpoints**
    - Prescription upload
    - Prescription status tracking
    - Prescription-based ordering

#### Files to Create:

-   `database/migrations/xxxx_create_prescriptions_table.php`
-   `app/Models/Prescription.php`
-   `app/Http/Controllers/PrescriptionController.php`
-   `app/Http/Services/PrescriptionService.php`
-   `app/Http/Resources/PrescriptionResource.php`

### Phase 6: Admin Dashboard APIs

**Objective**: Provide comprehensive admin management capabilities

#### Tasks:

1. **Inventory Management**

    - Stock level tracking
    - Low stock alerts
    - Inventory reporting

2. **Order Management**

    - Order processing workflow
    - Delivery tracking
    - Return/refund management

3. **Analytics & Reporting**
    - Sales analytics
    - Popular drugs tracking
    - Revenue reporting

#### Files to Create:

-   `app/Http/Controllers/Admin/InventoryController.php`
-   `app/Http/Controllers/Admin/OrderManagementController.php`
-   `app/Http/Controllers/Admin/AnalyticsController.php`
-   `app/Http/Services/AnalyticsService.php`

## Technical Specifications

### Authentication & Authorization

-   Extend existing Sanctum authentication
-   Role-based access control (customer, pharmacist, admin)
-   API rate limiting for public endpoints

### Database Design

```sql
-- Enhanced drugs table
drugs: id, name, slug, description, price, stock_quantity, prescription_required,
       manufacturer, expiry_date, dosage, package_size, image_url, status,
       created_at, updated_at

-- Cart system
carts: id, user_id, session_id, created_at, updated_at
cart_items: id, cart_id, drug_id, quantity, price, created_at, updated_at

-- Order system
orders: id, user_id, order_number, status, total_amount, shipping_address,
        payment_status, prescription_id, created_at, updated_at
order_items: id, order_id, drug_id, quantity, price, subtotal

-- Payment system
payments: id, order_id, payment_method, transaction_id, amount, status,
          gateway_response, created_at, updated_at

-- Prescription system
prescriptions: id, user_id, image_path, status, verified_by, verified_at,
               notes, created_at, updated_at
```

### API Response Format

```json
{
    "status": "success|error",
    "message": "Response message",
    "data": {},
    "meta": {
        "pagination": {},
        "total": 0
    }
}
```

### Error Handling

-   Standardized error responses
-   Validation error formatting
-   Exception logging
-   API error codes

### Security Considerations

-   Input validation and sanitization
-   SQL injection prevention
-   XSS protection
-   Rate limiting
-   Secure file uploads
-   Payment data encryption

## API Endpoints Structure

### Public Endpoints

```
GET /api/drugs - List all available drugs
GET /api/drugs/{id} - Get drug details
GET /api/drugs/categories - List drug categories
GET /api/drugs/search - Search drugs
GET /api/drugs/category/{category} - Drugs by category
```

### Authenticated Endpoints

```
# Cart Management
GET /api/cart - Get user cart
POST /api/cart/add - Add item to cart
PUT /api/cart/update/{item} - Update cart item
DELETE /api/cart/remove/{item} - Remove cart item
DELETE /api/cart/clear - Clear cart

# Order Management
POST /api/orders - Create order
GET /api/orders - List user orders
GET /api/orders/{id} - Get order details
PUT /api/orders/{id}/cancel - Cancel order

# Payment
POST /api/payments/process - Process payment
GET /api/payments/{id}/status - Check payment status

# Prescription
POST /api/prescriptions - Upload prescription
GET /api/prescriptions - List user prescriptions
GET /api/prescriptions/{id} - Get prescription details
```

### Admin Endpoints

```
# Drug Management
POST /api/admin/drugs - Create drug
PUT /api/admin/drugs/{id} - Update drug
DELETE /api/admin/drugs/{id} - Delete drug
GET /api/admin/drugs/inventory - Inventory report

# Order Management
GET /api/admin/orders - List all orders
PUT /api/admin/orders/{id}/status - Update order status
GET /api/admin/orders/analytics - Order analytics

# Prescription Management
GET /api/admin/prescriptions/pending - Pending prescriptions
PUT /api/admin/prescriptions/{id}/verify - Verify prescription
```

## Testing Strategy

-   Unit tests for models and services
-   Feature tests for API endpoints
-   Integration tests for payment processing
-   Performance tests for high-traffic endpoints

## Deployment Considerations

-   Database migrations in production
-   Payment gateway configuration
-   File storage for prescription images
-   Email service for notifications
-   Queue system for order processing

## Success Metrics

-   Successful drug listing and search
-   Cart operations completion rate
-   Order processing accuracy
-   Payment success rate
-   Prescription verification workflow
-   API response times
-   System uptime and reliability

## Timeline Estimate

-   Phase 1: 1-2 weeks
-   Phase 2: 1-2 weeks
-   Phase 3: 2-3 weeks
-   Phase 4: 2-3 weeks
-   Phase 5: 2-3 weeks
-   Phase 6: 1-2 weeks
-   Testing & Deployment: 1-2 weeks

**Total Estimated Duration: 10-17 weeks**

## Risk Mitigation

-   Regular code reviews
-   Incremental deployment
-   Backup and rollback strategies
-   Performance monitoring
-   Security audits
-   Compliance with pharmacy regulations
