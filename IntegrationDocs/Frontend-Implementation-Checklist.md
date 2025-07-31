# Frontend Implementation Checklist

## Complete Implementation Guide for React/Next.js Frontend

This document provides a detailed checklist and step-by-step implementation guide for building the React/Next.js frontend to integrate with the Health Nexus E-Pharmacy Laravel backend.

## 🚀 Phase 1: Project Setup and Configuration

### ✅ 1.1 Project Initialization

```bash
# Create Next.js project with TypeScript and Tailwind CSS
npx create-next-app@latest health-nexus-frontend \
  --typescript \
  --tailwind \
  --eslint \
  --app \
  --src-dir \
  --import-alias "@/*"

cd health-nexus-frontend
```

### ✅ 1.2 Install Core Dependencies

```bash
# State Management and HTTP Client
npm install zustand axios

# Form Handling and Validation
npm install react-hook-form @hookform/resolvers zod

# UI Components and Icons
npm install @headlessui/react @heroicons/react lucide-react

# Notifications and Utils
npm install react-hot-toast date-fns clsx tailwind-merge

# Payment Integration
npm install @paystack/inline-js

# Development Dependencies
npm install -D @types/node
```

### ✅ 1.3 Environment Configuration

Create `.env.local`:

```bash
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_FRONTEND_URL=http://localhost:3000

# PayStack Configuration
NEXT_PUBLIC_PAYSTACK_PUBLIC_KEY=pk_test_your_public_key_here

# App Configuration
NEXT_PUBLIC_APP_NAME="Health Nexus E-Pharmacy"
NEXT_PUBLIC_APP_VERSION=1.0.0
NEXT_PUBLIC_CURRENCY=NGN
NEXT_PUBLIC_CURRENCY_SYMBOL=₵
```

### ✅ 1.4 TypeScript Configuration

Update `tsconfig.json`:

```json
{
    "compilerOptions": {
        "target": "es5",
        "lib": ["dom", "dom.iterable", "es6"],
        "allowJs": true,
        "skipLibCheck": true,
        "strict": true,
        "noEmit": true,
        "esModuleInterop": true,
        "module": "esnext",
        "moduleResolution": "bundler",
        "resolveJsonModule": true,
        "isolatedModules": true,
        "jsx": "preserve",
        "incremental": true,
        "plugins": [
            {
                "name": "next"
            }
        ],
        "baseUrl": ".",
        "paths": {
            "@/*": ["./src/*"]
        }
    },
    "include": ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"],
    "exclude": ["node_modules"]
}
```

### ✅ 1.5 Tailwind Configuration

Update `tailwind.config.js`:

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
        "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
        "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: "#eff6ff",
                    500: "#3b82f6",
                    600: "#2563eb",
                    700: "#1d4ed8",
                },
                secondary: {
                    50: "#f0fdf4",
                    500: "#22c55e",
                    600: "#16a34a",
                    700: "#15803d",
                },
            },
            fontFamily: {
                sans: ["Inter", "system-ui", "sans-serif"],
            },
        },
    },
    plugins: [],
};
```

## 🔧 Phase 2: Core Infrastructure

### ✅ 2.1 Type Definitions

Create `src/types/index.ts`:

```typescript
// User Types
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    role: Role;
    phone?: string;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    slug: string;
}

// Drug Types
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

export interface DrugCategory {
    id: number;
    name: string;
    slug: string;
    description?: string;
    drugs_count?: number;
}

export interface Disease {
    id: number;
    name: string;
    slug: string;
    description?: string;
}

// Cart Types
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

// Order Types
export type OrderStatus = "placed" | "delivering" | "delivered";
export type PaymentStatus = "pending" | "paid" | "failed" | "refunded";

export interface Order {
    id: number;
    user_id: number;
    order_number: string;
    status: OrderStatus;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    formatted_subtotal: string;
    formatted_tax_amount: string;
    formatted_total_amount: string;
    shipping_address: ShippingAddress;
    phone_number: string;
    delivery_notes: string | null;
    payment_status: PaymentStatus;
    placed_at: string;
    delivering_at: string | null;
    delivered_at: string | null;
    items: OrderItem[];
    user: User;
    status_updated_by: User | null;
    created_at: string;
    updated_at: string;
}

export interface OrderItem {
    id: number;
    order_id: number;
    drug_id: number;
    drug_name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    formatted_unit_price: string;
    formatted_total_price: string;
    current_drug?: Drug;
}

export interface ShippingAddress {
    line1: string;
    line2?: string;
    city: string;
    state: string;
    postal_code?: string;
}

// Payment Types
export interface Payment {
    id: number;
    order_id: number;
    user_id: number;
    payment_reference: string;
    paystack_reference: string;
    amount: number;
    formatted_amount: string;
    currency: string;
    status: "pending" | "success" | "failed";
    payment_method?: string;
    access_code?: string;
    created_at: string;
    updated_at: string;
}

