# School Inventory Management System - Complete Documentation

## 🎯 System Overview

A production-ready Laravel 11 inventory management system for schools with comprehensive role-based access control, multi-stage approval workflows, supplier management, purchase order splitting, invoice management, and interactive dashboards with analytics.

## 📊 Implemented Features

### ✅ Core Functionality

- **Role-Based Access Control**: 6 distinct roles with specific permissions
    - `director`: View items, create requests, track own requests
    - `stock_manager`: Manage inventory, fulfill requests, create POs, manage suppliers
    - `hr_manager`: Approve requests/POs, system oversight
    - `finance_manager`: Manage invoices, view financial data
    - `pm_manager`: Project manager (extended permissions)
    - `logistics_manager`: Logistics operations

- **Inventory Management**
    - Full CRUD operations for items with categories
    - Low-stock threshold tracking with alerts
    - Description, unit, and price tracking
    - Computed `is_low_stock` attribute for real-time monitoring

- **Supplier Management**
    - Supplier directory with contact info
    - Supplier-specific item pricing
    - Supplier statistics and tracking

- **Request Workflow**
    - Multi-item requests with 24-hour pending expiration
    - Status tracking: pending → hr_approved → fulfilled/rejected
    - Receipt confirmation system
    - Automatic Bon de Sortie generation on fulfillment
    - Stock deduction with insufficient stock detection

- **Purchase Order System**
    - Multi-stage approval workflow
    - Supplier proposal collection and comparison
    - PO splitting across multiple suppliers
    - Multi-item POs with new item support
    - Parent-child PO relationships for splits

- **Invoice Management**
    - Incoming and return invoice types
    - Line items support (invoice_items)
    - Link to Purchase Order Items
    - Auto stock update from incoming invoices
    - File/image upload support
    - Link invoices to Bon de Livraisons (BDL)
    - **BDL-Invoice Reconciliation**: Verify invoice quantities match BDL quantities
    - **Supplier Validation**: Only BDLs from same supplier can be linked to one invoice

- **Bon de Livraison (BDL)**
    - Create delivery notes from confirmed purchase orders
    - Track delivered quantities per item
    - Link BDLs to invoices for reconciliation
    - File/image upload support

- **Phone Camera Upload**
    - Upload images via phone camera (100% local, no internet)
    - QR code generation for each upload context
    - Polling system to receive images on PC
    - Supports items, BDL, and invoice images
    - Image is copied to main storage when used

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
    - `ItemPolicy`: View (all), Create/Update/Delete (stock_manager, hr_manager)
    - `RequestPolicy`: Create (all), View (own or managers), Approve/Reject (hr_manager), Fulfill (stock_manager)
    - `PurchaseOrderPolicy`: Create (stock_manager), Initial/Final Approval (hr_manager), View (managers)
    - `InvoicePolicy`: Create/Manage (finance_manager, hr_manager)

### ✅ Database Architecture

**29 Migrations** defining ~20 tables with complete relationships:

| Table | Purpose |
|-------|---------|
| `users` | Authentication, role-based access |
| `departments` | School organizational structure |
| `items` | Inventory catalog with thresholds |
| `categories` | Item categorization |
| `requests` | Material requests |
| `request_items` | Junction table for multi-item requests |
| `bon_de_sorties` | Proof of item delivery |
| `purchase_orders` | Procurement tracking |
| `purchase_order_items` | Items in purchase orders |
| `purchase_order_suppliers` | Supplier proposals |
| `suppliers` | Supplier directory |
| `supplier_items` | Supplier-specific pricing |
| `invoices` | Financial records |
| `invoice_items` | Line items on invoices |
| `notifications` | Database notifications |
| `personal_access_tokens`, `sessions`, `cache`, `jobs`, `password_reset_tokens` | Framework tables |

### ✅ API Endpoints (~50 routes)

#### Authentication

```
POST   /api/login          - Login and get token
POST   /api/logout         - Logout current user
GET    /api/me             - Get current user details
```

#### Items

```
GET    /api/items          - List all items
POST   /api/items          - Create new item
GET    /api/items/{id}     - Get item details
PUT    /api/items/{id}     - Update item
DELETE /api/items/{id}     - Delete item
GET    /api/categories     - List all categories
```

#### Requests

```
GET    /api/requests                    - List requests (role-filtered)
POST   /api/requests                    - Create multi-item request
GET    /api/requests/unconfirmed        - Unconfirmed fulfilled requests
GET    /api/requests/my-unconfirmed     - User's unconfirmed requests
GET    /api/requests/{id}               - Get request details
PUT    /api/requests/{id}/status        - Update status (approve/reject)
POST   /api/requests/{id}/fulfill       - Fulfill and generate Bon de Sortie
POST   /api/requests/{id}/confirm-receipt - Confirm receipt
```

