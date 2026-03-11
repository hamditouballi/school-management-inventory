# School Inventory Management System - Complete Documentation

## 🎯 System Overview

A production-ready Laravel 11 inventory management system for schools with comprehensive role-based access control, request workflows, purchase order approval, invoice management, and interactive dashboards with analytics.

## 📊 Implemented Features

### ✅ Core Functionality

- **Role-Based Access Control**: 4 distinct roles with specific permissions
    - `director`: View items, create requests, track own requests
    - `stock_manager`: Manage inventory, approve/fulfill requests, create POs
    - `finance_manager`: Manage invoices, view financial data
    - `hr_manager`: Full system access, approve POs, manage users

- **Inventory Management**
    - Full CRUD operations for 30 pre-seeded items
    - Low-stock threshold tracking with alerts
    - Description and unit tracking
    - Computed `is_low_stock` attribute for real-time monitoring

- **Request Workflow**
    - Multi-item requests creation by any user
    - Status tracking: pending → approved → fulfilled
    - Automatic Bon de Sortie generation on fulfillment
    - Stock deduction with insufficient stock detection
    - Suggestion system for Purchase Orders when stock is low

- **Purchase Order System**
    - HR approval workflow (pending_hr → approved_hr/rejected_hr → ordered)
    - Multi-item POs with supplier tracking
    - Automatic total amount calculation
    - Status-based access control

- **Invoice Management**
    - Link invoices to Purchase Order Items
    - File path support for document uploads
    - Finance manager responsibility tracking

- **Dashboard & Analytics**
    - Interactive Chart.js visualizations
    - Monthly consumption trends (line chart)
    - Department-wise consumption (bar chart)
    - Monthly spending analysis (bar chart)
    - Top 10 consumed items (horizontal bar chart)
    - Low-stock alerts table
    - Real-time statistics cards

### ✅ Security & Authorization

- **Laravel Sanctum Authentication**
    - Token-based API authentication
    - Secure login/logout/me endpoints
    - Session management for web interface

- **Comprehensive Authorization Policies**
    - `ItemPolicy`: View (all), Create/Update/Delete (managers only)
    - `RequestPolicy`: Create (all), View (own or managers), Update/Fulfill (managers)
    - `PurchaseOrderPolicy`: Create (stock_manager), Approve (hr_manager), View (managers)
    - `InvoicePolicy`: Create/Manage (finance_manager, hr_manager)

### ✅ Database Architecture

**15 Tables** with complete relationships:

- `departments` (4 records: Nursery, Primary, Middle/High School, Administration)
- `users` (5 demo users with different roles)
- `items` (30 inventory items with descriptions and thresholds)
- `requests` (6 sample requests with various statuses)
- `request_items` (junction table with quantities)
- `bon_de_sorties` (outgoing slips for fulfilled requests)
- `purchase_orders` (4 sample POs with different statuses)
- `purchase_order_items` (junction table)
- `invoices` (6 sample invoices)
- `notifications` (ready for notification system)
- `personal_access_tokens` (Sanctum tokens)
- `sessions`, `cache`, `jobs`, `password_reset_tokens`

### ✅ API Endpoints (29 routes)

#### Authentication

```
POST   /api/login          - Login and get token
POST   /api/logout         - Logout current user
GET    /api/me             - Get current user details
```

#### Items

```
GET    /api/items          - List all items
POST   /api/items          - Create new item (managers only)
GET    /api/items/{id}     - Get item details
PUT    /api/items/{id}     - Update item (managers only)
DELETE /api/items/{id}     - Delete item (managers only)
```

#### Requests

```
GET    /api/requests                  - List requests (role-filtered)
POST   /api/requests                  - Create multi-item request
GET    /api/requests/{id}             - Get request details
PUT    /api/requests/{id}/status      - Update request status (managers)
POST   /api/requests/{id}/fulfill     - Fulfill and generate Bon de Sortie
```

#### Purchase Orders

```
GET    /api/purchase-orders                 - List all POs
POST   /api/purchase-orders                 - Create PO (stock_manager)
GET    /api/purchase-orders/{id}            - Get PO details
PUT    /api/purchase-orders/{id}            - Update PO
PUT    /api/purchase-orders/{id}/status     - Update status (HR approval)
DELETE /api/purchase-orders/{id}            - Delete PO
```

#### Invoices

