<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * イベントの主催者だけがイベントを複製できる。
     */
    public function duplicate(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }
}
