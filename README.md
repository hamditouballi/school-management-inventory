# School Inventory Management System

[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Pest](https://img.shields.io/badge/Testing-Pest-green.svg)](https://pestphp.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A professional, production-ready inventory management system designed specifically for school environments. This system streamlines the request, approval, and fulfillment process for school supplies, providing transparency and efficiency for teachers and administration alike.

---

## 📖 Table of Contents

- [Project Overview](#-project-overview)
- [System Architecture](#-system-architecture)
- [User Roles & Permissions](#-user-roles--permissions)
- [Business Workflows (Scenarios)](#-business-workflows-scenarios)
- [Database Structure](#-database-structure)
- [Testing Strategy](#-testing-strategy)
- [Installation Guide](#-installation-guide)
- [Deployment Guide](#-deployment-guide)
- [Security Measures](#-security-measures)
- [Future Improvements](#-future-improvements)

---

## 🎯 Project Overview

The **School Inventory System** is a centralized platform for managing school resources. It replaces manual, paper-based tracking with a digitized workflow that ensures every item is accounted for, from the moment a teacher requests it to the final invoice payment.

### Target Users

- **School Administration**: To monitor stock levels and spending.
- **Academic Staff**: To request materials for classrooms.
- **Logistics Teams**: To manage warehouse operations and procurement.

### Main Objectives

- **Efficiency**: Reduce time spent on manual approvals and paper trails.
- **Accuracy**: Prevent stock discrepancies through real-time tracking.
- **Financial Clarity**: Link spending directly to classroom needs and purchase orders.

### Business Value

By implementing this system, schools can significantly reduce waste, optimize their budget allocation based on real consumption data, and ensure that teachers always have the necessary tools for education without administrative delays.

---

## 🏗 System Architecture

The system is built on modern, scalable technologies:

- **Backend**: **Laravel 11** (PHP 8.2+) providing a robust RESTful API and a responsive Web interface.
- **Authentication**: **Laravel Sanctum** for secure, token-based API authentication and stateful session management.
- **Database**: Standardized schema utilizing **SQLite** for local development, compatible with MySQL/PostgreSQL for production.
- **File Storage**: Integrated with Laravel's **Public Disk** system for storing invoice images and item thumbnails.
- **Testing**: Powered by **Pest**, utilizing a scenario-based approach to ensure business logic correctness.

---

## 👥 User Roles & Permissions

The system implements a strict Role-Based Access Control (RBAC) model:

| Role                | Responsibility             | Allowed Actions                                   | Restricted Actions                   |
| :------------------ | :------------------------- | :------------------------------------------------ | :----------------------------------- |
| **Teacher**         | Classroom Resource User    | View items, Create requests, Track own requests   | Managing inventory, Modifying POs    |
| **Stock Manager**   | Warehouse & Inventory      | CRUD items, Approve/Fulfill requests, Create POs  | Financial reports, Final PO approval |
| **HR Manager**      | Administration & Oversight | Approve POs, Manage users, Full system visibility | Manual invoice entry                 |
| **Finance Manager** | Financial Control          | Manage invoices, Generate financial reports       | Modifying inventory levels           |

---

## 🔄 Business Workflows (Scenarios)

### 👨‍🏫 Teacher Scenario

1. **Login**: Securely access the portal.
2. **Browse**: Search the catalog for required items (e.g., Pencils, Paper).
3. **Request**: Create a multi-item request specifying quantities.
4. **Track**: Monitor the status as it moves from `pending` to `approved` and finally `fulfilled`.

### 📦 Stock Manager Scenario

1. **Inventory**: Add new items or update current stock levels.
2. **Procurement**: Identify low stock and create a **Purchase Order (PO)**.
3. **Fulfillment**: Review teacher requests, approve them, and generate a **Bon de Sortie** (Release Slip) upon physical delivery.
4. **Restock**: Mark POs as `ordered` once supplier confirmation is received.

### 👔 HR Manager Scenario

1. **Request Review**: Review and approve large or sensitive material requests (`hr_approved`).
2. **PO Approval**: Perform **Initial Approval** on purchase orders.
3. **Supplier Selection**: Review supplier proposals and grant **Final Approval** to the best offer.
4. **Auditing**: Monitor overall system activity and reports.

### 💰 Finance Manager Scenario

1. **Invoice Creation**: Link incoming supplier invoices to existing Purchase Orders.
2. **Manual Entry**: Create manual invoices for one-off school expenses.
3. **Documentation**: Upload and attach digital copies (images) of physical invoices.
4. **Reporting**: Generate spending reports and department consumption trends.

---

## 🗄 Database Structure

The database consists of **29 migrations** defining a highly relational schema:

- **Users & Departments**: Core identity and organizational structure.
- **Items**: Catalog of materials with `low_stock_threshold` and `quantity`.
- **Requests & Request Items**: Multi-item request tracking linked to users.
- **Purchase Orders & Suppliers**: Procurement workflow including multi-supplier proposals.
- **Invoices & Invoice Items**: Financial records linked to POs or standalone.
- **Bon de Sorties**: Legal/Administrative proof of item delivery from stock.

### Key Relationships

- `User` belongs to a `Department`.
- `Request` belongs to a `User` and has many `RequestItem`.
- `Invoice` optionally belongs to a `PurchaseOrder`.
- `PurchaseOrder` has many `PurchaseOrderSupplier` for bidding.

---

## 🧪 Testing Strategy

Quality is ensured through a comprehensive Pest-based testing suite:

- **Unit Tests**: Validating isolated logic in models and helpers.
- **Feature Tests**: Ensuring API endpoints respond correctly and respect authorization rules.
- **E2E Scenario Tests**: Role-based simulations (e.g., `TeacherScenarioTest.php`) that walk through entire business cycles.

### Running Tests

```bash
# Run all tests
php artisan test

# Run with step-by-step reporting
php artisan test:report
```

---

## 🚀 Installation Guide

Setting up the project locally is straightforward:

1. **Clone the Repository**

    ```bash
    git clone https://github.com/your-repo/school-inventory-system.git
    cd school-inventory-system
    ```

2. **Install Dependencies**

    ```bash
    composer install
    npm install && npm run build
    ```

3. **Environment Setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Initialize Database**

    ```bash
    # Creates SQLite DB and seeds with demo users/data
    php artisan migrate:fresh --seed
    ```

5. **Run the Server**
    ```bash
    php artisan serve
    ```

---

## 🌐 Deployment Guide (Local Server)

### Windows Server (IIS / Apache)

1. Ensure PHP 8.2+ and SQLite/MySQL extensions are enabled.
2. Point the Document Root to the `/public` directory.
3. Set appropriate permissions on `storage` and `bootstrap/cache`.

### Docker (Laravel Sail)

The project includes support for Docker:

```bash
./vendor/bin/sail up -d
```

### Production Tips

- Use a production-grade database (MySQL/PostgreSQL).
- Set `APP_DEBUG=false` and `APP_ENV=production`.
- Monitor logs in `storage/logs/laravel.log`.

---

## 🛡 Security Measures

- **Role-Based Authorization**: Controlled via Laravel **Policies** (`ItemPolicy`, `RequestPolicy`, etc.).
- **Sanctum Authentication**: Every API request requires a valid Bearer token.
- **Input Validation**: Strictly enforced via Request classes to prevent injection and malformed data.
- **Forbidden Actions**: Users are programmatically blocked from performing actions outside their role (e.g., Teachers cannot delete items).

---

## 🔮 Future Improvements

- **CI/CD Integration**: Automated testing and deployment pipelines.
- **Audit Logs**: Detailed history of every stock movement and status change.
- **Multi-School Support**: Architecture to handle multiple campuses in one instance.
- **Barcode/QR Support**: Scanning items for faster fulfillment and inventory counts.

---

**Developed for School Excellence.** 🏫✨
