<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plans;
use App\Models\ProviderSubscription;
use App\Services\PlanProrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProrationTest extends TestCase
{
    use RefreshDatabase;

    protected $prorationService;
    protected $user;
    protected $basicPlan;
    protected $premiumPlan;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->prorationService = new PlanProrationService();
        
        // Create test user
        $this->user = User::factory()->create([
            'user_type' => 'provider'
        ]);
        
        // Create test plans
        $this->basicPlan = Plans::create([
            'title' => 'Basic Plan',
            'identifier' => 'basic',
            'amount' => 100,
            'duration' => 1,
            'type' => 'month',
            'status' => 1
        ]);
        
        $this->premiumPlan = Plans::create([
            'title' => 'Premium Plan',
            'identifier' => 'premium',
            'amount' => 200,
            'duration' => 1,
            'type' => 'month',
            'status' => 1
        ]);
    }

    /** @test */
    public function it_calculates_proration_after_10_days()
    {
        // Create active subscription 10 days ago
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now()->addDays(20);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(20, $prorationData['remaining_days']);
        $this->assertEquals(66.67, $prorationData['credit_applied']);
        $this->assertEquals(133.33, $prorationData['final_price']);
    }

    /** @test */
    public function it_calculates_proration_after_15_days()
    {
        $startDate = Carbon::now()->subDays(15);
        $endDate = Carbon::now()->addDays(15);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(15, $prorationData['remaining_days']);
        $this->assertEquals(50.0, $prorationData['credit_applied']);
        $this->assertEquals(150.0, $prorationData['final_price']);
    }

    /** @test */
    public function it_calculates_proration_after_25_days()
    {
        $startDate = Carbon::now()->subDays(25);
        $endDate = Carbon::now()->addDays(5);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(5, $prorationData['remaining_days']);
        $this->assertEquals(16.67, $prorationData['credit_applied']);
        $this->assertEquals(183.33, $prorationData['final_price']);
    }

    /** @test */
    public function it_does_not_apply_proration_for_expired_plan()
    {
        $startDate = Carbon::now()->subDays(35);
        $endDate = Carbon::now()->subDays(5);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertFalse($prorationData['has_proration']);
        $this->assertEquals(200, $prorationData['final_price']);
        $this->assertEquals(0, $prorationData['remaining_days']);
    }

    /** @test */
    public function it_applies_proration_for_cancelled_but_not_expired_plan()
    {
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now()->addDays(20);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'cancelled',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(20, $prorationData['remaining_days']);
    }

    /** @test */
    public function it_does_not_apply_proration_when_no_previous_subscription()
    {
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertFalse($prorationData['has_proration']);
        $this->assertEquals('normal', $prorationData['purchase_type']);
        $this->assertEquals(200, $prorationData['final_price']);
    }

    /** @test */
    public function it_prevents_negative_price()
    {
        // Create a high-value plan
        $expensivePlan = Plans::create([
            'title' => 'Expensive Plan',
            'identifier' => 'expensive',
            'amount' => 500,
            'duration' => 1,
            'type' => 'month',
            'status' => 1
        ]);
        
        // Create subscription with 1 day used
        $startDate = Carbon::now()->subDays(1);
        $endDate = Carbon::now()->addDays(29);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $expensivePlan->id,
            'title' => $expensivePlan->title,
            'identifier' => $expensivePlan->identifier,
            'amount' => $expensivePlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        // Try to upgrade to cheaper plan
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->basicPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(0, $prorationData['final_price']); // Should be 0, not negative
    }

    /** @test */
    public function it_builds_correct_other_detail_for_upgrade()
    {
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now()->addDays(20);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $otherDetail = $this->prorationService->buildOtherDetail($prorationData);
        
        $this->assertEquals('upgrade', $otherDetail['purchase_type']);
        $this->assertEquals('Basic Plan', $otherDetail['previous_plan']);
        $this->assertEquals(100, $otherDetail['previous_plan_price']);
        $this->assertEquals(200, $otherDetail['original_price']);
        $this->assertEquals(133.33, $otherDetail['paid_amount']);
        $this->assertEquals(66.67, $otherDetail['credit_applied']);
        $this->assertEquals(20, $otherDetail['remaining_days']);
        $this->assertStringContainsString('Basic Plan', $otherDetail['reason']);
    }

    /** @test */
    public function it_builds_correct_other_detail_for_normal_purchase()
    {
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $otherDetail = $this->prorationService->buildOtherDetail($prorationData);
        
        $this->assertEquals('normal', $otherDetail['purchase_type']);
        $this->assertEquals(200, $otherDetail['original_price']);
        $this->assertEquals(200, $otherDetail['paid_amount']);
    }

    /** @test */
    public function it_uses_latest_subscription_when_multiple_exist()
    {
        // Create old expired subscription
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'inactive',
            'start_at' => Carbon::now()->subDays(60),
            'end_at' => Carbon::now()->subDays(30)
        ]);
        
        // Create recent active subscription
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now()->addDays(20);
        
        ProviderSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->basicPlan->id,
            'title' => $this->basicPlan->title,
            'identifier' => $this->basicPlan->identifier,
            'amount' => $this->basicPlan->amount,
            'type' => 'month',
            'duration' => 1,
            'status' => 'active',
            'start_at' => $startDate,
            'end_at' => $endDate
        ]);
        
        $prorationData = $this->prorationService->calculateProration(
            $this->user->id, 
            $this->premiumPlan->id
        );
        
        $this->assertTrue($prorationData['has_proration']);
        $this->assertEquals(20, $prorationData['remaining_days']);
    }
}
