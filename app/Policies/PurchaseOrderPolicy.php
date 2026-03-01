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
        return $user->role === 'stock_manager';
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Only stock_manager can update, and only if pending initial approval
        return $user->role === 'stock_manager' && $purchaseOrder->status === 'pending_initial_approval';
    }

    public function updateStatus(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // HR handles initial/final reviews. Stock manager marks final orders as ordered.
        if ($user->role === 'hr_manager') {
            return in_array($purchaseOrder->status, ['pending_initial_approval', 'pending_final_approval']);
        }
        if ($user->role === 'stock_manager') {
            return in_array($purchaseOrder->status, ['final_approved', 'ordered']);
        }
        return false;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->role === 'hr_manager') {
            return true;
        }
        return $user->role === 'stock_manager' && $purchaseOrder->status === 'pending_initial_approval';
    }

    public function reviewInitial(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->role === 'hr_manager' && $purchaseOrder->status === 'pending_initial_approval';
    }

    public function addProposals(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->role === 'stock_manager' && $purchaseOrder->status === 'initial_approved';
    }

    public function reviewFinal(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->role === 'hr_manager' && $purchaseOrder->status === 'pending_final_approval';
    }
}
