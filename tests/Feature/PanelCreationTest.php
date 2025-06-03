<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\ReorderInfo;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Http\Controllers\Customer\OrderController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

class PanelCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $orderController;
    protected $user;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderController = new OrderController();
        
        // Create test user and plan
        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'min_inbox' => 1,
            'max_inbox' => 10000
        ]);
    }

    /** @test */
    public function it_creates_single_panel_for_exactly_1790_inboxes()
    {
        // Arrange: Create order with exactly 1790 inboxes (1790 domains × 1 inbox each)
        $order = $this->createOrderWithInboxes(1790, 1);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should create exactly 1 panel
        $this->assertEquals(1, Panel::count());
        $panel = Panel::first();
        $this->assertEquals(1790, $panel->limit);
        $this->assertEquals(0, $panel->remaining_limit); // Fully used
        
        // Assert: Should create 1 order_panel record
        $this->assertEquals(1, OrderPanel::count());
        $orderPanel = OrderPanel::first();
        $this->assertEquals(1790, $orderPanel->space_assigned);
        
        // Assert: Should create 1 order_panel_split record
        $this->assertEquals(1, OrderPanelSplit::count());
        $split = OrderPanelSplit::first();
        $this->assertEquals(1790, count($split->domains));
    }

    /** @test */
    public function it_creates_multiple_panels_for_large_orders()
    {
        // Arrange: Create order with 4000 inboxes (4000 domains × 1 inbox each)
        $order = $this->createOrderWithInboxes(4000, 1);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should create 3 panels (1790 + 1790 + 420)
        $this->assertEquals(3, Panel::count());
        
        $panels = Panel::orderBy('created_at')->get();
        
        // First panel: 1790 capacity, 0 remaining
        $this->assertEquals(1790, $panels[0]->limit);
        $this->assertEquals(0, $panels[0]->remaining_limit);
        
        // Second panel: 1790 capacity, 0 remaining
        $this->assertEquals(1790, $panels[1]->limit);
        $this->assertEquals(0, $panels[1]->remaining_limit);
        
        // Third panel: 1790 capacity, 1370 remaining (420 used)
        $this->assertEquals(1790, $panels[2]->limit);
        $this->assertEquals(1370, $panels[2]->remaining_limit);
        
        // Assert: Should create 3 order_panel records
        $this->assertEquals(3, OrderPanel::count());
        
        // Assert: Should create 3 order_panel_split records
        $this->assertEquals(3, OrderPanelSplit::count());
        
        // Verify total domains assigned equals original count
        $totalDomainsAssigned = OrderPanelSplit::get()->sum(function($split) {
            return count($split->domains);
        });
        $this->assertEquals(4000, $totalDomainsAssigned);
    }

    /** @test */
    public function it_uses_existing_panel_for_small_orders()
    {
        // Arrange: Create existing panel with available space
        $existingPanel = Panel::create([
            'auto_generated_id' => 'EXISTING_PANEL',
            'title' => 'Existing Panel',
            'description' => 'Test panel',
            'limit' => 1790,
            'remaining_limit' => 1500,
            'is_active' => true,
            'created_by' => 'test'
        ]);
        
        // Create order with 500 inboxes (500 domains × 1 inbox each)
        $order = $this->createOrderWithInboxes(500, 1);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should not create new panel
        $this->assertEquals(1, Panel::count());
        
        // Assert: Existing panel should have reduced remaining capacity
        $existingPanel->refresh();
        $this->assertEquals(1000, $existingPanel->remaining_limit); // 1500 - 500
        
        // Assert: Should create 1 order_panel record linked to existing panel
        $this->assertEquals(1, OrderPanel::count());
        $orderPanel = OrderPanel::first();
        $this->assertEquals($existingPanel->id, $orderPanel->panel_id);
        $this->assertEquals(500, $orderPanel->space_assigned);
    }

    /** @test */
    public function it_splits_across_multiple_existing_panels()
    {
        // Arrange: Create two existing panels with limited space
        $panel1 = Panel::create([
            'auto_generated_id' => 'PANEL_1',
            'title' => 'Panel 1',
            'description' => 'Test panel 1',
            'limit' => 1790,
            'remaining_limit' => 300,
            'is_active' => true,
            'created_by' => 'test'
        ]);
        
        $panel2 = Panel::create([
            'auto_generated_id' => 'PANEL_2',
            'title' => 'Panel 2',
            'description' => 'Test panel 2',
            'limit' => 1790,
            'remaining_limit' => 400,
            'is_active' => true,
            'created_by' => 'test'
        ]);
        
        // Create order with 1000 inboxes (needs to split across panels)
        $order = $this->createOrderWithInboxes(1000, 1);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should create one additional panel for remaining space
        $this->assertEquals(3, Panel::count());
        
        // Assert: Should create 3 order_panel records (2 existing + 1 new)
        $this->assertEquals(3, OrderPanel::count());
        
        // Assert: Existing panels should be fully or partially used
        $panel1->refresh();
        $panel2->refresh();
        $this->assertLessThanOrEqual(300, 300 - $panel1->remaining_limit);
        $this->assertLessThanOrEqual(400, 400 - $panel2->remaining_limit);
    }

    /** @test */
    public function it_handles_multi_inbox_per_domain_correctly()
    {
        // Arrange: Create order with 500 domains × 3 inboxes = 1500 total inboxes
        $order = $this->createOrderWithInboxes(500, 3);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should use existing panel logic since 1500 < 1790
        $this->assertEquals(1, Panel::count());
        $panel = Panel::first();
        $this->assertEquals(290, $panel->remaining_limit); // 1790 - 1500
        
        // Assert: OrderPanel should reflect correct inboxes_per_domain
        $orderPanel = OrderPanel::first();
        $this->assertEquals(3, $orderPanel->inboxes_per_domain);
        $this->assertEquals(1500, $orderPanel->space_assigned);
        
        // Assert: Split should have 500 domains with 3 inboxes each
        $split = OrderPanelSplit::first();
        $this->assertEquals(500, count($split->domains));
        $this->assertEquals(3, $split->inboxes_per_domain);
    }

    /** @test */
    public function it_creates_new_panel_when_no_existing_panels_available()
    {
        // Arrange: No existing panels, create small order
        $order = $this->createOrderWithInboxes(100, 1);
        
        // Act: Trigger panel creation
        $this->orderController->pannelCreationAndOrderSplitOnPannels($order);
        
        // Assert: Should create new panel even for small order
        $this->assertEquals(1, Panel::count());
        $panel = Panel::first();
        $this->assertEquals(1790, $panel->limit);
        $this->assertEquals(1690, $panel->remaining_limit); // 1790 - 100
        
        // Assert: Order should be assigned to new panel
        $this->assertEquals(1, OrderPanel::count());
        $orderPanel = OrderPanel::first();
        $this->assertEquals(100, $orderPanel->space_assigned);
    }

    /**
     * Helper method to create order with specific inbox requirements
     */
    protected function createOrderWithInboxes($domainCount, $inboxesPerDomain)
    {
        // Create order
        $order = Order::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 100.00,
            'status' => 'pending',
            'currency' => 'USD'
        ]);
        
        // Generate domain list
        $domains = [];
        for ($i = 1; $i <= $domainCount; $i++) {
            $domains[] = "domain{$i}.com";
        }
        $domainsString = implode("\n", $domains);
        
        // Create reorder info
        ReorderInfo::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'order_id' => $order->id,
            'forwarding_url' => 'https://example.com',
            'hosting_platform' => 'test',
            'platform_login' => 'test@example.com',
            'platform_password' => 'password',
            'domains' => $domainsString,
            'sending_platform' => 'test',
            'sequencer_login' => 'seq@example.com',
            'sequencer_password' => 'password',
            'total_inboxes' => $domainCount * $inboxesPerDomain,
            'inboxes_per_domain' => $inboxesPerDomain,
            'first_name' => 'Test',
            'last_name' => 'User',
            'prefix_variant_1' => 'prefix1',
            'prefix_variant_2' => 'prefix2',
            'persona_password' => 'password',
            'email_persona_password' => 'password'
        ]);
        
        return $order->fresh(['reorderInfo']);
    }
}
