<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleBrowserTest extends DuskTestCase
{
    public function test_browser_can_open_google(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.google.com')
                ->assertSee('Google');
        });
    }
}
