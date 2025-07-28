# Health Nexus E-Pharmacy Backend

This is a comprehensive Laravel-based backend system for an e-pharmacy platform, implementing modern e-commerce features with secure payment processing and administrative management capabilities.

## Features Implemented

### Phase 1: Enhanced Drug Management ✅

-   **Enhanced Drug Model**: Added e-commerce fields (price, stock, expiry_date, image, status, description)
-   **Public Drug APIs**: Browse, search, filter, and view drugs
-   **Categories & Disease Mapping**: Drug categorization and disease relationships
-   **Stock Management**: Inventory tracking with availability checks
-   **Advanced Search**: Full-text search with ranking and filtering

### Phase 2: Shopping Cart System ✅

-   **Session-Based Carts**: Guest user cart support
-   **Database-Backed Carts**: Authenticated user persistent carts
-   **Cart Synchronization**: Seamless cart merging on login/registration
-   **Cart Management**: Add, update, remove items with validation
-   **Tax Calculations**: Automatic VAT calculation (7.5%)
-   **Cart Validation**: Stock availability and price change checks

### Phase 3: Order Management System ✅

-   **3-State Order Tracking**: Placed → Delivering → Delivered
-   **Order Creation**: Convert cart to order with inventory deduction
-   **Order Status Management**: Admin updates with user confirmation
-   **Order History**: Customer order tracking and details
-   **Delivery Confirmation**: Customer-initiated delivery confirmation

### Phase 4: Payment Integration ✅

-   **PayStack Integration**: Nigerian payment gateway support
-   **Payment Tracking**: Comprehensive payment logging and status management
-   **Webhook Processing**: Secure payment verification via webhooks
-   **Payment Security**: Signature verification and fraud protection
-   **Payment Methods**: Support for cards, bank transfers, USSD

### Phase 5: Admin Dashboard APIs ✅

-   **Inventory Management**: Stock tracking, updates, and alerts
-   **Low Stock Alerts**: Automated inventory monitoring
-   **Admin Analytics**: Sales reports and inventory statistics
-   **Bulk Operations**: Mass stock updates and drug management
-   **Order Processing**: Admin order workflow management

## Technical Stack

-   **Framework**: Laravel 11
-   **Database**: SQLite (development) / MySQL (production)
-   **Authentication**: Laravel Sanctum (API tokens)
-   **Payment Gateway**: PayStack
-   **API Documentation**: Scramble (with bearer token auth)
-   **Testing**: Pest PHP

## Database Schema

### Core Tables

-   `users` - User authentication and profiles
-   `drugs` - Product catalog with e-commerce fields
-   `drug_categories` - Product categorization
-   `diseases` - Medical condition mapping
-   `carts` - Shopping cart persistence
-   `cart_items` - Cart line items
-   `orders` - Order management
-   `order_items` - Order line items
-   `payments` - Payment tracking
-   `payment_logs` - Payment audit trail

## API Endpoints

### Public Endpoints

```
GET /api/drugs - Browse drugs with filtering
GET /api/drugs/search - Search drugs
GET /api/drugs/categories - List categories
GET /api/drugs/category/{slug} - Drugs by category
GET /api/drugs/{slug} - Drug details

GET /api/cart - View cart
POST /api/cart/add - Add to cart
PUT /api/cart/item/{id} - Update cart item
DELETE /api/cart/item/{id} - Remove cart item
DELETE /api/cart/clear - Clear cart
```

### Authenticated Endpoints

```
GET /api/orders - Order history
POST /api/orders - Create order
GET /api/orders/{id} - Order details
POST /api/orders/{id}/confirm-delivery - Confirm delivery

POST /api/payment/initialize - Start payment
GET /api/payment/verify/{reference} - Verify payment
POST /api/payment/webhook - Payment webhook
```

### Admin Endpoints

