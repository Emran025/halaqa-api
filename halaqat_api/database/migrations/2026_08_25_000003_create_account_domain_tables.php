<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table): void {
            $table->uuid('user_id')->primary();
            $table->string('teacher_code', 40)->unique();
            $table->string('qualification', 250);
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->time('available_time')->nullable();
            $table->unsignedSmallInteger('max_halaqas')->default(0);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->uuid('user_id')->primary();
            $table->string('memorization_level', 120)->nullable();
            $table->string('review_level', 120)->nullable();
            $table->decimal('memorized_juz_count', 4, 1)->unsigned()->nullable();
            $table->text('previous_memorization_notes')->nullable();
            $table->text('stop_reasons')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('teacher_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('teacher_id');
            $table->string('name', 250);
            $table->string('certificate_type', 100);
            $table->string('certificate_type_other', 150)->nullable();
            $table->string('riwayah', 100)->nullable();
            $table->string('issuing_place', 200)->nullable();
            $table->date('issuing_date')->nullable();
            $table->string('storage_disk', 50)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teacher_id', 'deleted_at']);
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('student_availability_profiles', function (Blueprint $table): void {
            $table->uuid('student_id')->primary();
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedSmallInteger('preferred_session_duration_minutes')->default(30);
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('student_availability_slots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('student_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('available_from');
            $table->time('available_to');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'day_of_week', 'available_from', 'available_to'], 'uq_student_availability_slot');
            $table->index(['student_id', 'day_of_week']);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('tracking_types', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('label_ar', 80);
            $table->string('label_en', 80);
            $table->unsignedTinyInteger('sort_order');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('tracking_units', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('label_ar', 80);
            $table->string('label_en', 80);
            $table->unsignedTinyInteger('sort_order');
            $table->boolean('is_active')->default(true);
        });

        DB::table('tracking_types')->insert([
            ['id' => 1, 'code' => 'memorization', 'label_ar' => 'حفظ', 'label_en' => 'Memorization', 'sort_order' => 1, 'is_active' => true],
            ['id' => 2, 'code' => 'review', 'label_ar' => 'مراجعة', 'label_en' => 'Review', 'sort_order' => 2, 'is_active' => true],
            ['id' => 3, 'code' => 'recitation', 'label_ar' => 'تلاوة', 'label_en' => 'Recitation', 'sort_order' => 3, 'is_active' => true],
        ]);
        DB::table('tracking_units')->insert([
            ['id' => 1, 'code' => 'juz', 'label_ar' => 'جزء', 'label_en' => 'Juz', 'sort_order' => 1, 'is_active' => true],
            ['id' => 2, 'code' => 'hizb', 'label_ar' => 'حزب', 'label_en' => 'Hizb', 'sort_order' => 2, 'is_active' => true],
            ['id' => 3, 'code' => 'halfHizb', 'label_ar' => 'نصف حزب', 'label_en' => 'Half Hizb', 'sort_order' => 3, 'is_active' => true],
            ['id' => 4, 'code' => 'quarterHizb', 'label_ar' => 'ربع حزب', 'label_en' => 'Quarter Hizb', 'sort_order' => 4, 'is_active' => true],
            ['id' => 5, 'code' => 'page', 'label_ar' => 'صفحة', 'label_en' => 'Page', 'sort_order' => 5, 'is_active' => true],
        ]);

        Schema::create('follow_up_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('created_by_user_id');
            $table->uuid('source_registration_request_id')->nullable();
            $table->string('frequency', 30);
            $table->string('status', 20)->default('draft');
            $table->string('timezone', 64)->default('UTC');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('approved_by_user_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('follow_up_plan_details', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->unsignedTinyInteger('tracking_type_id');
            $table->unsignedTinyInteger('tracking_unit_id');
            $table->decimal('amount', 8, 2)->unsigned();
            $table->string('notes', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();
            $table->index(['plan_id', 'sort_order']);
            $table->foreign('plan_id')->references('id')->on('follow_up_plans')->cascadeOnDelete();
            $table->foreign('tracking_type_id')->references('id')->on('tracking_types')->restrictOnDelete();
            $table->foreign('tracking_unit_id')->references('id')->on('tracking_units')->restrictOnDelete();
        });

        Schema::create('registration_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('teacher_id')->nullable();
            $table->string('teacher_code_snapshot', 40)->nullable();
            $table->uuid('requested_halaqa_id')->nullable();
            $table->string('routing_mode', 30);
            $table->string('state', 30)->default('pending');
            $table->string('public_message', 1000)->nullable();
            $table->string('decision_note', 2000)->nullable();
            $table->uuid('decided_by_teacher_id')->nullable();
            $table->dateTime('submitted_at');
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['teacher_id', 'state', 'submitted_at']);
            $table->index(['routing_mode', 'state', 'submitted_at']);
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('decided_by_teacher_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('follow_up_plans', function (Blueprint $table): void {
            $table->foreign('source_registration_request_id')->references('id')->on('registration_requests')->nullOnDelete();
        });

        Schema::create('registration_request_profiles', function (Blueprint $table): void {
            $table->uuid('registration_request_id')->primary();
            $table->string('gender', 20);
            $table->date('birth_date');
            $table->string('country', 100);
            $table->string('city', 100);
            $table->string('residence', 200)->nullable();
            $table->string('phone', 30);
            $table->string('phone_zone', 8);
            $table->string('whatsapp_phone', 30)->nullable();
            $table->string('whatsapp_zone', 8)->nullable();
            $table->string('memorization_level', 120)->nullable();
            $table->string('review_level', 120)->nullable();
            $table->decimal('memorized_juz_count', 4, 1)->unsigned()->nullable();
            $table->text('previous_memorization_notes')->nullable();
            $table->text('profile_bio')->nullable();
            $table->timestamps();
            $table->foreign('registration_request_id')->references('id')->on('registration_requests')->cascadeOnDelete();
        });

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->uuid('user_id');
            $table->string('idempotency_key', 120);
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'uq_idempotency_user_key');
            $table->index('expires_at');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('tokenable_type');
            $table->uuid('tokenable_id');
            $table->string('name');
            $table->char('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('registration_request_profiles');
        Schema::table('follow_up_plans', function (Blueprint $table): void {
            $table->dropForeign(['source_registration_request_id']);
        });
        Schema::dropIfExists('registration_requests');
        Schema::dropIfExists('follow_up_plan_details');
        Schema::dropIfExists('follow_up_plans');
        Schema::dropIfExists('tracking_units');
        Schema::dropIfExists('tracking_types');
        Schema::dropIfExists('student_availability_slots');
        Schema::dropIfExists('student_availability_profiles');
        Schema::dropIfExists('teacher_documents');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('teacher_profiles');
    }
};
