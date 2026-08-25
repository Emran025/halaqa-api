<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->unique('uq_session_reports_session');
            $table->string('state', 30)->default('draft');
            $table->text('summary')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('total_tasks')->default(0);
            $table->unsignedInteger('total_mistakes')->default(0);
            $table->json('mistake_counts')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('teacher_approved_by')->nullable();
            $table->dateTime('teacher_approved_at')->nullable();
            $table->text('teacher_approval_note')->nullable();
            $table->dateTime('student_acknowledged_at')->nullable();
            $table->text('student_acknowledgment_note')->nullable();
            $table->uuid('reopened_by')->nullable();
            $table->dateTime('reopened_at')->nullable();
            $table->string('reopen_reason', 1000)->nullable();
            $table->uuid('last_client_operation_id')->nullable();
            $table->uuid('last_operation_by_user_id')->nullable();
            $table->string('last_operation_type', 40)->nullable();
            $table->timestamps();
            $table->index(['state', 'updated_at'], 'idx_session_reports_state');
            $table->unique('last_client_operation_id', 'uq_session_reports_last_operation');
            $table->foreign('session_id')->references('id')->on('live_sessions')->cascadeOnDelete();
            $table->foreign('teacher_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reopened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('last_operation_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_reports');
    }
};
