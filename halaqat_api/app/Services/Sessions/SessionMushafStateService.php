<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\QuranAyah;
use App\Models\QuranEdition;
use App\Models\QuranPage;
use App\Models\QuranSurah;
use App\Models\SessionMushafState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SessionMushafStateService
{
    public function get(LiveSession $session): SessionMushafState
    {
        return SessionMushafState::query()->whereKey($session->id)->with(['rangeToAyah'])->firstOrFail();
    }

    public function save(User $actor, LiveSession $session, array $data): SessionMushafState
    {
        return DB::transaction(function () use ($actor, $session, $data): SessionMushafState {
            $this->assertEditionAndCoordinates($data);
            $existing = SessionMushafState::query()->whereKey($session->id)->lockForUpdate()->first();
            if ($existing !== null && (string) $existing->last_client_operation_id === (string) $data['client_operation_id']) {
                if ((string) $existing->updated_by_user_id !== (string) $actor->id) {
                    throw new ApiConflictException('The client operation already belongs to another user.', 'idempotency_key_reused', 'client_operation_id', $data['client_operation_id']);
                }

                return $existing->load(['rangeToAyah']);
            }

            $payload = ['edition_id' => $data['edition_id'], 'page_number' => $data['page_number'], 'surah_id' => $data['surah_id'] ?? null, 'ayah_id' => $data['ayah_id'] ?? null, 'range_from_page' => $data['range']['start_page'] ?? null, 'range_from_ayah_id' => $data['range']['start_ayah_id'] ?? null, 'range_to_page' => $data['range']['end_page'] ?? null, 'range_to_ayah_id' => $data['range']['end_ayah_id'] ?? null, 'updated_by_user_id' => $actor->id, 'last_client_operation_id' => $data['client_operation_id']];
            if ($existing === null) {
                $payload['session_id'] = $session->id;
                $payload['version'] = 1;
                $state = SessionMushafState::create($payload);
            } else {
                $existing->update($payload + ['version' => $existing->version + 1]);
                $state = $existing->fresh();
            }

            return $state->load(['rangeToAyah']);
        });
    }

    private function assertEditionAndCoordinates(array $data): void
    {
        $editionId = (int) $data['edition_id'];
        if (! QuranEdition::query()->whereKey($editionId)->exists()) {
            throw new ApiConflictException('The Quran edition is not available.', 'quran_edition_not_found', 'edition_id', (string) $editionId);
        }
        if (! QuranPage::query()->where('edition_id', $editionId)->where('page_number', $data['page_number'])->exists()) {
            throw new ApiConflictException('The page does not belong to the selected Quran edition.', 'quran_page_mismatch', 'page_number', (string) $data['page_number']);
        }
        if (isset($data['surah_id']) && $data['surah_id'] !== null && ! QuranSurah::query()->where('edition_id', $editionId)->whereKey($data['surah_id'])->exists()) {
            throw new ApiConflictException('The surah does not belong to the selected Quran edition.', 'quran_surah_mismatch', 'surah_id', (string) $data['surah_id']);
        }
        if (isset($data['ayah_id']) && $data['ayah_id'] !== null) {
            $ayah = QuranAyah::query()->where('edition_id', $editionId)->whereKey($data['ayah_id'])->first();
            if ($ayah === null || (int) $ayah->page_number !== (int) $data['page_number']) {
                throw new ApiConflictException('The ayah does not belong to the selected page and edition.', 'quran_ayah_mismatch', 'ayah_id', (string) $data['ayah_id']);
            }
        }
        if (isset($data['range']) && $data['range'] !== null) {
            $range = $data['range'];
            if ((int) $range['edition_id'] !== $editionId) {
                throw new ApiConflictException('The Quran range edition must match the state edition.', 'quran_range_edition_mismatch', 'range.edition_id', (string) $range['edition_id']);
            }
            if ($range['start_page'] !== null && $range['end_page'] !== null && (int) $range['start_page'] > (int) $range['end_page']) {
                throw new ApiConflictException('The Quran range start page cannot exceed its end page.', 'quran_range_invalid', 'range', $range['start_page'].'-'.$range['end_page']);
            }
            $this->assertRangeAyah($range['start_ayah_id'] ?? null, $editionId, $range['start_page'] ?? null, 'range.start_ayah_id');
            $endAyah = $this->assertRangeAyah($range['end_ayah_id'] ?? null, $editionId, $range['end_page'] ?? null, 'range.end_ayah_id');
            if (($range['end_ayah_number'] ?? null) !== null && ($endAyah === null || (int) $endAyah->number_in_surah !== (int) $range['end_ayah_number'])) {
                throw new ApiConflictException('The end ayah number does not match the selected Quran ayah.', 'quran_range_ayah_mismatch', 'range.end_ayah_number', (string) $range['end_ayah_number']);
            }
        }
    }

    private function assertRangeAyah(?int $ayahId, int $editionId, ?int $pageNumber, string $field): ?QuranAyah
    {
        if ($ayahId === null) {
            return null;
        }
        $ayah = QuranAyah::query()->where('edition_id', $editionId)->whereKey($ayahId)->first();
        if ($ayah === null || ($pageNumber !== null && (int) $ayah->page_number !== (int) $pageNumber)) {
            throw new ApiConflictException('The range ayah does not belong to the selected page and edition.', 'quran_range_ayah_mismatch', $field, (string) $ayahId);
        }

        return $ayah;
    }
}
