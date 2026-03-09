<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function transfer(User $user, ChatSession $session): bool
    {
        return $user->company_id === $session->company_id;
    }

    public function close(User $user, ChatSession $session): bool
    {
        return $user->company_id === $session->company_id;
    }
}
