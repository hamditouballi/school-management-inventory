<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        // Finance and HR managers can view invoices
        return in_array($user->role, ['finance_manager', 'hr_manager', 'stock_manager']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['finance_manager', 'hr_manager', 'stock_manager']);
    }

    public function create(User $user): bool
    {
        // Only finance_manager can create invoices
        return in_array($user->role, ['finance_manager', 'hr_manager']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['finance_manager', 'hr_manager']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['finance_manager', 'hr_manager']);
    }
}
