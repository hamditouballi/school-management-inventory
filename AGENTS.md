# AGENTS.md - Development Guidelines for This Project

This is a Laravel 11 + PHP 8.2 application with a Blade/TailwindCSS frontend.

## Project Overview

- **Framework**: Laravel 11.48
- **PHP Version**: 8.2+
- **Testing**: Pest PHP
- **Code Style**: Laravel Pint (PSR-12 + Laravel conventions)
- **Frontend**: Vite + TailwindCSS + AlpineJS
- **Authentication**: Laravel Sanctum (API tokens)

---

## Supported Locales

The application supports 3 languages with RTL support:

| Code | Language | Direction |
|------|----------|-----------|
| en | English | LTR |
| fr | Français | LTR |
| ar | العربية | RTL |

RTL support is configured in `config/app.php` under `available_locales`. The layout (`resources/views/layouts/app.blade.php`) automatically applies RTL styles when Arabic is active.

---

## Pages / Routes

### Web Routes (Page Controllers)

| Route | Controller | Description |
|-------|------------|-------------|
| `/` | - | Welcome page |
| `/login` | AuthWebController | Login page |
| `/dashboard` | DashboardController | Dashboard with stats |
| `/items` | - | Items management (Blade view) |
| `/requests` | - | Requests management (Blade view) |
| `/purchase-orders` | - | Purchase orders management (Blade view) |
| `/invoices` | - | Invoices management (Blade view) |
| `/locale/{locale}` | LocaleController | Language switching |

### API Routes

| Endpoint | Controller | Description |
|----------|------------|-------------|
| `GET /api/items` | ItemController | List/create items |
| `GET /api/requests` | RequestController | List/create requests |
| `POST /api/requests/{request}/fulfill` | RequestController | Fulfill a request |
| `PUT /api/requests/{request}/status` | RequestController | Update request status |
| `GET /api/purchase-orders` | PurchaseOrderController | List/create POs |
| `POST /api/purchase-orders/{po}/initial-approval` | PurchaseOrderController | Initial approval |
| `POST /api/purchase-orders/{po}/final-approval` | PurchaseOrderController | Final approval |
| `POST /api/purchase-orders/{po}/proposals` | PurchaseOrderController | Submit supplier proposals |
| `PUT /api/purchase-orders/{po}/status` | PurchaseOrderController | Update PO status |
| `GET /api/invoices` | InvoiceController | List/create invoices |
| `GET /api/stats/dashboard` | StatsController | Dashboard stats |
| `GET /api/stats/consumption` | StatsController | Consumption stats |
| `GET /api/stats/consumption-by-department` | StatsController | Consumption by department |
| `GET /api/stats/low-stock` | StatsController | Low stock items |
| `GET /api/stats/spending` | StatsController | Spending stats |
| `GET /api/stats/top-items` | StatsController | Top items |
| `GET /api/reports/consumed-materials` | ReportController | Consumed materials report |
| `GET /api/reports/department-consumption` | ReportController | Department consumption |
| `POST /api/login` | AuthController | API login |
| `POST /api/logout` | AuthController | API logout |
| `GET /api/me` | AuthController | Get current user |

---

## User Roles

| Role | Permissions |
|------|-------------|
| `director` | View requests |
| `stock_manager` | Manage items, requests, purchase orders |
| `hr_manager` | Approve requests, purchase orders |
| `finance_manager` | Manage invoices, final approval |

---

## Build / Lint / Test Commands

### Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run tests in parallel (requires pest-plugin-parallel)
./vendor/bin/pest --parallel

# Run a single test file
./vendor/bin/pest tests/Feature/InvoiceCrudTest.php

# Run a single test by name
./vendor/bin/pest --filter="it can list all invoices"

# Run only unit tests
./vendor/bin/pest --testsuite=Unit

# Run only feature tests
./vendor/bin/pest --testsuite=Feature
```

### Code Formatting (Laravel Pint)

```bash
# Format all PHP files
./vendor/bin/pint

