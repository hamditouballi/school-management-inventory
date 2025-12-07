<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        // Stock, Finance, and HR managers can view POs
        return in_array($user->role, ['stock_manager', 'finance_manager', 'hr_manager']);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['stock_manager', 'finance_manager', 'hr_manager']);
    }

    public function create(User $user): bool
    {
        // Only stock_manager can create purchase orders
        return in_array($user->role, ['stock_manager', 'hr_manager']);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Only stock_manager can update, and only if pending
        if ($user->role === 'stock_manager' && $purchaseOrder->status === 'pending_hr') {
            return true;
        }
        return $user->role === 'hr_manager';
    }

    public function updateStatus(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // HR can approve/reject, stock_manager can mark as ordered/received
        if ($user->role === 'hr_manager') {
            return true;
        }
        if ($user->role === 'stock_manager' && in_array($purchaseOrder->status, ['approved_hr', 'ordered'])) {
            return true;
        }
        return false;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Only stock_manager can delete pending POs, HR can delete any
        if ($user->role === 'hr_manager') {
            return true;
        }
        return $user->role === 'stock_manager' && $purchaseOrder->status === 'pending_hr';
    }
}
