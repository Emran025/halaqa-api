<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halaqas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->string('name', 150);
            $table->string('description', 1000)->nullable();
            $table->string('gender', 20);
            $table->string('country', 100);
            $table->string('residence', 200);
            $table->string('avatar_path', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('max_students')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teacher_id', 'status']);
            $table->index(['status', 'gender', 'country']);
            $table->foreign('teacher_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('halaqa_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('halaqa_id');
            $table->uuid('student_id');
            $table->string('status', 20)->default('active');
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();
            $table->index(['halaqa_id', 'status']);
            $table->index(['student_id', 'joined_at']);
            $table->foreign('halaqa_id')->references('id')->on('halaqas')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE halaqa_memberships ADD active_student_key TINYINT GENERATED ALWAYS AS (IF(status = 'active', 1, NULL)) STORED");
            DB::statement('CREATE UNIQUE INDEX uq_active_student_membership ON halaqa_memberships (student_id, active_student_key)');
        }

        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->foreign('requested_halaqa_id')->references('id')->on('halaqas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropForeign(['requested_halaqa_id']);
        });
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('DROP INDEX uq_active_student_membership ON halaqa_memberships');
        }
        Schema::dropIfExists('halaqa_memberships');
        Schema::dropIfExists('halaqas');
    }
};
