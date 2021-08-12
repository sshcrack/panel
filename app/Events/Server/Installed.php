<?php

namespace Kriegerhost\Events\Server;

use Kriegerhost\Events\Event;
use Kriegerhost\Models\Server;
use Illuminate\Queue\SerializesModels;

class Installed extends Event
{
    use SerializesModels;

    /**
     * @var \Kriegerhost\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     *
     * @var \Kriegerhost\Models\Server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
