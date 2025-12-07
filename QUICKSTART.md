# Quick Start Guide

## 🚀 Installation & Setup

1. **Install Dependencies**
   ```bash
   composer install
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

| Username | Password | Role |
|----------|----------|------|
| `hr_manager` | `password` | HR Manager (Full Access) |
| `stock_manager` | `password` | Stock Manager |
| `finance_manager` | `password` | Finance Manager |
| `teacher_nursery` | `password` | Teacher |

## 📊 Features Available

### ✅ Completed
- **API Backend**: Full RESTful API with Sanctum authentication
- **Database**: All models, migrations, relationships
- **Items Management**: CRUD operations with stock tracking
- **Request System**: Multi-item requests with approval workflow
- **Fulfillment**: Automatic Bon de Sortie generation & stock deduction
- **Purchase Orders**: HR approval workflow with supplier tracking
- **Invoices**: Finance management with PO linkage
- **Dashboard**: Interactive charts with Chart.js
  - Monthly consumption (line chart)
  - Consumption by department (bar chart)
  - Monthly spending (bar chart)
  - Top 10 consumed items (horizontal bar)
  - Low stock alerts table
- **Web UI**: Login, dashboard with role-based navigation
- **Demo Data**: 15 inventory items, 4 departments, 5 users

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

# View Dashboard Stats
curl http://localhost:8000/api/stats/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test with Postman
Import `postman_collection.json` for pre-configured requests.

## 📁 Project Structure

```
school-inventory-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/           # API Controllers
│   │   └── Web/           # Web Controllers
│   └── Models/            # Eloquent Models
├── database/
│   ├── migrations/        # Database Migrations
│   └── seeders/           # Data Seeders
├── resources/
│   └── views/             # Blade Templates
├── routes/
│   ├── api.php            # API Routes
│   └── web.php            # Web Routes
├── README.md              # Full Documentation
└── QUICKSTART.md          # This File
```

## 🎯 Key Workflows

### 1. Request → Fulfillment Flow
1. Teacher creates request: `POST /api/requests`
2. Stock manager approves: `PUT /api/requests/{id}/status` → `{"status":"approved"}`
3. Stock manager fulfills: `POST /api/requests/{id}/fulfill`
   - Generates Bon de Sortie
   - Decreases stock
   - Marks request as fulfilled

### 2. Purchase Order Approval
1. Stock manager creates PO: `POST /api/purchase-orders`
2. HR manager approves: `PUT /api/purchase-orders/{id}/status` → `{"status":"approved_hr"}`
3. Stock manager marks ordered: `PUT /api/purchase-orders/{id}/status` → `{"status":"ordered"}`

### 3. Invoice Management
1. Finance manager creates invoice: `POST /api/invoices`
2. Links to purchase order item via `id_purchase_order_item`

## 🛠 Development Tips

- All API routes require Sanctum authentication (except `/api/login`)
- Validation is built into all controllers
- Low stock threshold: 50 units
- Database uses SQLite (easily switchable to MySQL/PostgreSQL in `.env`)

## 📈 Dashboard Charts

The dashboard automatically fetches data from:
- `/api/stats/dashboard` - Overview statistics
- `/api/stats/consumption` - Monthly consumption trend
- `/api/stats/consumption-by-department` - Department breakdown
- `/api/stats/spending` - Financial trends
- `/api/stats/top-items` - Most consumed items
- `/api/stats/low-stock` - Items needing restock

## 🐛 Troubleshooting

**Issue**: API routes not found
**Solution**: Make sure `routes/api.php` is registered in `bootstrap/app.php`

**Issue**: Charts not loading
**Solution**: Ensure you're logged in and have an API token in session

**Issue**: Database errors
**Solution**: Run `php artisan migrate:fresh --seed`

## 📞 Next Steps

1. ✅ Test API endpoints with Postman
2. ✅ Login to web dashboard
3. ✅ Create test requests
4. ✅ View analytics charts
5. 🔄 Extend UI with full CRUD pages (optional)
6. 🔄 Add authorization policies (optional)
7. 🔄 Implement file uploads for invoices (optional)

## 🎉 Success!

Your School Inventory Management System is ready to use. Check README.md for complete API documentation.