// API Response Types
export interface APIResponse<T> {
    status: "success" | "error";
    message: string;
    data: T;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

// Filter Types
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

export interface OrderFilters {
    status?: OrderStatus;
    payment_status?: PaymentStatus;
    from_date?: string;
    to_date?: string;
    per_page?: number;
    page?: number;
}
```

### ✅ 2.2 API Client Setup

Create `src/lib/api/client.ts`:

```typescript
import axios, { AxiosInstance, AxiosResponse } from "axios";
import toast from "react-hot-toast";

const API_BASE_URL =
    process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

// Create axios instance
export const apiClient: AxiosInstance = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    withCredentials: true,
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
    (response: AxiosResponse) => response,
    async (error) => {
        const originalRequest = error.config;

        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            // Clear token and redirect to login
            localStorage.removeItem("auth_token");

            if (typeof window !== "undefined") {
                window.location.href = "/login";
            }

            return Promise.reject(error);
        }

        // Handle other errors
        if (error.response?.status >= 500) {
            toast.error("Server error. Please try again later.");
        } else if (error.response?.status === 422) {
            // Validation errors are handled by individual components
            return Promise.reject(error);
        } else if (error.response?.status === 403) {
            toast.error("You do not have permission to perform this action.");
        } else if (error.response?.status === 404) {
            toast.error("The requested resource was not found.");
        } else if (!error.response) {
            toast.error("Network error. Please check your connection.");
        }

        return Promise.reject(error);
    }
);

export default apiClient;
```

### ✅ 2.3 API Services

Create `src/lib/api/auth.ts`:

```typescript
import { apiClient } from "./client";
import { User, APIResponse } from "@/types";

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
    login: async (data: LoginRequest): Promise<AuthResponse> => {
        const response = await apiClient.post<APIResponse<AuthResponse>>(
            "/login",
            data
        );
        return response.data.data;
    },

    register: async (data: RegisterRequest): Promise<AuthResponse> => {
        const response = await apiClient.post<APIResponse<AuthResponse>>(
            "/register",
            data
        );
        return response.data.data;
    },

    logout: async (): Promise<void> => {
        await apiClient.post("/logout");
    },

    getUser: async (): Promise<User> => {
        const response = await apiClient.get<{ user: User }>("/user");
        return response.data.user;
    },
};
```

Create `src/lib/api/drugs.ts`:

```typescript
import { apiClient } from "./client";
import {
    Drug,
    DrugCategory,
    PaginatedResponse,
    DrugFilters,
    APIResponse,
} from "@/types";

export const drugsAPI = {
    list: async (
        filters: DrugFilters = {}
    ): Promise<PaginatedResponse<Drug>> => {
        const response = await apiClient.get<PaginatedResponse<Drug>>(
            "/drugs",
            {
                params: filters,
            }
        );
        return response.data;
    },

    search: async (query: string): Promise<PaginatedResponse<Drug>> => {
        const response = await apiClient.get<PaginatedResponse<Drug>>(
            "/drugs/search",
            {
                params: { q: query },
            }
        );
        return response.data;
    },

    getBySlug: async (slug: string): Promise<Drug> => {
        const response = await apiClient.get<APIResponse<Drug>>(
            `/drugs/${slug}`
        );
        return response.data.data;
    },

    getCategories: async (): Promise<DrugCategory[]> => {
        const response = await apiClient.get<APIResponse<DrugCategory[]>>(
            "/drugs/categories"
        );
        return response.data.data;
    },

    getByCategory: async (
        categorySlug: string,
        filters: DrugFilters = {}
    ): Promise<PaginatedResponse<Drug>> => {
        const response = await apiClient.get<PaginatedResponse<Drug>>(
            `/drugs/category/${categorySlug}`,
            {
                params: filters,
            }
        );
        return response.data;
    },
};
```

## 🛒 Phase 3: Authentication System

### ✅ 3.1 Authentication Store

Create `src/stores/authStore.ts`:

```typescript
import { create } from "zustand";
import { persist } from "zustand/middleware";
import { authAPI, LoginRequest, RegisterRequest } from "@/lib/api/auth";
import { User } from "@/types";
import toast from "react-hot-toast";

interface AuthState {
    user: User | null;
    token: string | null;
    isAuthenticated: boolean;
    isAdmin: boolean;
    isLoading: boolean;