```
GET    /api/invoices       - List all invoices
POST   /api/invoices       - Create invoice (finance_manager)
GET    /api/invoices/{id}  - Get invoice details
DELETE /api/invoices/{id}  - Delete invoice
```

#### Statistics & Dashboard

```
GET    /api/stats/dashboard                 - Overview statistics
GET    /api/stats/consumption               - Monthly consumption data
GET    /api/stats/consumption-by-department - Department breakdown
GET    /api/stats/spending                  - Monthly spending trends
GET    /api/stats/top-items                 - Top 10 consumed items
GET    /api/stats/low-stock                 - Low-stock alerts
```

### ✅ Web Interface

- **Responsive Design** with Tailwind CSS
- **Login Page** with demo credentials display
- **Dashboard** with 4 interactive charts and statistics
- **Navigation Menu** (role-based visibility)
- **Session Management** with API token integration
- **Alert Messages** for success/error feedback

## 🚀 Installation & Setup

```bash
# 1. Navigate to project
cd C:\Users\hamditou\school-inventory-system

# 2. Install dependencies (if not already done)
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Run migrations and seed database
php artisan migrate:fresh --seed

# 5. Start development server
php artisan serve
```

## 👥 Demo Credentials

| Username           | Password   | Role            | Department     |
| ------------------ | ---------- | --------------- | -------------- |
| `hr_manager`       | `password` | HR Manager      | Administration |
| `stock_manager`    | `password` | Stock Manager   | Administration |
| `finance_manager`  | `password` | Finance Manager | Administration |
| `director_nursery` | `password` | director        | Nursery        |
| `director_primary` | `password` | director        | Primary        |

## 📦 Demo Data Included

- **30 Inventory Items** with descriptions, prices, units, and low-stock thresholds
- **6 Sample Requests** (3 fulfilled, 1 approved, 2 pending)
- **Multiple Bon de Sorties** for fulfilled requests
- **4 Purchase Orders** (various approval states)
- **6 Invoices** (some linked to PO items)
- **4 Departments** (Nursery, Primary, Middle/High School, Administration)
- **5 Users** across different roles

## 🎯 Business Workflows

### 1. Request → Fulfillment Flow

```
1. director creates request with multiple items
   POST /api/requests
   {
     "items": [
       {"item_id": 1, "quantity_requested": 10},
       {"item_id": 2, "quantity_requested": 5}
     ]
   }

2. Stock Manager approves request
   PUT /api/requests/1/status
   {"status": "approved"}

3. Stock Manager fulfills request
   POST /api/requests/1/fulfill
   - Generates Bon de Sortie entries
   - Decreases item quantities
   - Updates request status to "fulfilled"
   - If insufficient stock → suggests Purchase Order
```

### 2. Purchase Order Approval Flow

```
1. Stock Manager creates PO (when stock low)
   POST /api/purchase-orders
   {
     "supplier": "ABC Supplies",
     "date": "2025-11-20",
     "items": [{"item_id": 1, "quantity": 100, "unit_price": 2.50}]
   }

2. HR Manager reviews and approves
   PUT /api/purchase-orders/1/status
   {"status": "approved_hr"}

3. Stock Manager marks as ordered
   PUT /api/purchase-orders/1/status
   {"status": "ordered"}
```

### 3. Invoice Management

```
Finance Manager creates invoice (linked to PO)
POST /api/invoices
{
  "supplier": "ABC Supplies",
  "description": "Office supplies delivery",
  "quantity": 100,
  "price": 250.00,
  "date": "2025-11-20",
  "id_purchase_order_item": 1
}
```

## 🔒 Authorization Matrix

| Action                  | director | Stock Manager | Finance Manager | HR Manager |
| ----------------------- | -------- | ------------- | --------------- | ---------- |
| View Items              | ✅       | ✅            | ✅              | ✅         |
| Create/Edit Items       | ❌       | ✅            | ❌              | ✅         |
| Create Request          | ✅       | ✅            | ✅              | ✅         |
| View All Requests       | ❌       | ✅            | ✅              | ✅         |
| Approve/Fulfill Request | ❌       | ✅            | ❌              | ✅         |
| Create PO               | ❌       | ✅            | ❌              | ✅         |
| Approve PO              | ❌       | ❌            | ❌              | ✅         |
| View/Create Invoices    | ❌       | ❌            | ✅              | ✅         |

## 🛠 Technical Stack

