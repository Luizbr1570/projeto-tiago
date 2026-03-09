<?php

namespace App\Policies;

use App\Models\ProductInterest;
use App\Models\User;

class ProductInterestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, ProductInterest $interest): bool
    {
        return $user->company_id === $interest->company_id;
    }
}
