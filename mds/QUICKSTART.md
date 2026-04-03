# Quick Start Guide

## 🚀 Installation & Setup

1. **Install Dependencies**

    ```bash
    composer install
    npm install && npm run build
    ```

2. **Configure Environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Run Migrations & Seed Database**

    ```bash
    php artisan migrate:fresh --seed
    ```

4. **Start Server**

    ```bash
    php artisan serve
    ```

5. **Access the Application**
    - Web UI: http://localhost:8000
    - API Base: http://localhost:8000/api

## 👥 Demo Login Credentials

| Username           | Password   | Role                     |
| ------------------ | ---------- | ------------------------ |
| `hr_manager`       | `password` | HR Manager (Full Access) |
| `stock_manager`    | `password` | Stock Manager            |
| `finance_manager`  | `password` | Finance Manager          |
| `director_nursery` | `password` | Director                 |

## 📊 Features Available

### ✅ Completed

- **API Backend**: Full RESTful API with Sanctum authentication
- **Database**: 29 migrations with ~20 tables and complete relationships
- **Items Management**: CRUD operations with categories and stock tracking
- **Supplier Management**: CRUD suppliers, supplier-specific pricing
- **Request System**: Multi-item requests with 24h expiration
- **Request Approval**: HR approval workflow (pending → hr_approved/rejected)
- **Fulfillment**: Automatic Bon de Sortie generation & stock deduction
- **Receipt Confirmation**: Optional receipt confirmation by requester
- **Purchase Orders**: Multi-stage approval (initial → proposals → final)
- **PO Splitting**: Split POs across multiple suppliers
- **Invoices**: Incoming/return types with line items, linked to POs
- **Excel Reports**: Export consumption and department data
- **Dashboard**: Interactive charts with Chart.js
    - Monthly consumption (line chart)
    - Consumption by department (bar chart)
    - Monthly spending (bar chart)
    - Top 10 consumed items (horizontal bar)
    - Low stock alerts table
- **Web UI**: Login, dashboard, full CRUD pages
- **Multi-language**: English, French, Arabic (RTL support)
- **Notifications**: 11 notification types for workflow events
- **Demo Data**: 30+ items, 4 departments, 5+ users

## 🔌 API Testing

### Test with cURL

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"stock_manager","password":"password"}'

# Get Items (use token from login)
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

# Fulfill Request (Stock Manager)
curl -X POST http://localhost:8000/api/requests/1/fulfill \
  -H "Authorization: Bearer YOUR_TOKEN"

# View Dashboard Stats
curl http://localhost:8000/api/stats/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test with Postman

Import `postman_collection.json` for pre-configured requests.

## 📁 Project Structure

```
school-management-inventory/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/           # API Controllers (10 controllers)
│   │   └── Web/           # Web Controllers (closures)
│   ├── Models/            # Eloquent Models (15+ models)
│   └── Policies/         # Authorization Policies
├── database/
│   ├── migrations/        # 29 Migrations
│   └── seeders/          # Data Seeders
├── resources/
│   ├── lang/             # Translations (en, fr, ar)
│   └── views/           # Blade Templates
├── routes/
│   ├── api.php          # API Routes (~50 endpoints)
│   └── web.php          # Web Routes
├── tests/               # Pest Tests
├── README.md            # Full Documentation
└── QUICKSTART.md        # This File
```

## 🎯 Key Workflows

### 1. Request → Fulfillment Flow

1. Director creates request: `POST /api/requests`
2. HR manager approves: `PUT /api/requests/{id}/status` → `{"status":"hr_approved"}`
3. Stock manager fulfills: `POST /api/requests/{id}/fulfill`
   - Generates Bon de Sortie
   - Decreases stock
   - Marks request as fulfilled
4. Director confirms receipt: `POST /api/requests/{id}/confirm-receipt`

### 2. Purchase Order Multi-Stage Approval

1. Stock manager creates PO: `POST /api/purchase-orders`
2. HR manager initial approval: `POST /api/purchase-orders/{id}/initial-approval`
3. Stock manager adds proposals: `POST /api/purchase-orders/{id}/proposals`
4. HR manager final approval: `POST /api/purchase-orders/{id}/final-approval`
5. Stock manager marks ordered: `PUT /api/purchase-orders/{id}/status` → `{"status":"ordered"}`

### 3. PO Splitting (Multi-Supplier)

1. After final approval: `POST /api/purchase-orders/{id}/split`
   - Creates child POs for each supplier
   - Parent PO marked as "split"

### 4. Invoice Management

1. Finance manager creates invoice: `POST /api/invoices`
   - Set type: "incoming" (increases stock) or "return" (decreases stock)
   - Link to PO via `id_purchase_order`
   - Add line items via `items` array

## 🛠 Development Tips

- All API routes require Sanctum authentication (except `/api/login`)
- Validation is built into all controllers
- Low stock items: `quantity < low_stock_threshold`
- Database uses SQLite (switchable to MySQL/PostgreSQL in `.env`)
- Request pending_until: auto-expires after 24 hours (run `php artisan requests:expire-pending`)

## 📈 Dashboard Charts

The dashboard automatically fetches data from:

- `/api/stats/dashboard` - Overview statistics
- `/api/stats/consumption` - Monthly consumption trend
- `/api/stats/consumption-by-department` - Department breakdown
- `/api/stats/spending` - Financial trends
- `/api/stats/top-items` - Most consumed items
- `/api/stats/low-stock` - Items needing restock

## 📥 Excel Reports

Export data via:

- `GET /api/reports/consumed-materials` - Consumed materials Excel
- `GET /api/reports/department-consumption` - Department consumption Excel

## 🐛 Troubleshooting

**Issue**: API routes not found
**Solution**: Make sure `routes/api.php` is registered in `bootstrap/app.php`

**Issue**: Charts not loading
**Solution**: Ensure you're logged in and have an API token in session

**Issue**: Database errors
**Solution**: Run `php artisan migrate:fresh --seed`

**Issue**: Requests stuck in pending
**Solution**: Run `php artisan requests:expire-pending` to auto-expire old requests

## 📞 Next Steps

1. ✅ Test API endpoints with Postman
2. ✅ Login to web dashboard
3. ✅ Create test requests
4. ✅ View analytics charts
5. ✅ Create purchase orders with multi-stage approval
6. ✅ Test supplier proposal workflow
7. ✅ Manage invoices with line items

## 🎉 Success!

Your School Inventory Management System is ready to use. Check README.md for complete API documentation.
