<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_tasks', function (Blueprint $table): void {
            $table->unsignedSmallInteger('start_page')->nullable()->after('planned_to_unit_id');
            $table->unsignedInteger('start_ayah_id')->nullable()->after('start_page');
            $table->unsignedSmallInteger('end_page')->nullable()->after('start_ayah_id');
            $table->unsignedInteger('end_ayah_id')->nullable()->after('end_page');
            $table->unsignedSmallInteger('current_page')->nullable()->after('end_ayah_id');
            $table->unsignedInteger('current_ayah_id')->nullable()->after('current_page');
            $table->uuid('last_draft_operation_id')->nullable()->after('current_ayah_id');
            $table->index('last_draft_operation_id');
        });
    }

    public function down(): void
    {
        Schema::table('session_tasks', function (Blueprint $table): void {
            $table->dropIndex(['last_draft_operation_id']);
            $table->dropColumn(['start_page', 'start_ayah_id', 'end_page', 'end_ayah_id', 'current_page', 'current_ayah_id', 'last_draft_operation_id']);
        });
    }
};
