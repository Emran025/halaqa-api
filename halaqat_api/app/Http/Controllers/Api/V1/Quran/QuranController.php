<?php

namespace App\Http\Controllers\Api\V1\Quran;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Quran\QuranResource;
use App\Models\QuranAyah;
use App\Models\QuranEdition;
use App\Models\QuranPage;
use App\Models\QuranSurah;
use Illuminate\Http\Request;

class QuranController extends Controller
{
    public function surahs(Request $request): array
    {
        $editionId = (int) $request->input('edition_id', QuranEdition::query()->where('is_default', true)->value('id'));

        return ['surahs' => QuranResource::collection(QuranSurah::query()->where('edition_id', $editionId)->orderBy('id')->get())];
    }

    public function page(int $pageNumber, Request $request): array
    {
        $editionId = (int) $request->input('edition_id', QuranEdition::query()->where('is_default', true)->value('id'));
        $page = QuranPage::query()->where('edition_id', $editionId)->where('page_number', $pageNumber)->firstOrFail();
        $page->setRelation('ayahs', QuranAyah::query()->where('edition_id', $editionId)->where('page_number', $pageNumber)->orderBy('id')->get());

        return ['quran_page' => QuranResource::make($page)];

    }

    public function ayah(int $ayahId, Request $request): array
    {
        $editionId = (int) $request->input('edition_id', QuranEdition::query()->where('is_default', true)->value('id'));
        $ayah = QuranAyah::query()->where('edition_id', $editionId)->where('id', $ayahId)->firstOrFail();

        return ['ayah' => QuranResource::make($ayah)];
    }
}
