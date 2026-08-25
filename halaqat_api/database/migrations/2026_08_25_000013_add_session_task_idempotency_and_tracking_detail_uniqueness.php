<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->after('direct_p2p_only');
            $table->unique('client_operation_id');
        });

        Schema::table('session_tasks', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->after('session_id');
            $table->unique('client_operation_id');
        });

        Schema::table('tracking_details', function (Blueprint $table): void {
            $table->unique('session_task_id');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_details', function (Blueprint $table): void {
            $table->dropUnique(['session_task_id']);
        });
        Schema::table('session_tasks', function (Blueprint $table): void {
            $table->dropUnique(['client_operation_id']);
            $table->dropColumn('client_operation_id');
        });
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->dropUnique(['client_operation_id']);
            $table->dropColumn('client_operation_id');
        });
    }
};
