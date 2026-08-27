<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->uuid('last_client_operation_id')->nullable()->after('client_operation_id');
            $table->uuid('last_operation_by_user_id')->nullable()->after('last_client_operation_id');
            $table->string('last_operation_type', 40)->nullable()->after('last_operation_by_user_id');
            $table->unique('last_client_operation_id', 'uq_live_sessions_last_operation');
            $table->foreign('last_operation_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->dropForeign(['last_operation_by_user_id']);
            $table->dropUnique('uq_live_sessions_last_operation');
            $table->dropColumn(['last_client_operation_id', 'last_operation_by_user_id', 'last_operation_type']);
        });
    }
};
