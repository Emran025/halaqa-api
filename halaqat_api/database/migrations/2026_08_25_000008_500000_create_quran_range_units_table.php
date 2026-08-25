<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_range_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('edition_id');
            $table->unsignedTinyInteger('unit_type_id');
            $table->unsignedSmallInteger('unit_index');
            $table->unsignedSmallInteger('from_surah_id');
            $table->unsignedInteger('from_ayah_id');
            $table->unsignedSmallInteger('from_page');
            $table->unsignedSmallInteger('to_surah_id');
            $table->unsignedInteger('to_ayah_id');
            $table->unsignedSmallInteger('to_page');
            $table->decimal('gap', 8, 4)->default(0);
            $table->unique(['edition_id', 'unit_type_id', 'unit_index']);
            $table->foreign('edition_id')->references('id')->on('quran_editions')->restrictOnDelete();
            $table->foreign('unit_type_id')->references('id')->on('tracking_units')->restrictOnDelete();
            $table->foreign(['from_surah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_surahs')->restrictOnDelete();
            $table->foreign(['to_surah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_surahs')->restrictOnDelete();
            $table->foreign(['from_ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
            $table->foreign(['to_ayah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_ayahs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_range_units');
    }
};