# Format with verbose output
./vendor/bin/pint -v

# Format specific file
./vendor/bin/pint app/Http/Controllers/Api/InvoiceController.php
```

### PHP Linting

```bash
# Run PHP built-in linter on files
php -l app/Http/Controllers/Controller.php

# Check syntax on entire app directory
find app -name "*.php" -exec php -l {} \;
```

### Frontend Build

```bash
# Install npm dependencies
npm install

# Run development server (with Vite hot reload)
npm run dev

# Build for production
npm run build
```

### Artisan Commands

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# List routes
php artisan route:list
```

---

## Code Style Guidelines

### PHP Style

- **Standard**: PSR-12 with Laravel conventions
- **Formatting**: Always run `./vendor/bin/pint` before committing
- **PHP Tags**: Use `<?php` (never `<?`)
- **PHP Attributes**: Prefer PHP 8 attributes over annotations where applicable

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Controllers | Singular, PascalCase | `InvoiceController` |
| Models | Singular, PascalCase | `Invoice`, `User` |
| Migrations | Timestamp + SnakeCase | `2024_01_15_000000_create_invoices_table` |
| Methods | camelCase | `getTotalAmountAttribute` |
| Variables | camelCase | `$invoice`, `$validatedData` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRY_COUNT` |
| Routes | RESTful, lowercase with hyphens | `/api/invoices` |

### Imports

- **Use FQCN**: Always use fully qualified class names for core Laravel classes
- **Group Imports**:
  1. Framework imports
  2. Package imports
  3. Application imports
  4. Function imports

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Invoice;
use App\Models\Item;
use function Pest\Laravel\actingAs;
```

### Type Declarations

- **Return Types**: Always declare return types on methods
- **Property Types**: Use typed properties where possible
- **Nullable**: Use `?Type` syntax for nullable parameters

```php
public function index(): JsonResponse
{
    return response()->json(Invoice::all());
}

public function store(Request $request): JsonResponse
{
    $validated = $request->validate([...]);
    // ...
}
```

### Controller Guidelines

- **Single Responsibility**: Each controller should focus on one resource
- **RESTful Actions**: Implement index, store, show, update, destroy
- **Response Format**: Use `response()->json()` for API routes
- **Validation**: Use form request classes for complex validation

### Model Guidelines

- **Fillable**: Always define `$fillable` for mass assignment
- **Casts**: Use `$casts` for date/datetime/array casting
- **Relationships**: Define relationships as methods returning relation objects
- **Scopes**: Use query scopes for reusable query logic

```php
class Invoice extends Model
{
    protected $fillable = ['type', 'supplier', 'date', 'id_responsible_finance'];
    
    protected $casts = [
        'date' => 'date',
    ];
    
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
```

### Error Handling

- **Transactions**: Use `DB::transaction()` or `beginTransaction/commit/rollBack` for multi-step operations
- **Exceptions**: Throw exceptions for errors, catch at controller level
- **Validation**: Use Laravel's built-in validation with clear error messages

```php
DB::beginTransaction();
try {
    $invoice = Invoice::create($validated);
    // ... process items
    DB::commit();
    return response()->json($invoice, 201);
} catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['error' => $e->getMessage()], 500);
}
```

### Database

- **Primary Keys**: Use bigIncrements (default in Laravel)
- **Foreign Keys**: Use `id_` prefix for foreign key columns (`id_user`, `id_invoice`)
- **Timestamps**: Let Laravel handle `created_at` and `updated_at`
- **Soft Deletes**: Use `SoftDeletes` trait where applicable

### Testing Conventions (Pest)

- **Test Names**: Use descriptive names with "it can..." or "should..." pattern
- **Arrange/Act/Assert**: Structure tests clearly
- **Factories**: Use model factories for test data

