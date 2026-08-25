<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->nullable()->unique('uq_users_client_operation_id');
        });

        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->nullable();
            $table->unique(['student_id', 'client_operation_id'], 'uq_registration_student_operation');
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropUnique('uq_registration_student_operation');
            $table->dropColumn('client_operation_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('uq_users_client_operation_id');
            $table->dropColumn('client_operation_id');
        });
    }
};