#### Purchase Orders

```
GET    /api/purchase-orders                  - List all POs
POST   /api/purchase-orders                  - Create PO
GET    /api/purchase-orders/{id}              - Get PO details
PUT    /api/purchase-orders/{id}              - Update PO
DELETE /api/purchase-orders/{id}              - Delete PO
PUT    /api/purchase-orders/{id}/status       - Update status
POST   /api/purchase-orders/{id}/initial-approval    - Initial approval (HR)
POST   /api/purchase-orders/{id}/proposals    - Add supplier proposals
POST   /api/purchase-orders/{id}/final-approval       - Final approval (HR)
POST   /api/purchase-orders/{id}/split        - Split PO to suppliers
```

#### Suppliers

```
GET    /api/suppliers                     - List suppliers
POST   /api/suppliers                     - Create supplier
GET    /api/suppliers/{id}                - Get supplier details
PUT    /api/suppliers/{id}                - Update supplier
DELETE /api/suppliers/{id}                - Delete supplier
GET    /api/suppliers/all-with-items      - All suppliers with items
GET    /api/suppliers/{id}/items          - Supplier's items
POST   /api/suppliers/{id}/items          - Add item to supplier
PUT    /api/suppliers/{id}/items/{itemId} - Update supplier item price
DELETE /api/suppliers/{id}/items/{itemId}  - Remove item from supplier
GET    /api/suppliers/{id}/stats          - Supplier statistics
```

#### Invoices

```
GET    /api/invoices       - List all invoices
POST   /api/invoices       - Create invoice
GET    /api/invoices/{id}  - Get invoice details
PUT    /api/invoices/{id}  - Update invoice
DELETE /api/invoices/{id}  - Delete invoice
```

#### Bon de Livraison

```
GET    /api/purchase-orders/{po}/bon-de-livraison     - List BDLs for PO
POST   /api/purchase-orders/{po}/bon-de-livraison     - Create BDL
GET    /api/bon-de-livraison/{id}                      - Get BDL details
PUT    /api/bon-de-livraison/{id}                      - Update BDL
DELETE /api/bon-de-livraison/{id}                      - Delete BDL
POST   /api/bon-de-livraison/{id}/confirm              - Confirm BDL
```

#### Statistics & Dashboard

```
GET    /api/stats/dashboard                  - Overview statistics
GET    /api/stats/consumption                - Monthly consumption data
GET    /api/stats/consumption-by-department - Department breakdown
GET    /api/stats/spending                   - Monthly spending trends
GET    /api/stats/top-items                  - Top 10 consumed items
GET    /api/stats/low-stock                  - Low-stock alerts
```

#### Reports (Excel Export)

```
GET    /api/reports/consumed-materials       - Export consumed materials
GET    /api/reports/department-consumption    - Export department consumption
```

#### Phone Camera Upload

```
GET    /api/server-ip                         - Get server local IP for QR code
GET    /phone-upload/{context}/{targetId}     - Show phone upload page
POST   /phone-upload                          - Handle phone upload
GET    /phone-uploads/{sessionKey}            - Poll for received uploads
POST   /phone-uploads/{uploadId}/received     - Mark upload as received
POST   /phone-uploads/promote                 - Copy upload to main storage
```

### ✅ Web Interface

- **Responsive Design** with Tailwind CSS + RTL support
- **Multi-language**: English, French, Arabic (RTL)
- **Login Page** with demo credentials display
- **Dashboard** with interactive charts and statistics
- **Navigation Menu** (role-based visibility)
- **Full CRUD Pages** for Items, Requests, POs, Invoices, Suppliers, Bon Sortie

## 🚀 Installation & Setup

```bash
# 1. Navigate to project
cd school-management-inventory

# 2. Install dependencies
composer install
npm install && npm run build

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
| `director_nursery` | `password` | Director        | Nursery        |
| `director_primary` | `password` | Director        | Primary        |

## 📦 Demo Data Included

- **30+ Inventory Items** with descriptions, prices, units, and low-stock thresholds
- **Multiple Sample Requests** with various statuses
- **Multiple Bon de Sorties** for fulfilled requests
- **Multiple Purchase Orders** (various approval states)
- **Multiple Invoices** (some linked to PO items)
- **Suppliers** with item pricing
- **4 Departments** (Nursery, Primary, Middle/High School, Administration)
- **5+ Users** across different roles

## 🎯 Business Workflows

### 1. Request → Fulfillment Flow

```
1. Director creates request with multiple items
   POST /api/requests
   {
     "items": [
       {"item_id": 1, "quantity_requested": 10},
       {"item_id": 2, "quantity_requested": 5}
     ]
   }
   Status: pending
   pending_until: 24 hours later

