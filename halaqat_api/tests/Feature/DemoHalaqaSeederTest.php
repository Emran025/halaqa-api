<?php

namespace Tests\Feature;

use Database\Seeders\DemoHalaqaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoHalaqaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_idempotent_halaqa_simulation_with_reference_ranges(): void
    {
        $this->seed(DemoHalaqaSeeder::class);

        $teacher = DB::table('users')->where('email', 'teacher.demo@halaqa.local')->first();
        $this->assertNotNull($teacher);
        $this->assertSame('teacher', $teacher->role);
        $this->assertTrue(Hash::check('HalaqaDemo!2026', $teacher->password));
        $this->assertDatabaseHas('teacher_profiles', ['user_id' => $teacher->id, 'teacher_code' => 'ITQAN-AR-01']);
        $this->assertDatabaseHas('halaqas', ['teacher_id' => $teacher->id, 'name' => 'حلقة الإتقان اليومية', 'status' => 'active']);
        $this->assertSame(6, DB::table('halaqa_memberships')->where('status', 'active')->count());
        $this->assertSame(7, DB::table('follow_up_plans')->where('status', 'active')->count());
        $this->assertSame(14, DB::table('follow_up_plan_details')->count());
        $this->assertGreaterThanOrEqual(276, DB::table('follow_up_items')->count());
        $this->assertSame(168, DB::table('daily_trackings')->count());
        $this->assertGreaterThan(250, DB::table('tracking_details')->count());
        $this->assertSame(3, DB::table('live_sessions')->count());
        $this->assertSame(2, DB::table('session_reports')->where('state', 'completed')->count());
        $this->assertSame(1, DB::table('registration_requests')->where('state', 'pending')->count());
        $this->assertSame(1054, DB::table('quran_range_units')->count());

        $this->seed(DemoHalaqaSeeder::class);

        $this->assertSame(8, DB::table('users')->count());
        $this->assertSame(6, DB::table('halaqa_memberships')->where('status', 'active')->count());
        $this->assertSame(14, DB::table('follow_up_plan_details')->count());
        $this->assertSame(3, DB::table('live_sessions')->count());
        $this->assertSame(5, DB::table('notifications')->count());
    }
}
