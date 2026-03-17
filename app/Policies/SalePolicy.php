<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id;
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id;
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id;
    }
}
