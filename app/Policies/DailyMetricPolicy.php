<?php

namespace App\Policies;

use App\Models\DailyMetric;
use App\Models\User;

class DailyMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }
}