- **Backend**: Laravel 11.x
- **Authentication**: Laravel Sanctum
- **Database**: SQLite (easily switchable to MySQL/PostgreSQL)
- **Frontend**: Blade Templates + Tailwind CSS
- **Charts**: Chart.js 4.x
- **JavaScript**: Alpine.js 3.x

## 📈 Low-Stock Alert System

Items automatically flagged as low-stock when:

```php
$item->quantity < $item->low_stock_threshold
```

- Accessible via `/api/stats/low-stock`
- Displayed in dashboard with red highlighting
- 8 items currently below threshold (ready for testing)

## 🧪 Testing the System

### Via Web Interface

1. Navigate to `http://localhost:8000`
2. Login with any demo credential
3. View interactive dashboard with charts
4. Navigate through role-appropriate pages

### Via API (cURL examples)

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"stock_manager","password":"password"}'

# Get Items
curl http://localhost:8000/api/items \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create Request
curl -X POST http://localhost:8000/api/requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"item_id":1,"quantity_requested":10}]}'

# Approve Request
curl -X PUT http://localhost:8000/api/requests/1/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"approved"}'

# Fulfill Request
curl -X POST http://localhost:8000/api/requests/1/fulfill \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get Dashboard Stats
curl http://localhost:8000/api/stats/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📁 Project Structure

```
school-inventory-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                    # API Controllers
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ItemController.php
│   │   │   │   ├── RequestController.php
│   │   │   │   ├── PurchaseOrderController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   └── StatsController.php
│   │   │   └── Web/                    # Web Controllers
│   │   │       ├── AuthWebController.php
│   │   │       └── DashboardController.php
│   ├── Models/                         # Eloquent Models
│   │   ├── User.php
│   │   ├── Department.php
│   │   ├── Item.php
│   │   ├── Request.php
│   │   ├── RequestItem.php
│   │   ├── BonDeSortie.php
│   │   ├── PurchaseOrder.php
│   │   ├── PurchaseOrderItem.php
│   │   └── Invoice.php
│   └── Policies/                       # Authorization Policies
│       ├── ItemPolicy.php
│       ├── RequestPolicy.php
│       ├── PurchaseOrderPolicy.php
│       └── InvoicePolicy.php
├── database/
│   ├── migrations/                     # Database Migrations
│   └── seeders/                        # Data Seeders
│       ├── DepartmentSeeder.php
│       ├── UserSeeder.php
│       ├── ItemSeeder.php
│       └── DemoDataSeeder.php
├── resources/views/                    # Blade Templates
│   ├── layouts/
│   │   └── app.blade.php              # Main Layout
│   ├── auth/
│   │   └── login.blade.php            # Login Page
│   └── dashboard.blade.php            # Dashboard with Charts
├── routes/
│   ├── api.php                        # API Routes
│   └── web.php                        # Web Routes
├── README.md
├── QUICKSTART.md
├── SYSTEM_DOCUMENTATION.md            # This File
└── postman_collection.json
```

## 🎉 Deployment Checklist

- [x] Laravel 11 project initialized
- [x] Sanctum authentication configured
- [x] 15 database tables with migrations
- [x] 9 Eloquent models with relationships
- [x] 4 authorization policies with role checks
- [x] 29 API endpoints (REST + JSON)
- [x] 6 API controllers with validation
- [x] Web interface with login & dashboard
- [x] Chart.js integration (4 charts)
- [x] 30 inventory items seeded
- [x] Demo data (requests, POs, invoices)
- [x] Low-stock alert system
- [x] Request fulfillment workflow
- [x] PO approval workflow
- [x] Comprehensive documentation
- [x] Demo credentials provided

## 🔄 Next Steps (Optional Enhancements)

- [ ] Add feature tests for workflows
- [ ] Implement database notifications
- [ ] Add caching to statistics endpoints
- [ ] Email notifications for low stock
- [ ] File upload for invoice documents
- [ ] PDF export for reports
- [ ] Audit trail for stock movements
- [ ] Advanced filtering and search
- [ ] Barcode/QR code integration
- [ ] Mobile responsive improvements

## 📞 Support

For issues or questions about the system, refer to:

- `README.md` - Basic setup and API examples
- `QUICKSTART.md` - Quick installation guide
- This file - Comprehensive system documentation

---

**Version**: 1.0.0  
**Laravel**: 11.x  
**Last Updated**: November 20, 2025
