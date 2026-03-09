<?php

namespace App\Policies;

use App\Models\AiInsight;
use App\Models\User;

class AiInsightPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, AiInsight $insight): bool
    {
        return $user->company_id === $insight->company_id;
    }
}