2. HR Manager reviews and approves OR rejects
   PUT /api/requests/{id}/status
   {"status": "hr_approved"} OR {"status": "rejected"}

3. Stock Manager fulfills request (if approved)
   POST /api/requests/{id}/fulfill
   - Generates Bon de Sortie entries
   - Decreases item quantities
   - Updates request status to "fulfilled"

4. Director confirms receipt (optional)
   POST /api/requests/{id}/confirm-receipt
   - Updates status to "received"
```

### 2. Purchase Order Multi-Stage Approval Flow

```
1. Stock Manager creates PO (when stock low)
   POST /api/purchase-orders
   {
     "date": "2026-03-20",
     "items": [
       {"item_id": 1, "quantity": 100, "unit_price": 2.50},
       {"new_item_name": "New Item", "quantity": 50, "unit_price": 5.00}
     ]
   }
   Status: pending_initial_approval

2. HR Manager Initial Approval
   POST /api/purchase-orders/{id}/initial-approval
   Status: initial_approved OR rejected

3. Stock Manager adds supplier proposals
   POST /api/purchase-orders/{id}/proposals
   {
     "proposals": [
       {"supplier_name": "ABC Supplies", "price": 250.00, "quality_rating": 5},
       {"supplier_name": "XYZ Corp", "price": 240.00, "quality_rating": 4}
     ]
   }
   Status: pending_final_approval

4. HR Manager Final Approval (selects best supplier)
   POST /api/purchase-orders/{id}/final-approval
   {"selected_supplier_id": 1}
   Status: final_approved

5. Stock Manager marks as ordered
   PUT /api/purchase-orders/{id}/status
   {"status": "ordered"}
```

### 3. PO Splitting Flow (Multi-Supplier)

```
1. After final approval, Stock Manager splits PO
   POST /api/purchase-orders/{id}/split
   {
     "splits": [
       {"supplier_id": 1, "items": [{"purchase_order_item_id": 1, "quantity": 50}]},
       {"supplier_id": 2, "items": [{"purchase_order_item_id": 2, "quantity": 30}]}
     ]
   }
   - Creates child POs linked to parent
   - Parent status: split
   - Each child follows independent workflow
```

### 4. Invoice Management

```
Finance Manager creates incoming invoice (linked to approved PO):
POST /api/invoices
{
  "type": "incoming",
  "supplier": "ABC Supplies",
  "date": "2026-03-20",
  "id_purchase_order": 1,
  "items": [
    {"item_name": "Item 1", "quantity": 100, "unit_price": 2.50}
  ]
}

