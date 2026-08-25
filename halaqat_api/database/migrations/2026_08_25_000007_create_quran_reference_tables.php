<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_editions', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('code', 60)->unique();
            $table->string('name_ar', 150);
            $table->string('script_name', 100);
            $table->string('version', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('quran_surahs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id');
            $table->unsignedSmallInteger('edition_id');
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->string('name_en_translation', 200);
            $table->unsignedSmallInteger('number_of_ayahs');
            $table->unsignedSmallInteger('first_page_starts_at');
            $table->string('revelation_type', 20);
            $table->primary(['id', 'edition_id']);
            $table->foreign('edition_id')->references('id')->on('quran_editions')->restrictOnDelete();
        });
        Schema::create('quran_pages', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedSmallInteger('edition_id');
            $table->unsignedSmallInteger('page_number');
            $table->longText('page_text');
            $table->timestamps();
            $table->unique(['edition_id', 'page_number']);
            $table->foreign('edition_id')->references('id')->on('quran_editions')->restrictOnDelete();
        });
        Schema::create('quran_ayahs', function (Blueprint $table): void {
            $table->unsignedInteger('id');
            $table->unsignedSmallInteger('edition_id');
            $table->unsignedSmallInteger('surah_id');
            $table->unsignedSmallInteger('number_in_surah');
            $table->text('text_uthmani');
            $table->text('text_emlaey');
            $table->unsignedSmallInteger('page_number');
            $table->unsignedTinyInteger('juz_number');
            $table->boolean('has_sajda')->default(false);
            $table->primary(['id', 'edition_id']);
            $table->unique(['edition_id', 'surah_id', 'number_in_surah']);
            $table->foreign('edition_id')->references('id')->on('quran_editions')->restrictOnDelete();
            $table->foreign(['surah_id', 'edition_id'])->references(['id', 'edition_id'])->on('quran_surahs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_ayahs');
        Schema::dropIfExists('quran_pages');
        Schema::dropIfExists('quran_surahs');
        Schema::dropIfExists('quran_editions');
    }
};
