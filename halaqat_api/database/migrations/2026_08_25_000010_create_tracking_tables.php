<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_trackings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('membership_id');
            $table->uuid('student_id');
            $table->date('date');
            $table->string('attendance_type', 20);
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('behavior_note')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'date']);
            $table->foreign('membership_id')->references('id')->on('halaqa_memberships')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('tracking_details', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tracking_id');
            $table->uuid('session_task_id')->nullable();
            $table->unsignedTinyInteger('tracking_type_id');
            $table->unsignedBigInteger('from_unit_id')->nullable();
            $table->unsignedBigInteger('to_unit_id')->nullable();
            $table->decimal('actual_amount', 8, 2)->unsigned()->default(0);
            $table->string('state', 20)->default('draft');
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->decimal('gap', 8, 4)->nullable();
            $table->timestamps();
            $table->foreign('tracking_id')->references('id')->on('daily_trackings')->cascadeOnDelete();
            $table->foreign('session_task_id')->references('id')->on('session_tasks')->nullOnDelete();
            $table->foreign('tracking_type_id')->references('id')->on('tracking_types')->restrictOnDelete();
            $table->foreign('from_unit_id')->references('id')->on('quran_range_units')->nullOnDelete();
            $table->foreign('to_unit_id')->references('id')->on('quran_range_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_details');
        Schema::dropIfExists('daily_trackings');
    }
};
