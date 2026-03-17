<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $currentUser, User $target): bool
    {
        return $currentUser->role === 'admin'
            && $currentUser->company_id === $target->company_id;
    }

    public function delete(User $currentUser, User $target): bool
    {
        return $currentUser->role === 'admin'
            && $currentUser->company_id === $target->company_id
            && $currentUser->id !== $target->id;
    }

    public function restore(User $currentUser, User $target): bool
    {
        return $currentUser->role === 'admin'
            && $currentUser->company_id === $target->company_id;
    }
}