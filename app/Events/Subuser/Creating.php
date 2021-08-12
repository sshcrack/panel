<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Events\Subuser;

use Kriegerhost\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Creating
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \Kriegerhost\Models\Subuser
     */
    public $subuser;

    /**
     * Create a new event instance.
     */
    public function __construct(Subuser $subuser)
    {
        $this->subuser = $subuser;
    }
}
