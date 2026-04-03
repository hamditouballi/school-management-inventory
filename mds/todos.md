# Project Improvements Todo List

## Caching Implementation

### StatsController Caching
- [ ] Add Cache facade import to StatsController
- [ ] Implement caching for dashboard endpoint with event-based invalidation
- [ ] Implement caching for consumption endpoint
- [ ] Implement caching for spending endpoint
- [ ] Implement caching for consumptionByDepartment endpoint
- [ ] Implement caching for topItems endpoint
- [ ] Implement caching for lowStock endpoint
- [ ] Add ?refresh=true parameter support to all stats endpoints
- [ ] Run tests and lint to verify implementation

### Cache Invalidation (Event Listeners)
- [ ] Add event listeners for cache invalidation on Item model
- [ ] Add event listeners for cache invalidation on Request model
- [ ] Add event listeners for cache invalidation on PurchaseOrder model
- [ ] Add event listeners for cache invalidation on Invoice model
- [ ] Add event listeners for cache invalidation on BonDeSortie model

## Database Performance

### Indexes
- [ ] Add database index on bon_de_sorties.date
- [ ] Add database index on invoices.date
- [ ] Add database index on requests.status
- [ ] Add database index on purchase_orders.status

## Code Fixes

### Invoice Model
- [ ] Fix Invoice model getTotalAmountAttribute N+1 issue

### Item Model
- [ ] Fix incorrect invoices relationship in Item model (should be through invoice_items, not direct)

## Database Structure Issues

### Invoice Model Cleanup
- [ ] Consolidate invoices table - remove redundant quantity/price columns since invoice_items exists
- [ ] Standardize invoice foreign key naming (id_invoice_item instead of id_purchase_order_item)

### Purchase Orders
- [ ] Replace supplier string column with supplier_id foreign key
- [ ] Add soft deletes to purchase_orders table

### General Schema
- [ ] Add soft deletes to requests table
- [ ] Add soft deletes to items table
- [ ] Add soft deletes to invoices table
- [ ] Add soft deletes to bon_de_sorties table
- [ ] Increase decimal precision for total_amount (12,2 instead of 10,2)

### Data Consistency
- [ ] Standardize foreign key naming across all tables (id_* prefix for user references)

## Enhancements

### StatsController
- [ ] Add date filtering to consumptionByDepartment endpoint
- [ ] Add authorization checks to stats endpoints