OR create return invoice:
POST /api/invoices
{
  "type": "return",
  "supplier": "ABC Supplies",
  "date": "2026-03-20",
  "items": [...]
}
```

## 🔒 Authorization Matrix

| Action                  | Director | Stock Manager | Finance Manager | HR Manager |
| ----------------------- | -------- | ------------- | --------------- | ---------- |
| View Items              | ✅       | ✅            | ✅              | ✅         |
| Create/Edit Items       | ❌       | ✅            | ❌              | ✅         |
| Create Request          | ✅       | ✅            | ✅              | ✅         |
| View All Requests       | Own only | ✅            | ✅              | ✅         |
| Approve/Reject Request  | ❌       | ❌            | ❌              | ✅         |
| Fulfill Request         | ❌       | ✅            | ❌              | ❌         |
| Create PO               | ❌       | ✅            | ❌              | ✅         |
| Initial PO Approval     | ❌       | ❌            | ❌              | ✅         |
| Final PO Approval       | ❌       | ❌            | ❌              | ✅         |
| View/Create Invoices    | ❌       | View          | ✅              | ✅         |
| Manage Suppliers        | ❌       | ✅            | ❌              | ✅         |

## 🛠 Technical Stack

- **Backend**: Laravel 11.x (PHP 8.2+)
- **Authentication**: Laravel Sanctum
- **Database**: SQLite (switchable to MySQL/PostgreSQL)
- **Frontend**: Blade Templates + Tailwind CSS + Alpine.js
- **Charts**: Chart.js 4.x
- **Excel Export**: Maatwebsite Excel
- **Testing**: Pest PHP

## 📈 Low-Stock Alert System

Items automatically flagged as low-stock when:

```php
$item->quantity < $item->low_stock_threshold
```

- Accessible via `/api/stats/low-stock`
- Displayed in dashboard with visual highlighting

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

# Approve Request (HR Manager)
curl -X PUT http://localhost:8000/api/requests/1/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"hr_approved"}'

# Fulfill Request
curl -X POST http://localhost:8000/api/requests/1/fulfill \
  -H "Authorization: Bearer YOUR_TOKEN"

# Confirm Receipt
curl -X POST http://localhost:8000/api/requests/1/confirm-receipt \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get Dashboard Stats
curl http://localhost:8000/api/stats/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📁 Project Structure

```
school-management-inventory/
├── app/
│   ├── Console/Commands/           # Artisan commands (ExpirePendingRequests)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                # API Controllers
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── BonDeSortieController.php
│   │   │   │   ├── BonDeLivraisonController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── ItemController.php
│   │   │   │   ├── PhoneUploadController.php
│   │   │   │   ├── PurchaseOrderController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── RequestController.php
│   │   │   │   ├── StatsController.php
│   │   │   │   └── SupplierController.php
│   │   │   └── Web/                # Web Controllers (closures)
│   │   └── Middleware/
│   │       └── SetLocale.php       # RTL/LTR support
│   ├── Models/                     # Eloquent Models
│   │   ├── User.php, Department.php, Item.php
│   │   ├── Request.php, RequestItem.php
│   │   ├── BonDeSortie.php
│   │   ├── PurchaseOrder.php, PurchaseOrderItem.php, PurchaseOrderSupplier.php
│   │   ├── Invoice.php, InvoiceItem.php
│   │   ├── Supplier.php, SupplierItem.php, Category.php
│   │   └── Notifications (11 classes)
│   ├── Policies/                   # Authorization Policies
│   │   ├── ItemPolicy.php
│   │   ├── RequestPolicy.php
│   │   ├── PurchaseOrderPolicy.php
│   │   └── InvoicePolicy.php
│   └── Exports/                    # Excel exports
│       ├── ConsumedMaterialsExport.php
│       ├── DepartmentConsumptionExport.php
│       └── MonthlyConsumptionExport.php
├── database/
│   ├── migrations/                # 29 Migrations
│   └── seeders/                   # Data Seeders
├── resources/
│   ├── lang/                      # Translations (en, fr, ar)
│   └── views/                     # Blade Templates
│       ├── layouts/
│       ├── items/
│       ├── requests/
│       ├── purchase-orders/
│       ├── invoices/
│       ├── suppliers/
│       ├── bon-sortie/
│       ├── notifications/
│       └── dashboard.blade.php
├── routes/
│   ├── api.php                    # API Routes
│   └── web.php                    # Web Routes
├── tests/                         # Pest Tests
│   ├── Feature/
│   └── Unit/
├── AGENTS.md                      # Development guidelines
├── README.md
├── QUICKSTART.md
├── SYSTEM_DOCUMENTATION.md        # This File
└── diagrams.md                    # UML Diagrams
```

## 🎉 Deployment Checklist

- [x] Laravel 11 project initialized
- [x] Sanctum authentication configured
- [x] 29 database migrations with relational schema
- [x] 15+ Eloquent models with relationships
- [x] 4 authorization policies with role checks
- [x] ~50 API endpoints (REST + JSON)
- [x] 10 API controllers with validation
- [x] Web interface with login, dashboard, and CRUD pages
- [x] Chart.js integration (5 charts)
- [x] Multi-language support (EN, FR, AR with RTL)
- [x] Supplier management system
- [x] Multi-stage PO approval workflow
- [x] PO splitting across suppliers
- [x] Receipt confirmation workflow
- [x] Excel export functionality
- [x] Notification system (11 notification types)
- [x] Low-stock alert system
- [x] Request fulfillment workflow
- [x] Comprehensive documentation
- [x] Demo credentials provided

## 🔄 Next Steps (Optional Enhancements)

- [ ] Add feature tests for workflows
- [ ] Implement email notifications
- [ ] Add caching to statistics endpoints
- [ ] PDF export for reports
- [ ] Audit trail for stock movements
- [ ] Advanced filtering and search
- [ ] Barcode/QR code integration
- [ ] Multi-school support

---

**Version**: 3.0.0  
**Laravel**: 11.x  
**PHP**: 8.2+  
**Last Updated**: April 5, 2026
