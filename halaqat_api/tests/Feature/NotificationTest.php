<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_notifications_and_can_filter_unread(): void
    {
        $student = $this->registerStudent('notification_student');
        $other = $this->registerStudent('notification_other');
        $ownUnread = $this->createNotification($student['user']['id'], 'follow_up_due', 'موعد متابعة', 'لديك عنصر متابعة مستحق', ['entity_type' => 'follow_up_item', 'entity_id' => (string) Str::uuid(), 'action' => 'open', 'follow_up_item_id' => (string) Str::uuid()]);
        $ownRead = $this->createNotification($student['user']['id'], 'reminder', 'تذكير', 'تذكير سابق', ['entity_type' => 'live_session', 'entity_id' => (string) Str::uuid(), 'action' => 'open'], now()->subMinute());
        $this->createNotification($other['user']['id'], 'system', 'خاص', 'لا يظهر', ['entity_type' => 'halaqa', 'entity_id' => (string) Str::uuid(), 'action' => 'open']);

        $this->withToken($student['token'])->getJson('/api/v1/notifications?unread_only=true')
            ->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.id', $ownUnread->id)->assertJsonPath('notifications.0.payload.entity_type', 'follow_up_item')->assertJsonPath('notifications.0.payload.session_id', null)->assertJsonMissingPath('data')->assertJsonStructure(['notifications' => ['*' => ['id', 'type', 'title', 'body', 'payload', 'read_at', 'created_at']], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
        $this->withToken($student['token'])->getJson('/api/v1/notifications?unexpected=true')->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
        $this->assertDatabaseHas('notifications', ['id' => $ownRead->id, 'read_at' => $ownRead->read_at]);
    }

    public function test_mark_one_read_is_idempotent_and_mark_all_is_scoped_to_user(): void
    {
        $student = $this->registerStudent('notification_reader');
        $other = $this->registerStudent('notification_other_reader');
        $first = $this->createNotification($student['user']['id'], 'system', 'الأول', 'الأول', ['entity_type' => 'halaqa', 'entity_id' => (string) Str::uuid(), 'action' => 'open']);
        $second = $this->createNotification($student['user']['id'], 'system', 'الثاني', 'الثاني', ['entity_type' => 'membership', 'entity_id' => (string) Str::uuid(), 'action' => 'review']);
        $outside = $this->createNotification($other['user']['id'], 'system', 'الخارجي', 'الخارجي', ['entity_type' => 'task', 'entity_id' => (string) Str::uuid(), 'action' => 'open']);

        $this->withToken($student['token'])->postJson('/api/v1/notifications/'.$first->id.'/read')->assertNoContent();
        $this->withToken($student['token'])->postJson('/api/v1/notifications/'.$first->id.'/read')->assertNoContent();
        $this->assertNotNull(Notification::query()->findOrFail($first->id)->read_at);
        app('auth')->forgetGuards();
        $this->withToken($other['token'])->postJson('/api/v1/notifications/'.$first->id.'/read')->assertNotFound();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/notifications/read-all')->assertNoContent();
        $this->assertNotNull(Notification::query()->findOrFail($second->id)->read_at);
        $this->assertNull(Notification::query()->findOrFail($outside->id)->read_at);
    }

    private function createNotification(string $userId, string $type, string $title, string $body, array $payload, $readAt = null): Notification
    {
        return Notification::create(['id' => (string) Str::uuid(), 'user_id' => $userId, 'type' => $type, 'title' => $title, 'body' => $body, 'payload' => $payload, 'read_at' => $readAt]);
    }

    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => $prefix, 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
