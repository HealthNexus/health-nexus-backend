# Frontend-Backend Integration Guide

## Health Nexus E-Pharmacy Integration Documentation

This comprehensive guide provides a detailed agent execution plan for integrating the frontend Quasar framework (Vue.js) application with the Laravel backend API. The backend is fully implemented across 5 phases as documented in the `/Docs` folder.

## Table of Contents

1. [Integration Overview](#integration-overview)
2. [Backend Analysis](#backend-analysis)
3. [Frontend Requirements](#frontend-requirements)
4. [API Endpoints Mapping](#api-endpoints-mapping)
5. [Authentication Integration](#authentication-integration)
6. [Phase-by-Phase Integration](#phase-by-phase-integration)
7. [State Management Strategy](#state-management-strategy)
8. [Error Handling](#error-handling)
9. [Testing Strategy](#testing-strategy)
10. [Deployment Considerations](#deployment-considerations)

## Integration Overview

The Health Nexus e-pharmacy platform consists of:

### Backend (Laravel 11) - ✅ COMPLETED

-   **Authentication**: Laravel Sanctum for API authentication
-   **E-pharmacy Features**: Full CRUD operations for drugs, cart, orders, payments
-   **Admin Panel**: Comprehensive inventory and order management
-   **Payment Gateway**: PayStack integration for Nigerian market
-   **Database**: SQLite (development) / MySQL (production)

### Frontend (Quasar) - 🔄 TO BE INTEGRATED

-   **Customer Portal**: Drug browsing, cart management, order tracking
-   **Admin Dashboard**: Inventory management, order processing, analytics
-   **Payment Integration**: PayStack payment flow
-   **Authentication**: JWT token management

## Backend Analysis

### Current Implementation Status

Based on the codebase analysis, the following has been implemented:

#### ✅ Phase 1: Enhanced Drug Management

-   **Models**: `Drug`, `DrugCategory`, `Disease` with full e-commerce fields
-   **Controllers**: `DrugController` with public APIs
-   **Resources**: `DrugResource`, `DrugCategoryResource`, `DiseaseResource`
-   **Endpoints**: Public drug browsing, search, filtering
-   **Features**: Stock management, availability checks, price formatting

#### ✅ Phase 2: Shopping Cart System

-   **Models**: `Cart`, `CartItem` with relationships
-   **Service**: `CartService` for cart operations
-   **Controller**: `CartController` with full CRUD
-   **Resources**: `CartResource`, `CartItemResource`
-   **Features**: Session-based carts, user carts, cart synchronization

#### ✅ Phase 3: Order Management System

-   **Models**: `Order`, `OrderItem` with status tracking
-   **Enum**: `OrderStatus` (placed, delivering, delivered)
-   **Service**: `OrderService` for order operations
-   **Controllers**: `OrderController`, `Admin\OrderManagementController`
-   **Features**: Order creation, status updates, delivery confirmation

#### ✅ Phase 4: Payment Integration

-   **Models**: `Payment`, `PaymentLog` for transaction tracking
-   **Service**: `PaymentService` with PayStack integration
-   **Controller**: `PaymentController` with webhook handling
-   **Features**: Payment initialization, verification, logging

#### ✅ Phase 5: Admin Dashboard APIs

-   **Controllers**: `Admin\InventoryController`, analytics endpoints
-   **Features**: Inventory management, low stock alerts, bulk operations

### API Endpoints Available

```
Public Endpoints:
GET  /api/drugs                     - List drugs with filtering
GET  /api/drugs/search              - Search drugs
GET  /api/drugs/categories          - Get drug categories
GET  /api/drugs/category/{slug}     - Drugs by category
GET  /api/drugs/{slug}              - Drug details

Cart Endpoints (Public + Auth):
GET    /api/cart                    - Get cart
POST   /api/cart/add                - Add to cart
PUT    /api/cart/item/{id}          - Update cart item
DELETE /api/cart/item/{id}          - Remove cart item
DELETE /api/cart/clear              - Clear cart
GET    /api/cart/totals             - Get cart totals
GET    /api/cart/validate           - Validate cart

Authenticated Endpoints:
POST   /api/register                - User registration
POST   /api/login                   - User login
POST   /api/logout                  - User logout
GET    /api/user                    - Get user info

Order Endpoints (Auth):
GET    /api/orders                  - User orders
POST   /api/orders                  - Create order
GET    /api/orders/{id}             - Order details
POST   /api/orders/{id}/confirm-delivery - Confirm delivery

Payment Endpoints (Auth):
POST   /api/payments/initialize     - Initialize payment
POST   /api/payments/webhook/paystack - PayStack webhook

Admin Endpoints (Admin Auth):
GET    /api/admin/inventory         - Inventory listing
GET    /api/admin/inventory/statistics - Inventory stats
PUT    /api/admin/inventory/{drug}/stock - Update stock
GET    /api/admin/orders            - Admin order management
PUT    /api/admin/orders/{id}/status - Update order status
```

## Frontend Requirements

### Technology Stack Recommendations

```json
{
    "framework": "Next.js 14+ with App Router",
    "state_management": "Zustand or Redux Toolkit",
    "http_client": "Axios with interceptors",
    "ui_library": "Tailwind CSS + Headless UI or Shadcn/ui",
    "form_handling": "React Hook Form + Zod validation",
    "payment": "PayStack React SDK",
    "authentication": "JWT tokens with refresh logic",
    "notifications": "React Hot Toast or Sonner",
    "routing": "Next.js App Router with middleware"
}
```

### Required Frontend Components

#### 1. Authentication Components

```typescript
// components/auth/
├── LoginForm.tsx
├── RegisterForm.tsx
├── LogoutButton.tsx
├── ProtectedRoute.tsx
└── AdminRoute.tsx
```

#### 2. Customer Components

```typescript
// components/customer/
├── DrugCatalog.tsx
├── DrugCard.tsx
├── DrugDetails.tsx
├── SearchBar.tsx
├── CategoryFilter.tsx
├── Cart/
│   ├── CartSidebar.tsx
│   ├── CartItem.tsx
│   └── CartSummary.tsx
├── Orders/
│   ├── OrderHistory.tsx
│   ├── OrderDetails.tsx
│   └── OrderTracking.tsx
└── Payment/
    ├── PaymentForm.tsx
    ├── PaymentCallback.tsx
    └── PaymentStatus.tsx
```

#### 3. Admin Components

```typescript
// components/admin/
├── Dashboard.tsx
├── Inventory/
│   ├── InventoryList.tsx
│   ├── StockUpdateForm.tsx
│   ├── LowStockAlerts.tsx
│   └── BulkActions.tsx
├── Orders/
│   ├── OrderManagement.tsx
│   ├── OrderDetails.tsx
│   └── StatusUpdate.tsx
└── Analytics/
    ├── SalesChart.tsx
    ├── InventoryStats.tsx
    └── RevenueMetrics.tsx
```

## API Endpoints Mapping

### Frontend API Service Structure

```typescript
// services/api/
├── client.ts              // Axios configuration
├── auth.ts               // Authentication endpoints
├── drugs.ts              // Drug-related endpoints
├── cart.ts               // Cart operations
├── orders.ts             // Order management
├── payments.ts           // Payment processing
└── admin/
    ├── inventory.ts      // Admin inventory
    ├── orders.ts         // Admin orders
    └── analytics.ts      // Admin analytics
```

### Detailed API Integration Plan

#### 1. Authentication API (`services/api/auth.ts`)

```typescript
import { apiClient } from "./client";

export interface LoginRequest {
    email: string;
    password: string;
}

export interface RegisterRequest {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
}

export interface AuthResponse {
    user: User;
    token: string;
    expires_at: string;
}

export const authAPI = {
    login: (data: LoginRequest): Promise<AuthResponse> =>
        apiClient.post("/login", data).then((res) => res.data),

    register: (data: RegisterRequest): Promise<AuthResponse> =>
        apiClient.post("/register", data).then((res) => res.data),

    logout: (): Promise<void> =>
        apiClient.post("/logout").then((res) => res.data),

    getUser: (): Promise<User> =>
        apiClient.get("/user").then((res) => res.data.user),
};
```

#### 2. Drugs API (`services/api/drugs.ts`)

```typescript
export interface DrugFilters {
    category?: string;
    search?: string;
    min_price?: number;
    max_price?: number;
    sort_by?: "name" | "price" | "created_at" | "stock";
    sort_direction?: "asc" | "desc";
    per_page?: number;
    page?: number;
}

export interface Drug {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    formatted_price: string;
    stock: number;
    expiry_date: string | null;
    image: string | null;
    status: "active" | "inactive" | "out_of_stock";
    is_available: boolean;
    is_expired: boolean;
    categories: DrugCategory[];
    diseases: Disease[];
    created_at: string;
    updated_at: string;
}

export const drugsAPI = {
    list: (filters: DrugFilters = {}): Promise<PaginatedResponse<Drug>> =>
        apiClient.get("/drugs", { params: filters }).then((res) => res.data),

    search: (query: string): Promise<PaginatedResponse<Drug>> =>
        apiClient
            .get("/drugs/search", { params: { q: query } })
            .then((res) => res.data),

    getBySlug: (slug: string): Promise<Drug> =>
        apiClient.get(`/drugs/${slug}`).then((res) => res.data.data),

    getCategories: (): Promise<DrugCategory[]> =>
        apiClient.get("/drugs/categories").then((res) => res.data.data),

    getByCategory: (
        categorySlug: string,
        filters: DrugFilters = {}
    ): Promise<PaginatedResponse<Drug>> =>
        apiClient
            .get(`/drugs/category/${categorySlug}`, { params: filters })
            .then((res) => res.data),
};
```

#### 3. Cart API (`services/api/cart.ts`)

```typescript
export interface CartItem {
    id: number;
    cart_id: number;
    drug_id: number;
    quantity: number;
    unit_price: number;
    total_price: number;
    drug: Drug;
    created_at: string;
    updated_at: string;
}

export interface Cart {
    id: number;
    user_id: number | null;
    session_id: string | null;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    formatted_subtotal: string;
    formatted_tax_amount: string;
    formatted_total_amount: string;
    items: CartItem[];
    items_count: number;
    is_empty: boolean;
    created_at: string;
    updated_at: string;
}

export interface AddToCartRequest {
    drug_id: number;
    quantity: number;
}

export const cartAPI = {
    get: (): Promise<Cart> =>
        apiClient.get("/cart").then((res) => res.data.data),

    addItem: (
        data: AddToCartRequest
    ): Promise<{ cart: Cart; added_item: CartItem }> =>
        apiClient.post("/cart/add", data).then((res) => res.data.data),

    updateItem: (
        itemId: number,
        quantity: number
    ): Promise<{ cart: Cart; updated_item: CartItem }> =>
        apiClient
            .put(`/cart/item/${itemId}`, { quantity })
            .then((res) => res.data.data),

    removeItem: (itemId: number): Promise<{ cart: Cart }> =>
        apiClient.delete(`/cart/item/${itemId}`).then((res) => res.data.data),

    clear: (): Promise<void> =>
        apiClient.delete("/cart/clear").then((res) => res.data),

    getTotals: (): Promise<CartTotals> =>
        apiClient.get("/cart/totals").then((res) => res.data.data),

    validate: (): Promise<CartValidation> =>
        apiClient.get("/cart/validate").then((res) => res.data.data),
};
```

#### 4. Orders API (`services/api/orders.ts`)

```typescript
export interface ShippingAddress {
    line1: string;
    line2?: string;
    city: string;
    state: string;
    postal_code?: string;
}

export interface CreateOrderRequest {
    shipping_address: ShippingAddress;
    phone_number: string;
    delivery_notes?: string;
}

export interface Order {
    id: number;
    user_id: number;
    order_number: string;
    status: "placed" | "delivering" | "delivered";
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    shipping_address: ShippingAddress;
    phone_number: string;
    delivery_notes: string | null;
    payment_status: "pending" | "paid" | "failed" | "refunded";
    placed_at: string;
    delivering_at: string | null;
    delivered_at: string | null;
    items: OrderItem[];
    user: User;
    status_updated_by: User | null;
    created_at: string;
    updated_at: string;
}

export const ordersAPI = {
    list: (filters: OrderFilters = {}): Promise<PaginatedResponse<Order>> =>
        apiClient.get("/orders", { params: filters }).then((res) => res.data),

    create: (data: CreateOrderRequest): Promise<Order> =>
        apiClient.post("/orders", data).then((res) => res.data.data.order),

    getById: (id: number): Promise<Order> =>
        apiClient.get(`/orders/${id}`).then((res) => res.data.data),

    confirmDelivery: (id: number): Promise<Order> =>
        apiClient
            .post(`/orders/${id}/confirm-delivery`)
            .then((res) => res.data.data.order),

    getStatistics: (): Promise<OrderStatistics> =>
        apiClient.get("/orders/statistics").then((res) => res.data.data),
};
```

#### 5. Payments API (`services/api/payments.ts`)

```typescript
export interface PaymentInitialization {
    payment_id: number;
    payment_reference: string;
    authorization_url: string;
    access_code: string;
    amount: string;
}

export const paymentsAPI = {
    initialize: (orderId: number): Promise<PaymentInitialization> =>
        apiClient
            .post("/payments/initialize", { order_id: orderId })
            .then((res) => res.data.data),

    calculateFees: (amount: number): Promise<PaymentFees> =>
        apiClient
            .post("/payments/calculate-fees", { amount })
            .then((res) => res.data.data),
};
```

## Phase-by-Phase Integration

### Phase 1: Basic Setup and Authentication

#### Step 1.1: Project Setup

```bash
# Create Next.js project
npx create-next-app@latest health-nexus-frontend --typescript --tailwind --app

# Install required dependencies
npm install axios zustand react-hook-form @hookform/resolvers zod
npm install react-hot-toast lucide-react @headlessui/react
npm install @paystack/inline-js
```

#### Step 1.2: API Client Configuration

Create `lib/api/client.ts`:

```typescript
import axios from "axios";

const API_BASE_URL =
    process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

export const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    withCredentials: true, // For CORS with cookies
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("auth_token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor for error handling
apiClient.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 401) {
            // Clear token and redirect to login
            localStorage.removeItem("auth_token");
            window.location.href = "/login";
        }
        return Promise.reject(error);
    }
);
```

#### Step 1.3: Authentication Store

Create `stores/authStore.ts`:

```typescript
import { create } from "zustand";
import { persist } from "zustand/middleware";
import { authAPI } from "@/lib/api/auth";

interface User {
    id: number;
    name: string;
    email: string;
    role: {
        id: number;
        name: string;
        slug: string;
    };
}

interface AuthState {
    user: User | null;
    token: string | null;
    isAuthenticated: boolean;
    isAdmin: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (data: RegisterRequest) => Promise<void>;
    logout: () => void;
    refreshUser: () => Promise<void>;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set, get) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            isAdmin: false,

            login: async (email: string, password: string) => {
                try {
                    const response = await authAPI.login({ email, password });
                    const { user, token } = response;

                    localStorage.setItem("auth_token", token);

                    set({
                        user,
                        token,
                        isAuthenticated: true,
                        isAdmin: user.role.slug === "admin",
                    });
                } catch (error) {
                    throw error;
                }
            },

            register: async (data: RegisterRequest) => {
                try {
                    const response = await authAPI.register(data);
                    const { user, token } = response;

                    localStorage.setItem("auth_token", token);

                    set({
                        user,
                        token,
                        isAuthenticated: true,
                        isAdmin: user.role.slug === "admin",
                    });
                } catch (error) {
                    throw error;
                }
            },

            logout: () => {
                localStorage.removeItem("auth_token");
                authAPI.logout().catch(() => {}); // Silent fail for logout API call

                set({
                    user: null,
                    token: null,
                    isAuthenticated: false,
                    isAdmin: false,
                });
            },

            refreshUser: async () => {
                try {
                    const user = await authAPI.getUser();
                    set({
                        user,
                        isAdmin: user.role.slug === "admin",
                    });
                } catch (error) {
                    get().logout();
                }
            },
        }),
        {
            name: "auth-storage",
            partialize: (state) => ({
                user: state.user,
                token: state.token,
                isAuthenticated: state.isAuthenticated,
                isAdmin: state.isAdmin,
            }),
        }
    )
);
```

### Phase 2: Drug Catalog Integration

#### Step 2.1: Drug Store

Create `stores/drugStore.ts`:

```typescript
import { create } from "zustand";
import { drugsAPI } from "@/lib/api/drugs";

interface DrugState {
    drugs: Drug[];
    categories: DrugCategory[];
    loading: boolean;
    error: string | null;
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    } | null;
    filters: DrugFilters;

    fetchDrugs: (filters?: DrugFilters) => Promise<void>;
    fetchCategories: () => Promise<void>;
    searchDrugs: (query: string) => Promise<void>;
    setFilters: (filters: Partial<DrugFilters>) => void;
    clearFilters: () => void;
}

export const useDrugStore = create<DrugState>((set, get) => ({
    drugs: [],
    categories: [],
    loading: false,
    error: null,
    pagination: null,
    filters: {},

    fetchDrugs: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const response = await drugsAPI.list({
                ...get().filters,
                ...filters,
            });
            set({
                drugs: response.data,
                pagination: response.meta,
                filters: { ...get().filters, ...filters },
                loading: false,
            });
        } catch (error) {
            set({ error: "Failed to fetch drugs", loading: false });
        }
    },

    fetchCategories: async () => {
        try {
            const categories = await drugsAPI.getCategories();
            set({ categories });
        } catch (error) {
            console.error("Failed to fetch categories:", error);
        }
    },

    searchDrugs: async (query: string) => {
        set({ loading: true, error: null });
        try {
            const response = await drugsAPI.search(query);
            set({
                drugs: response.data,
                pagination: response.meta,
                loading: false,
            });
        } catch (error) {
            set({ error: "Search failed", loading: false });
        }
    },

    setFilters: (newFilters: Partial<DrugFilters>) => {
        const filters = { ...get().filters, ...newFilters };
        set({ filters });
        get().fetchDrugs(filters);
    },

    clearFilters: () => {
        set({ filters: {} });
        get().fetchDrugs({});
    },
}));
```

#### Step 2.2: Drug Catalog Component

Create `components/drugs/DrugCatalog.tsx`:

```typescript
"use client";

import { useEffect } from "react";
import { useDrugStore } from "@/stores/drugStore";
import { DrugCard } from "./DrugCard";
import { DrugFilters } from "./DrugFilters";
import { Pagination } from "../ui/Pagination";
import { LoadingSpinner } from "../ui/LoadingSpinner";

export function DrugCatalog() {
    const { drugs, loading, error, pagination, fetchDrugs, fetchCategories } =
        useDrugStore();

    useEffect(() => {
        fetchDrugs();
        fetchCategories();
    }, []);

    if (loading && drugs.length === 0) {
        return <LoadingSpinner />;
    }

    if (error) {
        return (
            <div className="text-center py-12">
                <p className="text-red-600">{error}</p>
                <button
                    onClick={() => fetchDrugs()}
                    className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Try Again
                </button>
            </div>
        );
    }

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {/* Filters */}
            <DrugFilters />

            {/* Results */}
            <div className="mt-8">
                {drugs.length === 0 ? (
                    <div className="text-center py-12">
                        <p className="text-gray-600">
                            No drugs found matching your criteria.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {drugs.map((drug) => (
                                <DrugCard key={drug.id} drug={drug} />
                            ))}
                        </div>

                        {/* Pagination */}
                        {pagination && pagination.last_page > 1 && (
                            <div className="mt-8">
                                <Pagination
                                    currentPage={pagination.current_page}
                                    totalPages={pagination.last_page}
                                    onPageChange={(page) =>
                                        fetchDrugs({ page })
                                    }
                                />
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Loading overlay */}
            {loading && drugs.length > 0 && (
                <div className="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
                    <LoadingSpinner />
                </div>
            )}
        </div>
    );
}
```

### Phase 3: Shopping Cart Integration

#### Step 3.1: Cart Store

Create `stores/cartStore.ts`:

```typescript
import { create } from "zustand";
import { cartAPI } from "@/lib/api/cart";
import toast from "react-hot-toast";

interface CartState {
    cart: Cart | null;
    loading: boolean;
    error: string | null;

    fetchCart: () => Promise<void>;
    addItem: (drugId: number, quantity: number) => Promise<void>;
    updateItem: (itemId: number, quantity: number) => Promise<void>;
    removeItem: (itemId: number) => Promise<void>;
    clearCart: () => Promise<void>;
    validateCart: () => Promise<CartValidation>;
}

export const useCartStore = create<CartState>((set, get) => ({
    cart: null,
    loading: false,
    error: null,

    fetchCart: async () => {
        set({ loading: true, error: null });
        try {
            const cart = await cartAPI.get();
            set({ cart, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch cart", loading: false });
        }
    },

    addItem: async (drugId: number, quantity: number) => {
        try {
            const response = await cartAPI.addItem({
                drug_id: drugId,
                quantity,
            });
            set({ cart: response.cart });
            toast.success("Item added to cart");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to add item to cart";
            toast.error(message);
            throw error;
        }
    },

    updateItem: async (itemId: number, quantity: number) => {
        try {
            const response = await cartAPI.updateItem(itemId, quantity);
            set({ cart: response.cart });
            toast.success("Cart updated");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to update cart";
            toast.error(message);
            throw error;
        }
    },

    removeItem: async (itemId: number) => {
        try {
            const response = await cartAPI.removeItem(itemId);
            set({ cart: response.cart });
            toast.success("Item removed from cart");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to remove item";
            toast.error(message);
            throw error;
        }
    },

    clearCart: async () => {
        try {
            await cartAPI.clear();
            set({ cart: null });
            toast.success("Cart cleared");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to clear cart";
            toast.error(message);
            throw error;
        }
    },

    validateCart: async () => {
        try {
            return await cartAPI.validate();
        } catch (error) {
            throw error;
        }
    },
}));
```

### Phase 4: Order Management Integration

#### Step 4.1: Order Store

Create `stores/orderStore.ts`:

```typescript
import { create } from "zustand";
import { ordersAPI } from "@/lib/api/orders";
import toast from "react-hot-toast";

interface OrderState {
    orders: Order[];
    currentOrder: Order | null;
    loading: boolean;
    error: string | null;

    fetchOrders: (filters?: OrderFilters) => Promise<void>;
    fetchOrder: (id: number) => Promise<void>;
    createOrder: (data: CreateOrderRequest) => Promise<Order>;
    confirmDelivery: (id: number) => Promise<void>;
}

export const useOrderStore = create<OrderState>((set, get) => ({
    orders: [],
    currentOrder: null,
    loading: false,
    error: null,

    fetchOrders: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const response = await ordersAPI.list(filters);
            set({ orders: response.data, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch orders", loading: false });
        }
    },

    fetchOrder: async (id: number) => {
        set({ loading: true, error: null });
        try {
            const order = await ordersAPI.getById(id);
            set({ currentOrder: order, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch order", loading: false });
        }
    },

    createOrder: async (data: CreateOrderRequest) => {
        try {
            const order = await ordersAPI.create(data);
            toast.success("Order created successfully");
            return order;
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to create order";
            toast.error(message);
            throw error;
        }
    },

    confirmDelivery: async (id: number) => {
        try {
            const order = await ordersAPI.confirmDelivery(id);
            set({ currentOrder: order });
            toast.success("Delivery confirmed");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to confirm delivery";
            toast.error(message);
            throw error;
        }
    },
}));
```

### Phase 5: Payment Integration

#### Step 5.1: Payment Service

Create `lib/services/paymentService.ts`:

```typescript
import { paymentsAPI } from "@/lib/api/payments";

declare global {
    interface Window {
        PaystackPop: any;
    }
}

export class PaymentService {
    static initializePayment(orderId: number): Promise<PaymentInitialization> {
        return paymentsAPI.initialize(orderId);
    }

    static async processPayment(
        paymentData: PaymentInitialization,
        onSuccess: (reference: string) => void,
        onClose: () => void
    ): Promise<void> {
        if (typeof window === "undefined" || !window.PaystackPop) {
            throw new Error("PayStack library not loaded");
        }

        const handler = window.PaystackPop.setup({
            key: process.env.NEXT_PUBLIC_PAYSTACK_PUBLIC_KEY,
            email: "", // Will be set by backend
            amount: paymentData.amount,
            ref: paymentData.payment_reference,
            callback: (response: any) => {
                onSuccess(response.reference);
            },
            onClose: onClose,
        });

        handler.openIframe();
    }

    static calculateFees(amount: number): Promise<PaymentFees> {
        return paymentsAPI.calculateFees(amount);
    }
}
```

### Phase 6: Admin Dashboard Integration

#### Step 6.1: Admin Store

Create `stores/adminStore.ts`:

```typescript
import { create } from "zustand";
import { adminAPI } from "@/lib/api/admin";

interface AdminState {
    inventory: Drug[];
    orders: Order[];
    analytics: DashboardAnalytics | null;
    loading: boolean;
    error: string | null;

    fetchInventory: (filters?: InventoryFilters) => Promise<void>;
    fetchOrders: (filters?: AdminOrderFilters) => Promise<void>;
    fetchAnalytics: () => Promise<void>;
    updateStock: (drugId: number, data: StockUpdateData) => Promise<void>;
    updateOrderStatus: (orderId: number, status: OrderStatus) => Promise<void>;
}

export const useAdminStore = create<AdminState>((set, get) => ({
    inventory: [],
    orders: [],
    analytics: null,
    loading: false,
    error: null,

    fetchInventory: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const response = await adminAPI.inventory.list(filters);
            set({ inventory: response.data, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch inventory", loading: false });
        }
    },

    fetchOrders: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const response = await adminAPI.orders.list(filters);
            set({ orders: response.data, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch orders", loading: false });
        }
    },

    fetchAnalytics: async () => {
        set({ loading: true, error: null });
        try {
            const analytics = await adminAPI.analytics.getDashboard();
            set({ analytics, loading: false });
        } catch (error) {
            set({ error: "Failed to fetch analytics", loading: false });
        }
    },

    updateStock: async (drugId: number, data: StockUpdateData) => {
        try {
            await adminAPI.inventory.updateStock(drugId, data);
            // Refresh inventory
            get().fetchInventory();
            toast.success("Stock updated successfully");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to update stock";
            toast.error(message);
            throw error;
        }
    },

    updateOrderStatus: async (orderId: number, status: OrderStatus) => {
        try {
            await adminAPI.orders.updateStatus(orderId, status);
            // Refresh orders
            get().fetchOrders();
            toast.success("Order status updated");
        } catch (error: any) {
            const message =
                error.response?.data?.message ||
                "Failed to update order status";
            toast.error(message);
            throw error;
        }
    },
}));
```

## State Management Strategy

### Zustand Store Architecture

```typescript
// Store structure
stores/
├── authStore.ts          // User authentication state
├── drugStore.ts          // Drug catalog and search
├── cartStore.ts          // Shopping cart management
├── orderStore.ts         // Order history and tracking
├── adminStore.ts         // Admin dashboard state
└── uiStore.ts            // UI state (modals, notifications)
```

### Data Flow Pattern

```
Component → Store → API Service → Backend
    ↑                              ↓
    └── State Update ← Response ←──┘
```

## Error Handling

### API Error Handling Strategy

```typescript
// lib/utils/errorHandler.ts
export class APIError extends Error {
    constructor(message: string, public status: number, public code?: string) {
        super(message);
        this.name = "APIError";
    }
}

export function handleAPIError(error: any): APIError {
    if (error.response) {
        const { status, data } = error.response;
        return new APIError(
            data.message || "An error occurred",
            status,
            data.code
        );
    }

    if (error.request) {
        return new APIError("Network error", 0);
    }

    return new APIError(error.message || "Unknown error", 0);
}
```

### Form Validation Strategy

```typescript
// lib/schemas/validationSchemas.ts
import { z } from "zod";

export const loginSchema = z.object({
    email: z.string().email("Invalid email address"),
    password: z.string().min(6, "Password must be at least 6 characters"),
});

export const addToCartSchema = z.object({
    drug_id: z.number().positive("Invalid drug ID"),
    quantity: z
        .number()
        .min(1, "Quantity must be at least 1")
        .max(100, "Maximum quantity is 100"),
});

export const orderSchema = z.object({
    shipping_address: z.object({
        line1: z.string().min(5, "Address line 1 is required"),
        line2: z.string().optional(),
        city: z.string().min(2, "City is required"),
        state: z.string().min(2, "State is required"),
        postal_code: z.string().optional(),
    }),
    phone_number: z.string().min(10, "Valid phone number is required"),
    delivery_notes: z.string().optional(),
});
```

## Testing Strategy

### API Integration Tests

```typescript
// __tests__/api/drugs.test.ts
import { drugsAPI } from "@/lib/api/drugs";
import { apiClient } from "@/lib/api/client";

jest.mock("@/lib/api/client");
const mockedApiClient = apiClient as jest.Mocked<typeof apiClient>;

describe("Drugs API", () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test("should fetch drugs list", async () => {
        const mockResponse = {
            data: {
                data: [
                    { id: 1, name: "Aspirin", price: 10.99 },
                    { id: 2, name: "Paracetamol", price: 5.99 },
                ],
                meta: { current_page: 1, last_page: 1 },
            },
        };

        mockedApiClient.get.mockResolvedValue(mockResponse);

        const result = await drugsAPI.list();

        expect(mockedApiClient.get).toHaveBeenCalledWith("/drugs", {
            params: {},
        });
        expect(result.data).toHaveLength(2);
    });

    test("should handle API errors", async () => {
        mockedApiClient.get.mockRejectedValue({
            response: { status: 500, data: { message: "Server error" } },
        });

        await expect(drugsAPI.list()).rejects.toThrow();
    });
});
```

### Component Tests

```typescript
// __tests__/components/DrugCard.test.tsx
import { render, screen, fireEvent } from "@testing-library/react";
import { DrugCard } from "@/components/drugs/DrugCard";
import { useCartStore } from "@/stores/cartStore";

jest.mock("@/stores/cartStore");
const mockedUseCartStore = useCartStore as jest.MockedFunction<
    typeof useCartStore
>;

describe("DrugCard", () => {
    const mockDrug = {
        id: 1,
        name: "Aspirin",
        slug: "aspirin",
        price: 10.99,
        formatted_price: "₦10.99",
        stock: 50,
        is_available: true,
    };

    test("should render drug information", () => {
        mockedUseCartStore.mockReturnValue({
            addItem: jest.fn(),
            loading: false,
        } as any);

        render(<DrugCard drug={mockDrug} />);

        expect(screen.getByText("Aspirin")).toBeInTheDocument();
        expect(screen.getByText("₦10.99")).toBeInTheDocument();
    });

    test("should add item to cart when button is clicked", () => {
        const mockAddItem = jest.fn();
        mockedUseCartStore.mockReturnValue({
            addItem: mockAddItem,
            loading: false,
        } as any);

        render(<DrugCard drug={mockDrug} />);

        fireEvent.click(screen.getByText("Add to Cart"));

        expect(mockAddItem).toHaveBeenCalledWith(1, 1);
    });
});
```

## Deployment Considerations

### Environment Variables

```bash
# Frontend (.env.local)
NEXT_PUBLIC_API_URL=https://api.health-nexus.com/api
NEXT_PUBLIC_PAYSTACK_PUBLIC_KEY=pk_live_your_paystack_public_key
NEXT_PUBLIC_APP_ENV=production

# Backend (.env)
APP_URL=https://api.health-nexus.com
FRONTEND_URL=https://health-nexus.com
PAYSTACK_PUBLIC_KEY=pk_live_your_paystack_public_key
PAYSTACK_SECRET_KEY=sk_live_your_paystack_secret_key
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret
```

### CORS Configuration

Backend `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'payments/webhook/paystack'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Build and Deployment Scripts

```json
{
    "scripts": {
        "build": "next build",
        "start": "next start",
        "dev": "next dev",
        "test": "jest",
        "test:watch": "jest --watch",
        "lint": "next lint",
        "type-check": "tsc --noEmit"
    }
}
```

## Integration Checklist

### Phase 1: Authentication ✅

-   [ ] Set up API client with interceptors
-   [ ] Implement authentication store
-   [ ] Create login/register forms
-   [ ] Add protected route middleware
-   [ ] Test authentication flow

### Phase 2: Drug Catalog ✅

-   [ ] Implement drug listing with pagination
-   [ ] Add search and filtering
-   [ ] Create drug detail pages
-   [ ] Add category navigation
-   [ ] Test all drug endpoints

### Phase 3: Shopping Cart ✅

-   [ ] Implement cart store and operations
-   [ ] Create cart sidebar/page
-   [ ] Add cart persistence
-   [ ] Implement cart validation
-   [ ] Test cart synchronization

### Phase 4: Order Management ✅

-   [ ] Create order forms and validation
-   [ ] Implement order history
-   [ ] Add order tracking
-   [ ] Create delivery confirmation
-   [ ] Test order flow end-to-end

### Phase 5: Payment Processing ✅

-   [ ] Integrate PayStack SDK
-   [ ] Implement payment flow
-   [ ] Add payment callbacks
-   [ ] Create payment status pages
-   [ ] Test payment scenarios

### Phase 6: Admin Dashboard ✅

-   [ ] Create admin authentication
-   [ ] Implement inventory management
-   [ ] Add order processing
-   [ ] Create analytics dashboard
-   [ ] Test admin workflows

### Final Integration ✅

-   [ ] End-to-end testing
-   [ ] Performance optimization
-   [ ] Error handling review
-   [ ] Security audit
-   [ ] Documentation completion

## Conclusion

This integration plan provides a comprehensive roadmap for connecting the React/Next.js frontend with the Laravel backend. The backend is fully implemented according to the 5-phase documentation, and this plan ensures seamless integration with proper error handling, state management, and testing strategies.

The key to successful integration is following the phase-by-phase approach, implementing proper TypeScript types, and maintaining consistent error handling patterns throughout the application.
