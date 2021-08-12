<?php

namespace Kriegerhost\Tests\Browser\Processes\Dashboard;

use Kriegerhost\Tests\Browser\BrowserTestCase;

abstract class DashboardTestCase extends BrowserTestCase
{
    /**
     * @var \Kriegerhost\Models\User
     */
    protected $user;

    /**
     * Setup tests and provide a default user to calling functions.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->user();
    }
}
