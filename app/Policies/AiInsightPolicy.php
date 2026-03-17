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
        // FIX #10: restrito a admin pois disparar GenerateAiInsightJob
        // consome créditos de API de IA. Qualquer usuário autenticado
        // conseguia acionar esse job antes dessa restrição.
        return $user->role === 'admin';
    }

    public function delete(User $user, AiInsight $insight): bool
    {
        return $user->company_id === $insight->company_id;
    }

    public function restore(User $user, AiInsight $insight): bool
    {
        return $user->company_id === $insight->company_id
            && $user->role === 'admin';
    }
}