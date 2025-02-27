<?php

namespace Kriegerhost\Tests\Browser\Processes\Authentication;

use Facebook\WebDriver\WebDriverKeys;
use Kriegerhost\Tests\Browser\BrowserTestCase;
use Kriegerhost\Tests\Browser\Pages\LoginPage;
use Kriegerhost\Tests\Browser\KriegerhostBrowser;

class LoginProcessTest extends BrowserTestCase
{
    private $user;

    /**
     * Setup tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->user();
    }

    /**
     * Test that a user can login successfully using their email address.
     */
    public function testLoginUsingEmail()
    {
        $this->browse(function (KriegerhostBrowser $browser) {
            $browser->visit(new LoginPage())
                ->waitFor('@username')
                ->type('@username', $this->user->email)
                ->type('@password', self::$userPassword)
                ->click('@loginButton')
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }

    /**
     * Test that a user can login successfully using their username.
     */
    public function testLoginUsingUsername()
    {
        $this->browse(function (KriegerhostBrowser $browser) {
            $browser->visit(new LoginPage())
                ->waitFor('@username')
                ->type('@username', $this->user->username)
                ->type('@password', self::$userPassword)
                ->click('@loginButton')
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }

    /**
     * Test that entering the wrong password shows the expected error and then allows
     * us to login without clearing the username field.
     */
    public function testLoginWithErrors()
    {
        $this->browse(function (KriegerhostBrowser $browser) {
            $browser->logout()
                ->visit(new LoginPage())
                ->waitFor('@username')
                ->type('@username', $this->user->email)
                ->type('@password', 'invalid')
                ->click('@loginButton')
                ->waitFor('.alert.error')
                ->assertSeeIn('.alert.error', trans('auth.failed'))
                ->assertValue('@username', $this->user->email)
                ->assertValue('@password', '')
                ->assertFocused('@password')
                ->type('@password', self::$userPassword)
                ->keys('@password', [WebDriverKeys::ENTER])
                ->waitForReload()
                ->assertPathIs('/')
                ->assertAuthenticatedAs($this->user);
        });
    }
}
