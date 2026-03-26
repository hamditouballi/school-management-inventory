<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DebugTest extends DuskTestCase
{
    use RefreshDatabase;

    public function test_login_and_check_button(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'name' => 'Stock Manager',
                'username' => 'stock_manager',
                'email' => 'stock@school.com',
                'role' => 'stock_manager',
                'password' => bcrypt('password'),
            ]);

            $browser->loginAs($user)
                ->visit('/dashboard')
                ->screenshot('debug_dashboard')
                ->pause(2000);

            $browser->visit('/purchase-orders')
                ->screenshot('debug_po_page')
                ->pause(2000);

            if ($browser->see('@create-po-btn')) {
                $browser->screenshot('debug_button_found');
            } else {
                $browser->screenshot('debug_button_not_found');
            }
        });
    }
}
