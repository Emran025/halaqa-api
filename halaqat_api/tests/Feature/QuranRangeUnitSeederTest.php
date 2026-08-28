<?php

namespace Tests\Feature;

use Database\Seeders\QuranRangeUnitSeeder;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuranRangeUnitSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_all_reference_ranges_and_can_be_run_twice(): void
    {
        $this->seed([QuranReferenceSeeder::class, QuranRangeUnitSeeder::class]);

        $this->assertDatabaseCount('quran_range_units', 1054);
        $this->assertDatabaseHas('quran_range_units', [
            'edition_id' => 1,
            'unit_type_id' => 1,
            'unit_index' => 1,
            'from_surah_id' => 1,
            'from_ayah_id' => 1,
            'from_page' => 1,
            'to_surah_id' => 2,
            'to_ayah_id' => 141,
            'to_page' => 21,
        ]);
        $this->assertSame(604, DB::table('quran_range_units')->where('unit_type_id', 5)->count());

        $this->seed(QuranRangeUnitSeeder::class);

        $this->assertDatabaseCount('quran_range_units', 1054);
    }
}
