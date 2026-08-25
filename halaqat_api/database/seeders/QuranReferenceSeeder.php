<?php

namespace Database\Seeders;

use App\Models\QuranAyah;
use App\Models\QuranEdition;
use App\Models\QuranPage;
use App\Models\QuranSurah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuranReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $source = json_decode(file_get_contents(database_path('seeders/data/quran_reference.json')), true, 512, JSON_THROW_ON_ERROR);
        $edition = QuranEdition::updateOrCreate(['code' => $source['edition']['code']], array_merge($source['edition'], ['is_default' => true]));
        $pages = [];
        DB::transaction(function () use ($source, $edition, &$pages): void {
            foreach ($source['surahs'] as $surahData) {
                $ayahs = $surahData['ayahs'];
                QuranSurah::updateOrCreate(['id' => $surahData['number'], 'edition_id' => $edition->id], [
                    'name_ar' => $surahData['name_ar'], 'name_en' => $surahData['name_en'], 'name_en_translation' => $surahData['name_en_translation'],
                    'number_of_ayahs' => count($ayahs), 'first_page_starts_at' => $ayahs[0]['page_number'], 'revelation_type' => $surahData['revelation_type'],
                ]);
                foreach ($ayahs as $ayah) {
                    $ayahRecord = $ayah;
                    unset($ayahRecord['number']);
                    QuranAyah::updateOrCreate(['id' => $ayah['number'], 'edition_id' => $edition->id], array_merge($ayahRecord, ['surah_id' => $surahData['number']]));
                    $pages[$ayah['page_number']][] = $ayah['text_uthmani'];
                }
            }
            foreach ($pages as $number => $texts) {
                QuranPage::updateOrCreate(['edition_id' => $edition->id, 'page_number' => $number], ['page_text' => implode(' ', $texts)]);
            }
        });
    }
}
