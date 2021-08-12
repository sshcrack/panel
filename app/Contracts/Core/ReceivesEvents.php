<?php

namespace Kriegerhost\Contracts\Core;

use Kriegerhost\Events\Event;

interface ReceivesEvents
{
    /**
     * Handles receiving an event from the application.
     */
    public function handle(Event $notification): void;
}