    // Actions
    login: (credentials: LoginRequest) => Promise<void>;
    register: (data: RegisterRequest) => Promise<void>;
    logout: () => void;
    refreshUser: () => Promise<void>;
    initialize: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set, get) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            isAdmin: false,
            isLoading: false,

            login: async (credentials: LoginRequest) => {
                set({ isLoading: true });
                try {
                    const response = await authAPI.login(credentials);
                    const { user, token } = response;

                    localStorage.setItem("auth_token", token);

                    set({
                        user,
                        token,
                        isAuthenticated: true,
                        isAdmin: user.role.slug === "admin",
                        isLoading: false,
                    });

                    toast.success(`Welcome back, ${user.name}!`);
                } catch (error: any) {
                    set({ isLoading: false });
                    const message =
                        error.response?.data?.message || "Login failed";
                    toast.error(message);
                    throw error;
                }
            },

            register: async (data: RegisterRequest) => {
                set({ isLoading: true });
                try {
                    const response = await authAPI.register(data);
                    const { user, token } = response;

                    localStorage.setItem("auth_token", token);

                    set({
                        user,
                        token,
                        isAuthenticated: true,
                        isAdmin: user.role.slug === "admin",
                        isLoading: false,
                    });

                    toast.success(`Welcome to Health Nexus, ${user.name}!`);
                } catch (error: any) {
                    set({ isLoading: false });
                    const message =
                        error.response?.data?.message || "Registration failed";
                    toast.error(message);
                    throw error;
                }
            },

            logout: () => {
                localStorage.removeItem("auth_token");
                authAPI.logout().catch(() => {}); // Silent fail for API call

                set({
                    user: null,
                    token: null,
                    isAuthenticated: false,
                    isAdmin: false,
                });

                toast.success("Logged out successfully");
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

            initialize: () => {
                const token = localStorage.getItem("auth_token");
                if (token) {
                    set({ token });
                    get().refreshUser();
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

### ✅ 3.2 Authentication Components

Create `src/components/auth/LoginForm.tsx`:

```typescript
"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/stores/authStore";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import Link from "next/link";

const loginSchema = z.object({
    email: z.string().email("Invalid email address"),
    password: z.string().min(6, "Password must be at least 6 characters"),
});

type LoginForm = z.infer<typeof loginSchema>;

export function LoginForm() {
    const router = useRouter();
    const { login, isLoading } = useAuthStore();
    const [showPassword, setShowPassword] = useState(false);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<LoginForm>({
        resolver: zodResolver(loginSchema),
    });

    const onSubmit = async (data: LoginForm) => {
        try {
            await login(data);
            router.push("/");
        } catch (error) {
            // Error is handled by the store
        }
    };

    return (
        <div className="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
            <div className="text-center mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Sign In</h1>
                <p className="text-gray-600 mt-2">
                    Welcome back to Health Nexus
                </p>
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                <div>
                    <label
                        htmlFor="email"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Email Address
                    </label>
                    <Input
                        id="email"
                        type="email"
                        {...register("email")}
                        error={errors.email?.message}
                        placeholder="Enter your email"
                        className="mt-1"
                    />
                </div>

                <div>
                    <label
                        htmlFor="password"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Password
                    </label>
                    <div className="mt-1 relative">
                        <Input
                            id="password"
                            type={showPassword ? "text" : "password"}
                            {...register("password")}
                            error={errors.password?.message}
                            placeholder="Enter your password"
                        />
                        <button
                            type="button"
                            className="absolute inset-y-0 right-0 pr-3 flex items-center"
                            onClick={() => setShowPassword(!showPassword)}
                        >
                            {showPassword ? (
                                <EyeSlashIcon className="h-5 w-5 text-gray-400" />
                            ) : (
                                <EyeIcon className="h-5 w-5 text-gray-400" />
                            )}
                        </button>
                    </div>
                </div>

                <Button type="submit" isLoading={isLoading} className="w-full">
                    Sign In
                </Button>
            </form>

            <div className="mt-6 text-center">
                <p className="text-sm text-gray-600">
                    Don't have an account?{" "}
                    <Link
                        href="/register"
                        className="font-medium text-primary-600 hover:text-primary-500"
                    >
                        Sign up
                    </Link>
                </p>
            </div>
        </div>
    );
}
```

## 🏪 Phase 4: Drug Catalog Implementation

### ✅ 4.1 Drug Store

Create `src/stores/drugStore.ts`:

```typescript
import { create } from "zustand";
import { drugsAPI } from "@/lib/api/drugs";
import { Drug, DrugCategory, DrugFilters, PaginatedResponse } from "@/types";
import toast from "react-hot-toast";

interface DrugState {
    // Data
    drugs: Drug[];
    categories: DrugCategory[];
    currentDrug: Drug | null;

    // UI State
    loading: boolean;
    error: string | null;

    // Pagination
    pagination: PaginatedResponse<Drug>["meta"] | null;

    // Filters
    filters: DrugFilters;

    // Actions
    fetchDrugs: (filters?: DrugFilters) => Promise<void>;
    fetchCategories: () => Promise<void>;
    fetchDrugBySlug: (slug: string) => Promise<void>;
    searchDrugs: (query: string) => Promise<void>;
    setFilters: (filters: Partial<DrugFilters>) => void;
    clearFilters: () => void;
    loadMore: () => Promise<void>;
}

export const useDrugStore = create<DrugState>((set, get) => ({
    // Initial state
    drugs: [],
    categories: [],
    currentDrug: null,
    loading: false,
    error: null,
    pagination: null,
    filters: {},

    fetchDrugs: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const mergedFilters = { ...get().filters, ...filters };
            const response = await drugsAPI.list(mergedFilters);

            set({
                drugs: response.data,
                pagination: response.meta,
                filters: mergedFilters,
                loading: false,
            });
        } catch (error: any) {
            set({
                error: error.response?.data?.message || "Failed to fetch drugs",
                loading: false,
            });
            toast.error("Failed to load drugs");
        }
    },

    fetchCategories: async () => {
        try {
            const categories = await drugsAPI.getCategories();
            set({ categories });
        } catch (error) {
            console.error("Failed to fetch categories:", error);
            toast.error("Failed to load categories");
        }
    },

    fetchDrugBySlug: async (slug: string) => {
        set({ loading: true, error: null });
        try {
            const drug = await drugsAPI.getBySlug(slug);
            set({ currentDrug: drug, loading: false });
        } catch (error: any) {
            set({
                error: error.response?.data?.message || "Drug not found",
                loading: false,
                currentDrug: null,
            });
            toast.error("Drug not found");
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
        } catch (error: any) {
            set({
                error: error.response?.data?.message || "Search failed",
                loading: false,
            });
            toast.error("Search failed");
        }
    },

    setFilters: (newFilters: Partial<DrugFilters>) => {
        const filters = { ...get().filters, ...newFilters, page: 1 };
        set({ filters });
        get().fetchDrugs(filters);
    },

    clearFilters: () => {
        set({ filters: {} });
        get().fetchDrugs({});
    },

    loadMore: async () => {
        const { pagination, filters } = get();
        if (!pagination || pagination.current_page >= pagination.last_page) {
            return;
        }

        try {
            const nextPage = pagination.current_page + 1;
            const response = await drugsAPI.list({
                ...filters,
                page: nextPage,
            });

            set((state) => ({
                drugs: [...state.drugs, ...response.data],
                pagination: response.meta,
            }));
        } catch (error: any) {
            toast.error("Failed to load more drugs");
        }
    },
}));
```

### ✅ 4.2 Drug Components

Create `src/components/drugs/DrugCard.tsx`:

```typescript
"use client";

import { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { Drug } from "@/types";
import { useCartStore } from "@/stores/cartStore";
import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import { ShoppingCartIcon, EyeIcon } from "@heroicons/react/24/outline";

interface DrugCardProps {
    drug: Drug;
}

export function DrugCard({ drug }: DrugCardProps) {
    const { addItem, loading } = useCartStore();
    const [quantity, setQuantity] = useState(1);

    const handleAddToCart = async () => {
        try {
            await addItem(drug.id, quantity);
        } catch (error) {
            // Error is handled by the store
        }
    };

    return (
        <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200">
            {/* Drug Image */}
            <div className="relative h-48 bg-gray-200">
                {drug.image ? (
                    <Image
                        src={drug.image}
                        alt={drug.name}
                        fill
                        className="object-cover"
                        sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                    />
                ) : (
                    <div className="flex items-center justify-center h-full">
                        <div className="text-gray-400 text-4xl">💊</div>
                    </div>
                )}

                {/* Status Badge */}
                <div className="absolute top-2 right-2">
                    {!drug.is_available ? (
                        <Badge variant="destructive">Out of Stock</Badge>
                    ) : drug.stock <= 10 ? (
                        <Badge variant="warning">Low Stock</Badge>
                    ) : null}
                </div>
            </div>

            {/* Drug Info */}
            <div className="p-4">
                <div className="mb-2">
                    <h3 className="font-semibold text-lg text-gray-900 line-clamp-2">
                        {drug.name}
                    </h3>
                    {drug.categories.length > 0 && (
                        <p className="text-sm text-gray-500">
                            {drug.categories[0].name}
                        </p>
                    )}
                </div>

                {drug.description && (
                    <p className="text-gray-600 text-sm mb-3 line-clamp-2">
                        {drug.description}
                    </p>
                )}

                {/* Price */}
                <div className="flex items-center justify-between mb-4">
                    <span className="text-2xl font-bold text-primary-600">
                        {drug.formatted_price}
                    </span>
                    <span className="text-sm text-gray-500">
                        Stock: {drug.stock}
                    </span>
                </div>

                {/* Actions */}
                <div className="flex space-x-2">
                    <Link href={`/drugs/${drug.slug}`} className="flex-1">
                        <Button variant="outline" className="w-full" size="sm">
                            <EyeIcon className="h-4 w-4 mr-2" />
                            View
                        </Button>
                    </Link>

                    {drug.is_available && (
                        <div className="flex space-x-1">
                            <select
                                value={quantity}
                                onChange={(e) =>
                                    setQuantity(Number(e.target.value))
                                }
                                className="text-sm border border-gray-300 rounded px-2 py-1"
                                disabled={loading}
                            >
                                {Array.from(
                                    { length: Math.min(drug.stock, 10) },
                                    (_, i) => (
                                        <option key={i + 1} value={i + 1}>
                                            {i + 1}
                                        </option>
                                    )
                                )}
                            </select>

                            <Button
                                onClick={handleAddToCart}
                                isLoading={loading}
                                size="sm"
                                className="px-3"
                            >
                                <ShoppingCartIcon className="h-4 w-4" />
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
```

## 🛒 Phase 5: Shopping Cart Implementation

### ✅ 5.1 Cart Store

Create `src/stores/cartStore.ts`:

```typescript
import { create } from "zustand";
import { cartAPI } from "@/lib/api/cart";
import { Cart, CartItem } from "@/types";
import toast from "react-hot-toast";

interface CartState {
    cart: Cart | null;
    loading: boolean;
    error: string | null;
    isOpen: boolean;

    // Actions
    fetchCart: () => Promise<void>;
    addItem: (drugId: number, quantity: number) => Promise<void>;
    updateItem: (itemId: number, quantity: number) => Promise<void>;
    removeItem: (itemId: number) => Promise<void>;
    clearCart: () => Promise<void>;
    validateCart: () => Promise<any>;

    // UI Actions
    openCart: () => void;
    closeCart: () => void;
    toggleCart: () => void;
}

export const useCartStore = create<CartState>((set, get) => ({
    cart: null,
    loading: false,
    error: null,
    isOpen: false,

    fetchCart: async () => {
        set({ loading: true, error: null });
        try {
            const cart = await cartAPI.get();
            set({ cart, loading: false });
        } catch (error: any) {
            set({
                error: error.response?.data?.message || "Failed to fetch cart",
                loading: false,
            });
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

    // UI Actions
    openCart: () => set({ isOpen: true }),
    closeCart: () => set({ isOpen: false }),
    toggleCart: () => set((state) => ({ isOpen: !state.isOpen })),
}));
```

## 📋 Phase 6: Order Management

### ✅ 6.1 Order Store Implementation

Create `src/stores/orderStore.ts`:

```typescript
import { create } from "zustand";
import { ordersAPI, CreateOrderRequest } from "@/lib/api/orders";
import { Order, OrderFilters, PaginatedResponse } from "@/types";
import toast from "react-hot-toast";

interface OrderState {
    orders: Order[];
    currentOrder: Order | null;
    loading: boolean;
    error: string | null;
    pagination: PaginatedResponse<Order>["meta"] | null;

    // Actions
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
    pagination: null,

    fetchOrders: async (filters = {}) => {
        set({ loading: true, error: null });
        try {
            const response = await ordersAPI.list(filters);
            set({
                orders: response.data,
                pagination: response.meta,
                loading: false,
            });
        } catch (error: any) {
            set({
                error:
                    error.response?.data?.message || "Failed to fetch orders",
                loading: false,
            });
            toast.error("Failed to load orders");
        }
    },

    fetchOrder: async (id: number) => {
        set({ loading: true, error: null });
        try {
            const order = await ordersAPI.getById(id);
            set({ currentOrder: order, loading: false });
        } catch (error: any) {
            set({
                error: error.response?.data?.message || "Failed to fetch order",
                loading: false,
            });
            toast.error("Order not found");
        }
    },

    createOrder: async (data: CreateOrderRequest) => {
        try {
            const order = await ordersAPI.create(data);

            // Add to orders list
            set((state) => ({
                orders: [order, ...state.orders],
                currentOrder: order,
            }));

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

            // Update order in list
            set((state) => ({
                orders: state.orders.map((o) => (o.id === id ? order : o)),
                currentOrder:
                    state.currentOrder?.id === id ? order : state.currentOrder,
            }));

            toast.success("Delivery confirmed successfully");
        } catch (error: any) {
            const message =
                error.response?.data?.message || "Failed to confirm delivery";
            toast.error(message);
            throw error;
        }
    },
}));
```

## 💳 Phase 7: Payment Integration

### ✅ 7.1 Payment Service

Create `src/lib/services/paymentService.ts`:

```typescript
import { paymentsAPI } from "@/lib/api/payments";

// PayStack types
declare global {
    interface Window {
        PaystackPop: {
            setup: (config: PaystackConfig) => {
                openIframe: () => void;
            };
        };
    }
}

interface PaystackConfig {
    key: string;
    email: string;
    amount: number;
    ref: string;
    callback: (response: PaystackResponse) => void;
    onClose: () => void;
}

interface PaystackResponse {
    reference: string;
    status: string;
    message: string;
    transaction: string;
    trxref: string;
}

export interface PaymentInitialization {
    payment_id: number;
    payment_reference: string;
    authorization_url: string;
    access_code: string;
    amount: string;
}

export class PaymentService {
    private static isPaystackLoaded(): boolean {
        return (
            typeof window !== "undefined" && window.PaystackPop !== undefined
        );
    }

    static async initializePayment(
        orderId: number
    ): Promise<PaymentInitialization> {
        try {
            return await paymentsAPI.initialize(orderId);
        } catch (error: any) {
            throw new Error(
                error.response?.data?.message || "Failed to initialize payment"
            );
        }
    }

    static async processPayment(
        paymentData: PaymentInitialization,
        userEmail: string,
        onSuccess: (reference: string) => void,
        onClose: () => void
    ): Promise<void> {
        if (!this.isPaystackLoaded()) {
            throw new Error(
                "PayStack library not loaded. Please refresh the page."
            );
        }

        const publicKey = process.env.NEXT_PUBLIC_PAYSTACK_PUBLIC_KEY;
        if (!publicKey) {
            throw new Error("PayStack public key not configured");
        }

        // Extract amount as number (remove currency symbol and convert)
        const amountStr = paymentData.amount.replace(/[₵,]/g, "");
        const amount = parseFloat(amountStr) * 100; // Convert to kobo

        const handler = window.PaystackPop.setup({
            key: publicKey,
            email: userEmail,
            amount: amount,
            ref: paymentData.payment_reference,
            callback: (response: PaystackResponse) => {
                if (response.status === "success") {
                    onSuccess(response.reference);
                }
            },
            onClose: onClose,
        });

        handler.openIframe();
    }

    static async calculateFees(amount: number) {
        try {
            return await paymentsAPI.calculateFees(amount);
        } catch (error: any) {
            throw new Error(
                error.response?.data?.message || "Failed to calculate fees"
            );
        }
    }

    static loadPaystackScript(): Promise<void> {
        return new Promise((resolve, reject) => {
            if (this.isPaystackLoaded()) {
                resolve();
                return;
            }

            const script = document.createElement("script");
            script.src = "https://js.paystack.co/v1/inline.js";
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Failed to load PayStack"));
            document.head.appendChild(script);
        });
    }
}
```

## 🔐 Phase 8: Admin Dashboard

### ✅ 8.1 Admin Store

Create `src/stores/adminStore.ts`:

```typescript
import { create } from "zustand";
import { adminAPI } from "@/lib/api/admin";
import { Drug, Order } from "@/types";
import toast from "react-hot-toast";

interface AdminState {
    // Inventory
    inventory: Drug[];
    inventoryLoading: boolean;

    // Orders
    orders: Order[];
    ordersLoading: boolean;

    // Analytics
    analytics: any;
    analyticsLoading: boolean;

    // General
    error: string | null;

    // Actions
    fetchInventory: (filters?: any) => Promise<void>;
    fetchOrders: (filters?: any) => Promise<void>;
    fetchAnalytics: () => Promise<void>;
    updateStock: (drugId: number, data: any) => Promise<void>;
    updateOrderStatus: (
        orderId: number,
        status: string,
        notes?: string
    ) => Promise<void>;
}

export const useAdminStore = create<AdminState>((set, get) => ({
    // Initial state
    inventory: [],
    inventoryLoading: false,
    orders: [],
    ordersLoading: false,
    analytics: null,
    analyticsLoading: false,
    error: null,

    fetchInventory: async (filters = {}) => {
        set({ inventoryLoading: true, error: null });
        try {
            const response = await adminAPI.inventory.list(filters);
            set({ inventory: response.data, inventoryLoading: false });
        } catch (error: any) {
            set({
                error:
                    error.response?.data?.message ||
                    "Failed to fetch inventory",
                inventoryLoading: false,
            });
            toast.error("Failed to load inventory");
        }
    },

    fetchOrders: async (filters = {}) => {
        set({ ordersLoading: true, error: null });
        try {
            const response = await adminAPI.orders.list(filters);
            set({ orders: response.data, ordersLoading: false });
        } catch (error: any) {
            set({
                error:
                    error.response?.data?.message || "Failed to fetch orders",
                ordersLoading: false,
            });
            toast.error("Failed to load orders");
        }
    },

    fetchAnalytics: async () => {
        set({ analyticsLoading: true, error: null });
        try {
            const analytics = await adminAPI.analytics.getDashboard();
            set({ analytics, analyticsLoading: false });
        } catch (error: any) {
            set({
                error:
                    error.response?.data?.message ||
                    "Failed to fetch analytics",
                analyticsLoading: false,
            });
            toast.error("Failed to load analytics");
        }
    },

    updateStock: async (drugId: number, data: any) => {
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

    updateOrderStatus: async (
        orderId: number,
        status: string,
        notes?: string
    ) => {
        try {
            await adminAPI.orders.updateStatus(orderId, { status, notes });
            // Refresh orders
            get().fetchOrders();
            toast.success("Order status updated successfully");
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

## 🎨 Phase 9: UI Components

### ✅ 9.1 Base UI Components

Create `src/components/ui/Button.tsx`:

```typescript
import React from "react";
import { clsx } from "clsx";

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: "primary" | "secondary" | "outline" | "destructive";
    size?: "sm" | "md" | "lg";
    isLoading?: boolean;
    children: React.ReactNode;
}

export function Button({
    variant = "primary",
    size = "md",
    isLoading = false,
    className,
    disabled,
    children,
    ...props
}: ButtonProps) {
    const baseClasses =
        "inline-flex items-center justify-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed";

    const variants = {
        primary:
            "bg-primary-600 hover:bg-primary-700 text-white focus:ring-primary-500",
        secondary:
            "bg-secondary-600 hover:bg-secondary-700 text-white focus:ring-secondary-500",
        outline:
            "border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 focus:ring-primary-500",
        destructive:
            "bg-red-600 hover:bg-red-700 text-white focus:ring-red-500",
    };

    const sizes = {
        sm: "px-3 py-1.5 text-sm",
        md: "px-4 py-2 text-sm",
        lg: "px-6 py-3 text-base",
    };

    return (
        <button
            className={clsx(
                baseClasses,
                variants[variant],
                sizes[size],
                className
            )}
            disabled={disabled || isLoading}
            {...props}
        >
            {isLoading && (
                <svg
                    className="-ml-1 mr-2 h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth="4"
                    />
                    <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            )}
            {children}
        </button>
    );
}
```

## 🧪 Phase 10: Testing Implementation

### ✅ 10.1 Test Setup

Create `jest.config.js`:

```javascript
const nextJest = require("next/jest");

const createJestConfig = nextJest({
    dir: "./",
});

const customJestConfig = {
    setupFilesAfterEnv: ["<rootDir>/jest.setup.js"],
    moduleNameMapping: {
        "^@/(.*)$": "<rootDir>/src/$1",
    },
    testEnvironment: "jest-environment-jsdom",
};

module.exports = createJestConfig(customJestConfig);
```

Create `jest.setup.js`:

```javascript
import "@testing-library/jest-dom";

// Mock Next.js router
jest.mock("next/navigation", () => ({
    useRouter() {
        return {
            push: jest.fn(),
            replace: jest.fn(),
            prefetch: jest.fn(),
        };
    },
    useSearchParams() {
        return new URLSearchParams();
    },
    usePathname() {
        return "";
    },
}));

// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
};
global.localStorage = localStorageMock;
```

### ✅ 10.2 Component Tests

Create `src/components/__tests__/DrugCard.test.tsx`:

```typescript
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DrugCard } from "@/components/drugs/DrugCard";
import { useCartStore } from "@/stores/cartStore";

