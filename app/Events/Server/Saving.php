<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Events\Server;

use Kriegerhost\Models\Server;
use Illuminate\Queue\SerializesModels;

class Saving
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \Kriegerhost\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