```
GET /api/admin/inventory - Inventory overview
GET /api/admin/inventory/statistics - Inventory stats
GET /api/admin/inventory/low-stock-alerts - Low stock alerts
PUT /api/admin/inventory/{drug}/stock - Update stock
POST /api/admin/inventory/bulk-update-stock - Bulk stock update

GET /api/admin/orders - Admin order management
PUT /api/admin/orders/{id}/status - Update order status
GET /api/admin/orders/analytics - Order analytics
```

## Installation & Setup

### Requirements

-   PHP 8.2+
-   Composer
-   Node.js & NPM (for frontend build tools)
-   SQLite or MySQL

### Installation Steps

1. **Clone the repository**

```bash
git clone <repository-url>
cd health-nexus-backend
```

2. **Install dependencies**

```bash
composer install
npm install
```

3. **Environment setup**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**

```bash
php artisan migrate
php artisan db:seed
```

5. **PayStack configuration**
   Add your PayStack credentials to `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret
```

6. **Start development server**

```bash
php artisan serve
```

## Configuration

### Environment Variables

```env
# PayStack Payment Gateway
PAYSTACK_PUBLIC_KEY=your_public_key
PAYSTACK_SECRET_KEY=your_secret_key
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret
PAYSTACK_TEST_MODE=true

# Cart Configuration
CART_TAX_RATE=0.075
CART_SESSION_EXPIRY=10080
CART_MAX_ITEMS=50

# Payment Configuration
PAYMENT_CURRENCY=NGN
PAYMENT_TIMEOUT=30
```

## Security Features

-   **Authentication**: Sanctum API tokens with role-based access
-   **Authorization**: Admin middleware for restricted operations
-   **Payment Security**: PayStack signature verification
-   **Input Validation**: Comprehensive request validation
-   **SQL Injection Protection**: Eloquent ORM with prepared statements
-   **CORS**: Configured for cross-origin requests

## Testing

Run the test suite:

```bash
php artisan test
# or
vendor/bin/pest
```

### Test Coverage Areas

-   Drug management and search
-   Cart functionality and persistence
-   Order creation and tracking
-   Payment processing and verification
-   Admin operations and analytics
-   Authentication and authorization

## Business Logic

### Order Flow

1. **Cart Management**: Users add drugs to cart (session/database)
2. **Order Creation**: Cart converts to order with inventory deduction
3. **Payment Processing**: PayStack payment initialization and verification
4. **Order Fulfillment**: Admin marks order as delivering
5. **Delivery Confirmation**: Customer confirms receipt

### Inventory Management

-   Real-time stock tracking
-   Automatic status updates (active/out_of_stock)
-   Low stock alerts and notifications
-   Bulk inventory operations

### Payment Processing

-   Secure PayStack integration
-   Payment logging and audit trail
-   Webhook verification and processing
-   Multiple payment methods support

## API Documentation

Access the interactive API documentation at:

```
http://localhost:8000/docs/api
```

The documentation includes:

-   Bearer token authentication
-   Request/response examples
-   Schema definitions
-   Error codes and responses

## Production Deployment

### Database Migration

```bash
php artisan migrate --force
```

### Environment Configuration

-   Set `APP_ENV=production`
-   Configure production database
-   Set PayStack live keys
-   Enable HTTPS
-   Configure proper CORS settings

### Performance Optimization

-   Enable Laravel caching
-   Use Redis for sessions/cache
-   Optimize database queries
-   Enable gzip compression
-   Configure CDN for static assets

## Monitoring & Logging

The system includes comprehensive logging for:

-   Payment transactions
-   Inventory changes
-   Order status updates
-   Admin actions
-   Error tracking

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For technical support and questions:

-   Create an issue in the repository
-   Contact the development team
-   Check the documentation at `/docs/api`

---

**Health Nexus E-Pharmacy Backend** - A comprehensive e-commerce solution for pharmaceutical retail with modern payment processing and administrative management capabilities.