// Mock the cart store
jest.mock("@/stores/cartStore");
const mockedUseCartStore = useCartStore as jest.MockedFunction<
    typeof useCartStore
>;

const mockDrug = {
    id: 1,
    name: "Paracetamol 500mg",
    slug: "paracetamol-500mg",
    description: "Pain relief medication",
    price: 250.0,
    formatted_price: "₵250.00",
    stock: 50,
    expiry_date: "2025-12-31",
    image: "/test-image.jpg",
    status: "active" as const,
    is_available: true,
    is_expired: false,
    categories: [{ id: 1, name: "Analgesics", slug: "analgesics" }],
    diseases: [],
    created_at: "2024-07-20T10:00:00.000000Z",
    updated_at: "2024-07-24T10:00:00.000000Z",
};

describe("DrugCard", () => {
    beforeEach(() => {
        mockedUseCartStore.mockReturnValue({
            addItem: jest.fn(),
            loading: false,
        } as any);
    });

    test("renders drug information correctly", () => {
        render(<DrugCard drug={mockDrug} />);

        expect(screen.getByText("Paracetamol 500mg")).toBeInTheDocument();
        expect(screen.getByText("₵250.00")).toBeInTheDocument();
        expect(screen.getByText("Stock: 50")).toBeInTheDocument();
        expect(screen.getByText("Analgesics")).toBeInTheDocument();
    });

    test("shows out of stock badge when drug is not available", () => {
        const unavailableDrug = { ...mockDrug, is_available: false, stock: 0 };
        render(<DrugCard drug={unavailableDrug} />);

        expect(screen.getByText("Out of Stock")).toBeInTheDocument();
    });

    test("calls addItem when add to cart button is clicked", async () => {
        const mockAddItem = jest.fn();
        mockedUseCartStore.mockReturnValue({
            addItem: mockAddItem,
            loading: false,
        } as any);

        render(<DrugCard drug={mockDrug} />);

        const addButton = screen.getByRole("button", { name: /cart/i });
        fireEvent.click(addButton);

        await waitFor(() => {
            expect(mockAddItem).toHaveBeenCalledWith(1, 1);
        });
    });

    test("disables add to cart when loading", () => {
        mockedUseCartStore.mockReturnValue({
            addItem: jest.fn(),
            loading: true,
        } as any);

        render(<DrugCard drug={mockDrug} />);

        const quantitySelect = screen.getByRole("combobox");
        expect(quantitySelect).toBeDisabled();
    });
});
```

## 📱 Phase 11: Responsive Layout

### ✅ 11.1 Main Layout Component

Create `src/components/layout/MainLayout.tsx`:

```typescript
"use client";

