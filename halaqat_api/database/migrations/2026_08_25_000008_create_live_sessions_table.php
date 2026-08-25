<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('halaqa_id');
            $table->uuid('teacher_id');
            $table->uuid('student_id');
            $table->uuid('follow_up_item_id')->nullable();
            $table->unsignedTinyInteger('task_type_id');
            $table->string('state', 40)->default('requested');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('requested_at');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('connected_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('end_reason', 500)->nullable();
            $table->boolean('direct_p2p_only')->default(true);
            $table->timestamps();
            $table->index(['teacher_id', 'state', 'scheduled_at']);
            $table->index(['student_id', 'state', 'scheduled_at']);
            $table->foreign('halaqa_id')->references('id')->on('halaqas')->restrictOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('follow_up_item_id')->references('id')->on('follow_up_items')->nullOnDelete();
            $table->foreign('task_type_id')->references('id')->on('tracking_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
