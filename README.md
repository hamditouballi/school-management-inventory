# School Inventory Management System

A complete Laravel-based inventory management system for schools with role-based access control, request fulfillment, purchase order approval workflow, and comprehensive dashboard analytics.

## Features

- **Multi-Role Access Control**: Teacher, Stock Manager, Finance Manager, HR Manager
- **Inventory Management**: CRUD operations, real-time tracking, low-stock alerts
- **Request Workflow**: Multi-item requests with approval and fulfillment
- **Purchase Order System**: HR approval workflow with supplier tracking
- **Invoice Management**: Finance tracking with PO linkage
- **Dashboard & Analytics**: Consumption, spending, top items charts

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

## Demo Users

| Username | Password | Role |
|----------|----------|------|
| `hr_manager` | `password` | HR Manager |
| `stock_manager` | `password` | Stock Manager |
| `finance_manager` | `password` | Finance Manager |
| `teacher_nursery` | `password` | Teacher |

## API Endpoints

**Base URL**: `http://localhost:8000/api`

### Authentication
- `POST /login` - Login
- `POST /logout` - Logout
- `GET /me` - Current user

### Items
- `GET /items` - List items
- `POST /items` - Create item
- `GET /items/{id}` - Get item
- `PUT /items/{id}` - Update item
- `DELETE /items/{id}` - Delete item

### Requests
- `GET /requests` - List requests
- `POST /requests` - Create request
- `GET /requests/{id}` - Get request
- `PUT /requests/{id}/status` - Update status
- `POST /requests/{id}/fulfill` - Fulfill request

### Purchase Orders
- `GET /purchase-orders` - List POs
- `POST /purchase-orders` - Create PO
- `GET /purchase-orders/{id}` - Get PO
- `PUT /purchase-orders/{id}/status` - Update status
- `DELETE /purchase-orders/{id}` - Delete PO

### Invoices
- `GET /invoices` - List invoices
- `POST /invoices` - Create invoice
- `DELETE /invoices/{id}` - Delete invoice

### Statistics
- `GET /stats/dashboard` - Overview stats
- `GET /stats/consumption` - Monthly consumption
- `GET /stats/consumption-by-department` - By department
- `GET /stats/spending` - Monthly spending
- `GET /stats/top-items` - Top consumed items
- `GET /stats/low-stock` - Low stock alerts

## Example Usage

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"stock_manager","password":"password"}'

# Create Request
curl -X POST http://localhost:8000/api/requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"item_id":1,"quantity_requested":10}]}'
```

## License

Open-source for educational purposes.
