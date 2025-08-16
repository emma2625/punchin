<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all users
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true; // Can view any user
        }

        // Admin can view their own company's staff
        if ($user->role === UserRole::ADMIN && $model->company_id === $user->company_id) {
            return true;
        }

        // Staff can only view themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create other users.
     */
    public function create(User $user): bool
    {
        // Superadmin can create Admins
        // Admin can create Staff
        return in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $model): bool
    {
        // Superadmin can update any user
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin can update their company's staff
        if ($user->role === UserRole::ADMIN && $model->company_id === $user->company_id) {
            return true;
        }

        // Staff can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        // Superadmin can delete anyone
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin can delete staff in their company
        return $user->role === UserRole::ADMIN && $model->company_id === $user->company_id;
    }
}