import { useEffect } from "react";
import { Toaster } from "react-hot-toast";
import { useAuthStore } from "@/stores/authStore";
import { useCartStore } from "@/stores/cartStore";
import { Header } from "./Header";
import { Footer } from "./Footer";
import { CartSidebar } from "@/components/cart/CartSidebar";

interface MainLayoutProps {
    children: React.ReactNode;
}

export function MainLayout({ children }: MainLayoutProps) {
    const { initialize } = useAuthStore();
    const { fetchCart } = useCartStore();

    useEffect(() => {
        // Initialize auth on app start
        initialize();

        // Fetch cart
        fetchCart();
    }, [initialize, fetchCart]);

    return (
        <>
            <div className="min-h-screen bg-gray-50 flex flex-col">
                <Header />

                <main className="flex-1">{children}</main>

                <Footer />
            </div>

            {/* Cart Sidebar */}
            <CartSidebar />

            {/* Toast Notifications */}
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 4000,
                    style: {
                        background: "#363636",
                        color: "#fff",
                    },
                    success: {
                        duration: 3000,
                        iconTheme: {
                            primary: "#4ade80",
                            secondary: "#fff",
                        },
                    },
                    error: {
                        duration: 5000,
                        iconTheme: {
                            primary: "#ef4444",
                            secondary: "#fff",
                        },
                    },
                }}
            />
        </>
    );
}
```

## 🚀 Phase 12: Performance Optimization

### ✅ 12.1 Image Optimization

Create `next.config.js`:

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
    images: {
        domains: [
            "localhost",
            "api.health-nexus.com",
            // Add your backend domain
        ],
        formats: ["image/webp", "image/avif"],
    },

    // Enable compression
    compress: true,

    // Enable PWA features
    experimental: {
        optimizeCss: true,
    },
};

module.exports = nextConfig;
```

