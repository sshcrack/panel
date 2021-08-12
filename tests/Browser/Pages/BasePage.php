<?php

namespace Kriegerhost\Tests\Browser\Pages;

use Laravel\Dusk\Page;

abstract class BasePage extends Page
{
    /**
     * @return array
     */
    public static function siteElements()
    {
        return [
            '@@success' => '.alert.success[role="alert"]',
            '@@error' => '.alert.error[role="alert"]',
        ];
    }
}
