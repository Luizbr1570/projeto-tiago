<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->company_id === $conversation->company_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->company_id === $conversation->company_id;
    }
}