### ✅ 12.2 Performance Monitoring

Create `src/lib/utils/performance.ts`:

```typescript
export class PerformanceMonitor {
    private static measurements: Map<string, number> = new Map();

    static startMeasurement(key: string): void {
        this.measurements.set(key, performance.now());
    }

    static endMeasurement(key: string): number {
        const start = this.measurements.get(key);
        if (!start) {
            console.warn(`No measurement started for key: ${key}`);
            return 0;
        }

        const end = performance.now();
        const duration = end - start;

        this.measurements.delete(key);

        // Log slow operations in development
        if (process.env.NODE_ENV === "development" && duration > 1000) {
            console.warn(
                `Slow operation detected: ${key} took ${duration.toFixed(2)}ms`
            );
        }

        return duration;
    }

    static measureAsync<T>(key: string, fn: () => Promise<T>): Promise<T> {
        return new Promise(async (resolve, reject) => {
            this.startMeasurement(key);
            try {
                const result = await fn();
                this.endMeasurement(key);
                resolve(result);
            } catch (error) {
                this.endMeasurement(key);
                reject(error);
            }
        });
    }
}
```

## 📦 Phase 13: Build and Deployment

### ✅ 13.1 Build Scripts

Update `package.json`:

```json
{
    "scripts": {
        "dev": "next dev",
        "build": "next build",
        "start": "next start",
        "lint": "next lint",
        "lint:fix": "next lint --fix",
        "type-check": "tsc --noEmit",
        "test": "jest",
        "test:watch": "jest --watch",
        "test:coverage": "jest --coverage",
        "build:analyze": "ANALYZE=true next build"
    }
}
```

