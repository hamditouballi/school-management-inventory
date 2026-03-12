# AGENTS.md - Development Guidelines for This Project

This is a Laravel 11 + PHP 8.2 application with a Vue.js/TailwindCSS frontend.

## Project Overview

- **Framework**: Laravel 11.31
- **PHP Version**: 8.2+
- **Testing**: Pest PHP
- **Code Style**: Laravel Pint (PSR-12 + Laravel conventions)
- **Frontend**: Vite + TailwindCSS + AlpineJS

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

This app supports multiple languages (English, Arabic, French). All user-facing strings should use translation keys.

### Translation Files

- **Location**: `resources/lang/{locale}/messages.php`
- **Supported Locales**: `en`, `ar`, `fr` (configured in `config/app.php`)

### Usage in Blade Templates

```blade
{{ __('messages.key_name') }}
```

### Usage in JavaScript

For JavaScript strings, use the translation in blade template:

```javascript
Notification.success('{{ __('messages.success_message') }}');
```

### Adding New Translations

1. Add the key to both `resources/lang/en/messages.php` (base) and `resources/lang/ar/messages.php`
2. Use descriptive keys following the existing pattern (e.g., `entity_action`)

### Clearing Cache

After modifying translations, clear the view cache:

```bash
php artisan view:clear
php artisan config:clear
```

### Example Translation Keys

```php
// In messages.php
'loading' => 'Loading...',
'error_loading' => 'Error loading',
'no_data_found' => 'No data found',
'success_message' => 'Operation completed successfully!',
'error_message' => 'An error occurred. Please try again.',
```

---

## Architecture Notes

- **Web Controllers**: Located in `app/Http/Controllers/Web/`
- **API Controllers**: Located in `app/Http/Controllers/Api/`
- **Models**: Located in `app/Models/`
- **Migrations**: Located in `database/migrations/`
- **Factories**: Located in `database/factories/`
- **Seeders**: Located in `database/seeders/`
- **Tests**: Located in `tests/Feature/` and `tests/Unit/`
