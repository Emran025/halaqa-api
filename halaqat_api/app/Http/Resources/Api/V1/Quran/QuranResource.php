<?php

namespace App\Http\Resources\Api\V1\Quran;

use App\Models\QuranAyah;
use App\Models\QuranSurah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof QuranSurah) {
            return ['id' => (int) $this->id, 'edition_id' => (int) $this->edition_id, 'name_ar' => $this->name_ar, 'name_en' => $this->name_en, 'name_en_translation' => $this->name_en_translation, 'number_of_ayahs' => (int) $this->number_of_ayahs, 'first_page_starts_at' => (int) $this->first_page_starts_at, 'revelation_type' => $this->revelation_type];
        }
        if ($this->resource instanceof QuranAyah) {
            return ['id' => (int) $this->id, 'edition_id' => (int) $this->edition_id, 'surah_id' => (int) $this->surah_id, 'number_in_surah' => (int) $this->number_in_surah, 'text_uthmani' => $this->text_uthmani, 'text_emlaey' => $this->text_emlaey, 'page_number' => (int) $this->page_number, 'juz_number' => (int) $this->juz_number, 'has_sajda' => (bool) $this->has_sajda];
        }

        return ['id' => (int) $this->id, 'edition_id' => (int) $this->edition_id, 'page_number' => (int) $this->page_number, 'page_text' => $this->page_text, 'ayahs' => QuranResource::collection($this->whenLoaded('ayahs'))];
    }
}
