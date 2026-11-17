<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Services\SanctionService;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->sendingBlockedReason($user) === null;
    }

    public function close(User $user, Conversation $conversation): bool
    {
        return ! $conversation->closed_at && $conversation->sendingBlockedReason($user) === null;
    }

    public function reopen(User $user, Conversation $conversation): bool
    {
        return $conversation->closed_at !== null
            && $conversation->closed_by === $user->id
            && $conversation->isParticipant($user)
            && $user->isVerified()
            && ! app(SanctionService::class)->suspended($user)
            && ! app(SanctionService::class)->banned($user);
    }

    public function reportMessage(User $user, Conversation $conversation, ConversationMessage $message): bool
    {
        return $conversation->isParticipant($user)
            && $message->conversation_id === $conversation->id
            && $message->sender_id !== $user->id
            && $user->isVerified();
    }
}