```php
use App\Models\Invoice;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'finance_manager']);
});

test('it can list all invoices', function () {
    Invoice::factory()->count(3)->create();

    actingAs($this->user, 'sanctum')
        ->getJson('/api/invoices')
        ->assertStatus(200)
        ->assertJsonCount(3);
});
```

### API Design

- **RESTful**: Follow REST principles
- **Status Codes**: Use appropriate codes (200, 201, 204, 400, 401, 403, 404, 422, 500)
- **Authentication**: Use Laravel Sanctum for API authentication
- **Versioning**: Prefix APIs with `/api/`

### Security

- **Mass Assignment**: Never expose sensitive fields in `$fillable`
- **Authorization**: Use Policies for authorization checks
- **Input Validation**: Always validate user input
- **SQL Injection**: Use Eloquent ORM (automatic escaping) - never use raw SQL with user input

---

## Translation / Localization

This app supports multiple languages (English, Arabic, French). All user-facing strings must use translation keys - no hardcoded text.

### Translation Files

- **Location**: `resources/lang/{locale}/messages.php`
- **Supported Locales**: `en`, `ar`, `fr` (configured in `config/app.php`)

### Adding New Translations

When adding new UI text, you must add translations to ALL three locale files:

1. Add the key to `resources/lang/en/messages.php` (base)
2. Add the key to `resources/lang/ar/messages.php`
3. Add the key to `resources/lang/fr/messages.php`

### Usage in Blade Templates

```blade
{{ __('messages.key_name') }}
```

### Usage in JavaScript

For JavaScript strings, use the translation in blade template:

```javascript
Notification.success('{{ __('messages.success_message') }}');
```

### Pagination Translation Pattern

Use this pattern for pagination info:

```blade
{{ __('messages.showing') }} <span id="showingFrom">0</span> {{ __('messages.to') }} <span id="showingTo">0</span> {{ __('messages.of') }} <span id="totalItems">0</span> {{ __('messages.items') }}
```

### Status Translation Pattern

For translatable status values in JavaScript, use a translation object:

```javascript
const statusTranslations = {
    pending_initial_approval: '{{ __('messages.pending_initial_approval') }}',
    initial_approved: '{{ __('messages.initial_approved') }}',
    // ...
};

// Usage
`${statusTranslations[po.status] || po.status}`
```

### Clearing Cache

After modifying translations, clear the view cache:

```bash
php artisan view:clear
php artisan config:clear
```

---

## RTL (Right-to-Left) Support

The application supports RTL for Arabic. The layout (`resources/views/layouts/app.blade.php`) handles this automatically:

- Sets `dir` attribute on `<html>` tag based on locale
- Adds `direction: rtl` to body when Arabic is active
- Includes `.rtl-flip` class for flipping icons/arrows

### RTL Table Styling

Tables automatically align to the right in RTL mode via CSS in the layout:

```css
[dir="rtl"] table th, 
[dir="rtl"] table td {
    text-align: right !important;
}
```

### Adding RTL-Specific Styles

For components that need different styling in RTL mode, use the `[dir="rtl"]` selector:

```css
[dir="rtl"] .some-class {
    /* RTL-specific styles */
}
```

---

## Architecture Notes

- **Web Controllers**: Located in `app/Http/Controllers/Web/` (implicit, using closures)
- **API Controllers**: Located in `app/Http/Controllers/Api/`
- **Models**: Located in `app/Models/`
- **Migrations**: Located in `database/migrations/`
- **Factories**: Located in `database/factories/`
- **Seeders**: Located in `database/seeders/`
- **Tests**: Located in `tests/Feature/` and `tests/Unit/`
- **Views**: Located in `resources/views/`
  - `resources/views/layouts/` - Layout templates
  - `resources/views/items/` - Items page
  - `resources/views/requests/` - Requests page
  - `resources/views/purchase-orders/` - Purchase orders page
  - `resources/views/invoices/` - Invoices page
