<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;

class TicketTypePolicy
{
    /**
     * イベントの主催者だけが券種を追加できる。
     */
    public function create(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }

    /**
     * イベントの主催者だけが券種を削除できる。
     */
    public function delete(User $user, TicketType $ticketType): bool
    {
        return $ticketType->event->isOwnedBy($user);
    }
}
