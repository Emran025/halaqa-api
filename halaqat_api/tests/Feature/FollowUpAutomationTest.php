<?php

namespace Tests\Feature;

use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\FollowUpPlanDetail;
use App\Models\User;
use App\Services\Progress\FollowUpAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class FollowUpAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_plan_generates_one_next_item_and_retries_without_duplicates(): void
    {
        $student = User::factory()->student()->create();
        $plan = FollowUpPlan::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'created_by_user_id' => $student->id,
            'frequency' => 'daily',
            'status' => 'active',
            'timezone' => 'UTC',
            'starts_on' => '2026-08-25',
            'version' => 1,
        ]);
        $detail = FollowUpPlanDetail::create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'tracking_type_id' => 1,
            'tracking_unit_id' => 1,
            'amount' => 1,
            'sort_order' => 1,
        ]);
        $now = Carbon::parse('2026-08-26 10:00:00', 'UTC');
        $service = app(FollowUpAutomationService::class);

        $this->assertSame(1, $service->process($now));
        $this->assertSame(1, FollowUpItem::query()->where('plan_detail_id', $detail->id)->count());
        $this->assertSame('2026-08-26', Carbon::parse(FollowUpItem::query()->where('plan_detail_id', $detail->id)->value('scheduled_for'))->toDateString());
        $this->assertSame(0, $service->process($now));
        $this->assertSame(1, FollowUpItem::query()->where('plan_detail_id', $detail->id)->count());
    }

    public function test_due_item_is_marked_and_notified_once(): void
    {
        $student = User::factory()->student()->create();
        $plan = FollowUpPlan::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'created_by_user_id' => $student->id,
            'frequency' => 'daily',
            'status' => 'proposed',
            'timezone' => 'UTC',
            'version' => 1,
        ]);
        $detail = FollowUpPlanDetail::create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'tracking_type_id' => 1,
            'tracking_unit_id' => 1,
            'amount' => 1,
            'sort_order' => 1,
        ]);
        $item = FollowUpItem::create([
            'id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'plan_detail_id' => $detail->id,
            'student_id' => $student->id,
            'scheduled_for' => Carbon::parse('2026-08-26 09:30:00', 'UTC'),
            'timezone' => 'UTC',
            'state' => 'upcoming',
        ]);
        $now = Carbon::parse('2026-08-26 10:00:00', 'UTC');
        $service = app(FollowUpAutomationService::class);

        $service->process($now);
        $this->assertDatabaseHas('follow_up_items', ['id' => $item->id, 'state' => 'due']);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['type' => 'follow_up_due', 'user_id' => $student->id]);

        $service->process($now);
        $this->assertDatabaseCount('notifications', 1);
    }
}