### ✅ 13.2 Docker Configuration

Create `Dockerfile`:

```dockerfile
FROM node:18-alpine AS base

# Install dependencies only when needed
FROM base AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# Rebuild the source code only when needed
FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

ENV NEXT_TELEMETRY_DISABLED 1

RUN npm run build

# Production image, copy all the files and run next
FROM base AS runner
WORKDIR /app

ENV NODE_ENV production
ENV NEXT_TELEMETRY_DISABLED 1

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

COPY --from=builder /app/public ./public

COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

USER nextjs

EXPOSE 3000

ENV PORT 3000

CMD ["node", "server.js"]
```

## ✅ Final Integration Checklist

### Authentication & Security

-   [ ] JWT token management with automatic refresh
-   [ ] Protected routes for authenticated users
-   [ ] Admin-only routes with role-based access
-   [ ] Secure API communication with HTTPS
-   [ ] Input validation and sanitization

### E-commerce Features

-   [ ] Drug catalog with search and filtering
-   [ ] Shopping cart with session/user persistence
-   [ ] Order creation and tracking system
-   [ ] PayStack payment integration
-   [ ] Order status updates and delivery confirmation

### Admin Dashboard

-   [ ] Inventory management with stock tracking
-   [ ] Order processing workflow
-   [ ] Analytics and reporting
-   [ ] Low stock alerts
-   [ ] Bulk operations for inventory

