<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->unsignedTinyInteger('tracking_type_id');
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->unsignedBigInteger('planned_from_unit_id')->nullable();
            $table->unsignedBigInteger('planned_to_unit_id')->nullable();
            $table->decimal('planned_amount', 8, 2)->unsigned()->nullable();
            $table->decimal('actual_amount', 8, 2)->unsigned()->nullable();
            $table->string('state', 20)->default('draft');
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->decimal('gap', 8, 4)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'sequence_no']);
            $table->foreign('session_id')->references('id')->on('live_sessions')->cascadeOnDelete();
            $table->foreign('tracking_type_id')->references('id')->on('tracking_types')->restrictOnDelete();
            $table->foreign('planned_from_unit_id')->references('id')->on('quran_range_units')->nullOnDelete();
            $table->foreign('planned_to_unit_id')->references('id')->on('quran_range_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_tasks');
    }
};
