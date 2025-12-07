<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;

class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        // All can view (filtered in controller)
        return true;
    }

    public function view(User $user, RequestModel $request): bool
    {
        // Teachers can only view their own requests
        if ($user->role === 'teacher') {
            return $request->user_id === $user->id;
        }
        // Managers can view all
        return in_array($user->role, ['stock_manager', 'finance_manager', 'hr_manager']);
    }

    public function create(User $user): bool
    {
        // All authenticated users can create requests
        return true;
    }

    public function updateStatus(User $user, RequestModel $request): bool
    {
        // Only stock_manager and hr_manager can update request status
        return in_array($user->role, ['stock_manager', 'hr_manager']);
    }

    public function fulfill(User $user, RequestModel $request): bool
    {
        // Only stock_manager can fulfill requests
        return in_array($user->role, ['stock_manager', 'hr_manager']);
    }

    public function delete(User $user, RequestModel $request): bool
    {
        // Teachers can delete their own pending requests
        if ($user->role === 'teacher' && $request->status === 'pending') {
            return $request->user_id === $user->id;
        }
        // HR can delete any
        return $user->role === 'hr_manager';
    }
}