### User Experience

-   [ ] Responsive design for mobile/tablet/desktop
-   [ ] Loading states and error handling
-   [ ] Toast notifications for user feedback
-   [ ] Accessible components with ARIA labels
-   [ ] SEO optimization with meta tags

### Performance

-   [ ] Image optimization with Next.js Image component
-   [ ] Code splitting and lazy loading
-   [ ] API request caching and optimization
-   [ ] Bundle size analysis and optimization
-   [ ] Performance monitoring

### Testing

-   [ ] Unit tests for components and utilities
-   [ ] Integration tests for API services
-   [ ] E2E tests for critical user flows
-   [ ] Accessibility testing
-   [ ] Performance testing

### Deployment

-   [ ] Environment configuration for production
-   [ ] Docker containerization
-   [ ] CI/CD pipeline setup
-   [ ] Error tracking and monitoring
-   [ ] Performance monitoring in production

## 🎯 Success Metrics

### Technical Metrics

-   **Page Load Time**: < 3 seconds on 3G
-   **First Contentful Paint**: < 1.5 seconds
-   **Bundle Size**: < 250KB gzipped
-   **API Response Time**: < 500ms average
-   **Test Coverage**: > 80%

### Business Metrics

-   **Cart Abandonment Rate**: < 30%
-   **Order Completion Rate**: > 90%
-   **Payment Success Rate**: > 95%
-   **User Engagement**: Average session > 5 minutes
-   **Mobile Usage**: > 60% of traffic

## 📞 Support and Maintenance

### Monitoring Setup

-   Error tracking with Sentry or similar
-   Performance monitoring with Web Vitals
-   API monitoring and alerting
-   User analytics and behavior tracking

### Regular Maintenance Tasks

-   Security updates for dependencies
-   Performance optimization reviews
-   API endpoint monitoring
-   Database query optimization
-   User feedback collection and analysis

This comprehensive implementation guide provides everything needed to build a production-ready React/Next.js frontend that seamlessly integrates with the Health Nexus E-Pharmacy Laravel backend.
