<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->uuid('plan_detail_id');
            $table->uuid('student_id');
            $table->uuid('halaqa_id')->nullable();
            $table->dateTime('scheduled_for');
            $table->string('timezone', 64);
            $table->string('state', 20)->default('upcoming');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('skipped_at')->nullable();
            $table->string('skip_reason', 500)->nullable();
            $table->uuid('rescheduled_from_id')->nullable();
            $table->dateTime('notification_sent_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'scheduled_for', 'state']);
            $table->index(['halaqa_id', 'scheduled_for', 'state']);
            $table->index(['plan_id', 'scheduled_for']);
            $table->foreign('plan_id')->references('id')->on('follow_up_plans')->restrictOnDelete();
            $table->foreign('plan_detail_id')->references('id')->on('follow_up_plan_details')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('halaqa_id')->references('id')->on('halaqas')->nullOnDelete();
            $table->foreign('rescheduled_from_id')->references('id')->on('follow_up_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_items');
    }
};
