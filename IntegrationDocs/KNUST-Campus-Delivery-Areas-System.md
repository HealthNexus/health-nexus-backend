# KNUST Campus Delivery Areas System

## 🎯 Overview

Your delivery system has been updated to use a **database-driven approach** with delivery areas specifically tailored for **KNUST campus and surrounding areas in Kumasi, Ghana**.

## 🗃️ Database Structure

### **DeliveryArea Model**

```php
// Fields
- id (Primary Key)
- code (Unique identifier, e.g., 'knust_campus')
- name (Display name, e.g., 'KNUST Campus')
- description (Area description)
- base_fee (Delivery fee in Ghana Cedis)
- is_active (Can temporarily disable areas)
- sort_order (Order for frontend display)
- landmarks (JSON array of notable landmarks)
- created_at, updated_at
```

### **Relationships**

```php
// DeliveryArea -> Orders
DeliveryArea::orders() // Get all orders for this area

// Order -> DeliveryArea
Order::deliveryAreaDetails() // Get area details for order
```

## 🏫 KNUST Campus Areas (Seeded Data)

### **Campus Areas**

1. **KNUST Campus** (`knust_campus`) - ₵1.50

    - Main university campus including all halls and departments
    - Landmarks: KNUST Main Gate, Great Hall, University Hospital, Library

2. **Ayeduase** (`ayeduase`) - ₵2.00

    - Residential area adjacent to KNUST campus
    - Landmarks: Ayeduase Gate, STC Station, KNUST Hospital

3. **Bomso** (`bomso`) - ₵2.50
    - Popular student residential area near KNUST
    - Landmarks: Bomso Market, Bomso Roundabout, St. Monica's College

### **Student Areas**

4. **Kentinkrono** (`kentinkrono`) - ₵3.00

    - Residential area popular with KNUST students
    - Landmarks: Kentinkrono Station, Royal Golf Club, Star Oil

5. **Daban** (`daban`) - ₵2.80

    - Student accommodation area near KNUST
    - Landmarks: Daban Junction, Pentagon Hostel Area, Goil Station

6. **Anloga Junction** (`anloga`) - ₵2.50
    - Major junction area with student hostels
    - Landmarks: Anloga Junction, VIP Station, Shell Station

### **Extended Areas**

7. **Kotei** (`kotei`) - ₵3.50
    - Residential area south of KNUST
8. **Ayigya** (`ayigya`) - ₵4.00

    - Town near KNUST with student accommodation

9. **Forest Hill** (`forest`) - ₵4.50

    - Upscale residential area near KNUST

10. **Maxima** (`maxima`) - ₵3.50

    - Commercial and residential area

11. **North Campus Extension** (`north_campus`) - ₵3.00

    - Extended campus area and new developments

12. **Atasomanso** (`atasomanso`) - ₵3.80
    - Residential area near KNUST

## 💰 Pricing Structure (Ghana Cedis)

### **Delivery Fees**

-   **Free delivery**: Orders ≥ ₵100
-   **50% discount**: Orders ≥ ₵50
-   **Base fees**: Range from ₵1.50 (campus) to ₵4.50 (upscale areas)

### **Delivery Time**

-   **Standard**: 1-2 business days for all areas

## 🚀 API Endpoints

### **Public Endpoints**

```bash
# Get all delivery areas
GET /api/delivery/areas

# Calculate delivery fee
POST /api/delivery/calculate-fee
{
  "delivery_area": "knust_campus",
  "order_value": 75.50
}
```

### **Admin Endpoints**

```bash
# Get delivery statistics
GET /api/admin/delivery/statistics

# Get optimized delivery routes
GET /api/admin/delivery/routes

# Get orders by area
GET /api/admin/delivery/area/knust_campus

# Create new delivery area
POST /api/admin/delivery/areas
{
  "code": "new_area",
  "name": "New Area",
  "description": "Description",
  "base_fee": 3.00,
  "landmarks": ["Landmark 1", "Landmark 2"]
}

# Update delivery area
PUT /api/admin/delivery/areas/knust_campus
{
  "base_fee": 2.00,
  "description": "Updated description"
}

# Toggle area status
PATCH /api/admin/delivery/areas/knust_campus/toggle
```

## 🎯 Frontend Integration

### **Get Delivery Areas**

```javascript
const getDeliveryAreas = async () => {
    const response = await fetch("/api/delivery/areas");
    const result = await response.json();

    return result.data; // Array of delivery areas
};

// Example response:
[
    {
        code: "knust_campus",
        name: "KNUST Campus",
        description: "Main university campus...",
        base_fee: 1.5,
        formatted_fee: "₵1.50",
        landmarks: ["KNUST Main Gate", "Great Hall"],
        is_active: true,
        pending_orders_count: 5,
    },
];
```

### **Calculate Delivery Fee**

```javascript
const calculateDeliveryFee = async (areaCode, orderValue) => {
  const response = await fetch('/api/delivery/calculate-fee', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      delivery_area: areaCode,
      order_value: orderValue
    })
  });

  const result = await response.json();
  return result.data;
};

// Example response:
{
  "delivery_fee": 1.50,
  "estimated_delivery_time": "1-2 business days",
  "formatted_fee": "₵1.50",
  "free_delivery_threshold": 100,
  "discount_threshold": 50,
  "is_free_delivery": false
}
```

### **Create Order with Delivery Area**

```javascript
const createOrder = async (orderData) => {
    const response = await fetch("/api/orders", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            phone_number: "+233....",
            delivery_address: "Room 123, Unity Hall, KNUST",
            delivery_area: "knust_campus",
            landmark: "Near Unity Hall",
            delivery_notes: "Call when you arrive",
            delivery_fee: 1.5,
        }),
    });
};
```

## 🔄 Migration Commands

```bash
# Run the new migration
php artisan migrate

# Seed delivery areas
php artisan db:seed --class=DeliveryAreaSeeder

# Or seed everything including delivery areas
php artisan db:seed
```

## ⚡ Benefits of Database Approach

### **1. Dynamic Management**

-   ✅ Add/remove areas without code changes
-   ✅ Update fees in real-time
-   ✅ Temporarily disable areas
-   ✅ Reorder areas for better UX

### **2. Rich Data**

-   ✅ Detailed area descriptions
-   ✅ Landmark information for delivery
-   ✅ Area-specific statistics
-   ✅ Flexible fee structures

### **3. Admin Features**

-   ✅ Real-time delivery statistics
-   ✅ Route optimization by area
-   ✅ Order management by location
-   ✅ Fee adjustment capabilities

### **4. Scalability**

-   ✅ Easy to add new areas as campus expands
-   ✅ Support for seasonal fee adjustments
-   ✅ Area-specific promotions
-   ✅ Data-driven delivery optimization

## 🎓 KNUST-Specific Features

### **Campus-Friendly**

-   ✅ Lowest fees for on-campus delivery
-   ✅ Hall and department-specific landmarks
-   ✅ Student area focus
-   ✅ Local business integration

### **Kumasi Context**

-   ✅ Real Kumasi area names
-   ✅ Local landmarks everyone knows
-   ✅ Appropriate fee structure for Ghana
-   ✅ Student budget considerations

Your delivery system is now perfectly tailored for KNUST campus and the surrounding Kumasi areas! 🎉
