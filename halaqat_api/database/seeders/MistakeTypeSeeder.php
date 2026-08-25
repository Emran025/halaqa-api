<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MistakeTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('mistake_types')->upsert([
            ['id' => 1, 'code' => 'none', 'label_ar' => 'غير مصنف', 'label_en' => 'Unclassified', 'sort_order' => 1],
            ['id' => 2, 'code' => 'memory', 'label_ar' => 'نسيان', 'label_en' => 'Memory', 'sort_order' => 2],
            ['id' => 3, 'code' => 'grammar', 'label_ar' => 'نحو', 'label_en' => 'Grammar', 'sort_order' => 3],
            ['id' => 4, 'code' => 'pronunciation', 'label_ar' => 'مخارج ونطق', 'label_en' => 'Pronunciation', 'sort_order' => 4],
            ['id' => 5, 'code' => 'timing', 'label_ar' => 'وقف وابتداء', 'label_en' => 'Timing', 'sort_order' => 5],
        ], ['id'], ['code', 'label_ar', 'label_en', 'sort_order']);
    }
}
