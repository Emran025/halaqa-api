<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mistakes', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->after('created_by_user_id');
            $table->unique('client_operation_id');
            $table->dropForeign(['ayah_id']);
            $table->foreign(['ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE mistakes ADD active_mistake_key TINYINT GENERATED ALWAYS AS (IF(deleted_at IS NULL, 1, NULL)) STORED');
            Schema::table('mistakes', function (Blueprint $table): void {
                $table->unique(['tracking_detail_id', 'ayah_id', 'word_index', 'mistake_type_id', 'active_mistake_key'], 'uq_mistake_position_type');
            });
        }

        Schema::table('task_notes', function (Blueprint $table): void {
            $table->uuid('client_operation_id')->after('author_id');
            $table->unique('client_operation_id');
            $table->foreign(['ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('mistakes', function (Blueprint $table): void {
                $table->dropUnique('uq_mistake_position_type');
            });
            DB::statement('ALTER TABLE mistakes DROP COLUMN active_mistake_key');
        }

        Schema::table('task_notes', function (Blueprint $table): void {
            $table->dropForeign(['ayah_id', 'edition_id']);
            $table->dropUnique(['client_operation_id']);
            $table->dropColumn('client_operation_id');
        });

        Schema::table('mistakes', function (Blueprint $table): void {
            $table->dropForeign(['ayah_id', 'edition_id']);
            $table->dropUnique(['client_operation_id']);
            $table->dropColumn('client_operation_id');
            $table->foreign('ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
        });
    }
};
