<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_authenticated_merchant_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'generated_at',
                'period',
                'summary',
                'top_customers',
                'projected_overage_revenue',
                'usage_drops',
            ],
        ]);
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $this->getJson('/api/v1/merchants/'.$this->merchant->public_id.'/dashboard')
            ->assertUnauthorized();
    }

    public function test_cross_tenant_merchant_access_returns_not_found(): void
    {
        $other = Merchant::factory()->create();

        $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$other->public_id.'/dashboard',
        )->assertNotFound();
    }

    public function test_top_five_customers_ordered_by_current_month_usage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-20 10:00:00', 'UTC'));

        $customers = Customer::factory()->count(6)->forMerchant($this->merchant)->create();
        $usage = [100, 500, 300, 900, 200, 800];

        foreach ($customers as $i => $customer) {
            DailyUsage::factory()->create([
                'merchant_id' => $this->merchant->id,
                'customer_id' => $customer->id,
                'usage_date' => '2026-06-10',
                'total_quantity' => $usage[$i],
            ]);
        }

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $response->assertOk();
        $top = $response->json('data.top_customers');
        $this->assertCount(5, $top);
        $this->assertSame(900, $top[0]['usage_units']);
        $this->assertSame(800, $top[1]['usage_units']);
    }

    public function test_customers_with_no_usage_are_omitted_from_top_list(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', 'UTC'));

        Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $response->assertOk();
        $this->assertSame([], $response->json('data.top_customers'));
    }

    public function test_projected_overage_is_zero_without_active_subscription(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', 'UTC'));

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $response->assertJsonPath('data.projected_overage_revenue.amount', 0);
    }

    public function test_usage_drop_greater_than_fifty_percent_is_detected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00', 'UTC'));

        $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Drop Co']);

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-02-10',
            'total_quantity' => 1000,
        ]);

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-10',
            'total_quantity' => 400,
        ]);

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $drops = $response->json('data.usage_drops');
        $this->assertCount(1, $drops);
        $this->assertSame('Drop Co', $drops[0]['name']);
        $this->assertSame(-60, $drops[0]['percentage_change']);
    }

    public function test_exactly_fifty_percent_drop_is_not_included(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00', 'UTC'));

        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-02-10',
            'total_quantity' => 1000,
        ]);

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-10',
            'total_quantity' => 500,
        ]);

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $this->assertSame([], $response->json('data.usage_drops'));
    }

    public function test_previous_zero_usage_is_ignored_for_drops(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00', 'UTC'));

        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-10',
            'total_quantity' => 100,
        ]);

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $this->assertSame([], $response->json('data.usage_drops'));
    }

    public function test_shorter_previous_month_clamps_comparison_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'UTC'));

        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-02-28',
            'total_quantity' => 1000,
        ]);

        DailyUsage::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-31',
            'total_quantity' => 100,
        ]);

        $snapshot = app(DashboardService::class)->forMerchant($this->merchant);

        $this->assertCount(1, $snapshot->usageDrops);
        $this->assertSame(100, $snapshot->usageDrops[0]->currentUsageUnits);
        $this->assertSame(1000, $snapshot->usageDrops[0]->previousUsageUnits);
    }

    public function test_dashboard_does_not_query_raw_usage_events_table(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10 10:00:00', 'UTC'));

        DB::enableQueryLog();

        $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        )->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        $this->assertStringNotContainsString('usage_events', strtolower($queries));

        DB::disableQueryLog();
    }

    public function test_merchant_isolation_for_customer_usage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10 10:00:00', 'UTC'));

        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

        DailyUsage::factory()->create([
            'merchant_id' => $otherMerchant->id,
            'customer_id' => $otherCustomer->id,
            'usage_date' => '2026-06-05',
            'total_quantity' => 50_000,
        ]);

        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/merchants/'.$this->merchant->public_id.'/dashboard',
        );

        $this->assertSame([], $response->json('data.top_customers'));
    }
}
