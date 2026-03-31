<?php

namespace Tests\Browser;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PurchaseOrderWorkflowTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $stockManager;

    protected User $hrManager;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockManager = User::factory()->create([
            'name' => 'Stock Manager',
            'username' => 'stock_manager',
            'email' => 'stock@school.com',
            'password' => bcrypt('password'),
            'role' => 'stock_manager',
        ]);

        $this->hrManager = User::factory()->create([
            'name' => 'HR Manager',
            'username' => 'hr_manager',
            'email' => 'hr@school.com',
            'password' => bcrypt('password'),
            'role' => 'hr_manager',
        ]);

        $this->item = Item::factory()->create([
            'designation' => 'Test Item',
            'quantity' => 100,
        ]);
    }

    public function test_full_po_approval_workflow(): void
    {
        $this->browse(function (Browser $browser) {
            $poId = $this->createPurchaseOrder($browser);
            $this->initialApproval($browser, $poId);
            $this->addProposals($browser, $poId);
            $this->finalApproval($browser, $poId);
        });
    }

    public function test_po_rejection_at_initial_approval(): void
    {
        $this->browse(function (Browser $browser) {
            $poId = $this->createPurchaseOrder($browser);
            $this->rejectAtInitial($browser, $poId);
        });
    }

    public function test_po_rejection_at_final_approval(): void
    {
        $this->browse(function (Browser $browser) {
            $poId = $this->createPurchaseOrder($browser);
            $this->initialApproval($browser, $poId);
            $this->addProposals($browser, $poId);
            $this->rejectAtFinal($browser, $poId);
        });
    }

    private function loginAsStockManager(Browser $browser): void
    {
        $browser->loginAs($this->stockManager)
            ->visit('/dashboard')
            ->assertSee('Stock Manager');
    }

    private function loginAsHRManager(Browser $browser): void
    {
        $browser->loginAs($this->hrManager)
            ->visit('/dashboard')
            ->assertSee('HR Manager');
    }

    private function createPurchaseOrder(Browser $browser): int
    {
        $this->loginAsStockManager($browser);

        $browser->visit('/purchase-orders')
            ->waitFor('@create-po-btn', 10)
            ->click('@create-po-btn')
            ->waitFor('@po-date-input')
            ->type('@po-date-input', now()->format('Y-m-d'))
            ->click('@add-item-btn')
            ->waitFor('@item-select-0')
            ->click('@item-select-0 + .ts-wrapper')
            ->type('.ts-wrapper .ts-control input', $this->item->designation)
            ->waitFor('.ts-dropdown')
            ->click('.ts-dropdown .ts-option:first-child')
            ->waitFor('#quantity-input-0')
            ->type('#quantity-input-0', '100')
            ->click('@submit-po-btn')
            ->waitForText('Purchase order created successfully', 10);

        $poId = $this->getLatestPOId();
        $browser->assertSee('pending_initial_approval');

        return $poId;
    }

    private function initialApproval(Browser $browser, int $poId): void
    {
        $this->loginAsHRManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@initial-approve-btn-{$poId}")
            ->waitForText('Purchase order approved successfully', 10)
            ->assertSee('initial_approved');
    }

    private function rejectAtInitial(Browser $browser, int $poId): void
    {
        $this->loginAsHRManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@initial-reject-btn-{$poId}")
            ->waitForText('Purchase order rejected', 10)
            ->assertSee('rejected');
    }

    private function addProposals(Browser $browser, int $poId): void
    {
        $this->loginAsStockManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@add-proposals-btn-{$poId}")
            ->waitFor('@proposal-supplier-0')
            ->type('@proposal-supplier-0', 'Supplier A')
            ->type('@proposal-price-0', '1500.00')
            ->click('@add-proposal-btn')
            ->type('@proposal-supplier-1', 'Supplier B')
            ->type('@proposal-price-1', '1450.00')
            ->click('@submit-proposals-btn')
            ->waitForText('Proposals submitted successfully', 10)
            ->assertSee('pending_final_approval');
    }

    private function finalApproval(Browser $browser, int $poId): void
    {
        $this->loginAsHRManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@select-supplier-btn-{$poId}")
            ->waitFor('@proposal-select-modal')
            ->click('@select-proposal-radio-0')
            ->click('@confirm-final-approval-btn')
            ->waitForText('Purchase order finalized successfully', 10)
            ->assertSee('final_approved');

        $this->markAsOrdered($browser, $poId);
    }

    private function rejectAtFinal(Browser $browser, int $poId): void
    {
        $this->loginAsHRManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@select-supplier-btn-{$poId}")
            ->waitFor('@reject-proposals-btn')
            ->click('@reject-proposals-btn')
            ->waitForText('Purchase order rejected', 10)
            ->assertSee('rejected');
    }

    private function markAsOrdered(Browser $browser, int $poId): void
    {
        $this->loginAsStockManager($browser);

        $browser->visit('/purchase-orders')
            ->waitForText("#{$poId}")
            ->click("@mark-ordered-btn-{$poId}")
            ->waitForText('Purchase order marked as ordered', 10)
            ->assertSee('ordered');
    }

    private function getLatestPOId(): int
    {
        return PurchaseOrder::latest('id')->first()->id;
    }
}
