<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Qualquer usuário autenticado da empresa pode ver e criar leads.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }
    public function restore(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }
}
