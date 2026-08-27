<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_mushaf_states', function (Blueprint $table): void {
            $table->uuid('session_id')->primary();
            $table->unsignedSmallInteger('edition_id');
            $table->unsignedSmallInteger('page_number');
            $table->unsignedSmallInteger('surah_id')->nullable();
            $table->unsignedInteger('ayah_id')->nullable();
            $table->unsignedSmallInteger('range_from_page')->nullable();
            $table->unsignedInteger('range_from_ayah_id')->nullable();
            $table->unsignedSmallInteger('range_to_page')->nullable();
            $table->unsignedInteger('range_to_ayah_id')->nullable();
            $table->uuid('updated_by_user_id');
            $table->unsignedBigInteger('version')->default(1);
            $table->uuid('last_client_operation_id')->nullable();
            $table->timestamps();
            $table->index(['edition_id', 'page_number']);
            $table->index(['updated_by_user_id', 'updated_at']);
            $table->foreign('session_id')->references('id')->on('live_sessions')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('quran_editions')->restrictOnDelete();
            $table->foreign(['surah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_surahs')->restrictOnDelete();
            $table->foreign(['ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
            $table->foreign(['range_from_ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
            $table->foreign(['range_to_ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_mushaf_states');
    }
};
