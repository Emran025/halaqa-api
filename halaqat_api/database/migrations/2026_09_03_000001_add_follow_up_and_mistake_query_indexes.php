<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_trackings', function (Blueprint $table): void {
            $table->index(
                ['student_id', 'date', 'created_at'],
                'daily_trackings_student_date_created_idx'
            );
            $table->index(
                ['membership_id', 'date'],
                'daily_trackings_membership_date_idx'
            );
        });

        Schema::table('tracking_details', function (Blueprint $table): void {
            $table->index(['tracking_id', 'tracking_type_id'], 'tracking_details_tracking_type_idx');
            $table->index(['session_task_id', 'tracking_type_id'], 'tracking_details_task_type_idx');
        });

        Schema::table('mistakes', function (Blueprint $table): void {
            $table->index(['tracking_detail_id', 'created_by_user_id'], 'mistakes_detail_creator_idx');
        });

        Schema::table('task_notes', function (Blueprint $table): void {
            $table->index(['session_task_id', 'author_id'], 'task_notes_task_author_idx');
        });
    }

    public function down(): void
    {
        Schema::table('task_notes', function (Blueprint $table): void {
            $table->dropIndex('task_notes_task_author_idx');
        });
        Schema::table('mistakes', function (Blueprint $table): void {
            $table->dropIndex('mistakes_detail_creator_idx');
        });
        Schema::table('tracking_details', function (Blueprint $table): void {
            $table->dropIndex('tracking_details_tracking_type_idx');
            $table->dropIndex('tracking_details_task_type_idx');
        });
        Schema::table('daily_trackings', function (Blueprint $table): void {
            $table->dropIndex('daily_trackings_student_date_created_idx');
            $table->dropIndex('daily_trackings_membership_date_idx');
        });
    }
};
