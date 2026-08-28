<?php

namespace Database\Seeders;

use App\Models\QuranEdition;
use App\Models\TrackingUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuranRangeUnitSeeder extends Seeder
{
    public function run(): void
    {
        $sourcePath = database_path('seeders/data/quran_range_units.json');
        $source = json_decode(file_get_contents($sourcePath), true, 512, JSON_THROW_ON_ERROR);
        $edition = QuranEdition::query()->where('code', 'hafs-uthmani')->firstOrFail();
        $unitTypeIds = TrackingUnit::query()->pluck('id', 'code')->all();
        $codeMap = ['half_hizb' => 'halfHizb', 'quarter_hizb' => 'quarterHizb'];

        if (! isset($source['units']) || ! is_array($source['units'])) {
            throw new RuntimeException('ملف نطاقات المصحف المرجعي لا يحتوي على وحدات صالحة.');
        }

        $rows = array_map(function (array $unit) use ($edition, $unitTypeIds, $codeMap): array {
            $unitCode = $codeMap[$unit['unit_type_code']] ?? $unit['unit_type_code'];
            if (! isset($unitTypeIds[$unitCode])) {
                throw new RuntimeException("نوع الوحدة المرجعية غير معروف: {$unitCode}");
            }

            return [
                'edition_id' => $edition->id,
                'unit_type_id' => $unitTypeIds[$unitCode],
                'unit_index' => $unit['unit_index'],
                'from_surah_id' => $unit['from_surah_id'],
                'from_ayah_id' => $unit['from_ayah_id'],
                'from_page' => $unit['from_page'],
                'to_surah_id' => $unit['to_surah_id'],
                'to_ayah_id' => $unit['to_ayah_id'],
                'to_page' => $unit['to_page'],
                'gap' => $unit['gap'],
            ];
        }, $source['units']);

        DB::table('quran_range_units')->upsert(
            $rows,
            ['edition_id', 'unit_type_id', 'unit_index'],
            ['from_surah_id', 'from_ayah_id', 'from_page', 'to_surah_id', 'to_ayah_id', 'to_page', 'gap'],
        );
    }
}
