<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_up_items', function (Blueprint $table): void {
            $table->uuid('last_client_operation_id')->nullable()->after('notification_sent_at');
            $table->uuid('last_operation_by_user_id')->nullable()->after('last_client_operation_id');
            $table->string('last_operation_type', 20)->nullable()->after('last_operation_by_user_id');
            $table->string('reschedule_reason', 500)->nullable()->after('last_operation_type');
            $table->unique('last_client_operation_id', 'uq_follow_up_items_last_operation');
            $table->foreign('last_operation_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('follow_up_items', function (Blueprint $table): void {
            $table->dropForeign(['last_operation_by_user_id']);
            $table->dropUnique('uq_follow_up_items_last_operation');
            $table->dropColumn(['last_client_operation_id', 'last_operation_by_user_id', 'last_operation_type', 'reschedule_reason']);
        });
    }
};
