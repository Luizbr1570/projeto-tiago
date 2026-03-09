<?php

namespace App\Policies;

use App\Models\Followup;
use App\Models\User;

class FollowupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Followup $followup): bool
    {
        return $user->company_id === $followup->company_id;
    }

    public function delete(User $user, Followup $followup): bool
    {
        return $user->company_id === $followup->company_id;
    }
}
