<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Observers;

use Kriegerhost\Events;
use Kriegerhost\Models\Subuser;
use Kriegerhost\Notifications\AddedToServer;
use Kriegerhost\Notifications\RemovedFromServer;

class SubuserObserver
{
    /**
     * Listen to the Subuser creating event.
     */
    public function creating(Subuser $subuser)
    {
        event(new Events\Subuser\Creating($subuser));
    }

    /**
     * Listen to the Subuser created event.
     */
    public function created(Subuser $subuser)
    {
        event(new Events\Subuser\Created($subuser));

        $subuser->user->notify((new AddedToServer([
            'user' => $subuser->user->name_first,
            'name' => $subuser->server->name,
            'uuidShort' => $subuser->server->uuidShort,
        ])));
    }

    /**
     * Listen to the Subuser deleting event.
     */
    public function deleting(Subuser $subuser)
    {
        event(new Events\Subuser\Deleting($subuser));
    }

    /**
     * Listen to the Subuser deleted event.
     */
    public function deleted(Subuser $subuser)
    {
        event(new Events\Subuser\Deleted($subuser));

        $subuser->user->notify((new RemovedFromServer([
            'user' => $subuser->user->name_first,
            'name' => $subuser->server->name,
        ])));
    }
}
