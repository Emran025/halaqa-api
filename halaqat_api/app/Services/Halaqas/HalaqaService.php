<?php

namespace App\Services\Halaqas;

use App\Exceptions\ApiConflictException;
use App\Models\Halaqa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HalaqaService
{
    public function create(User $teacher, array $data): Halaqa
    {
        return DB::transaction(function () use ($teacher, $data): Halaqa {
            $profile = $teacher->teacherProfile;
            if ($profile?->max_halaqas > 0 && $teacher->halaqas()->where('status', 'active')->count() >= $profile->max_halaqas) {
                throw new ApiConflictException('The teacher has reached the maximum number of active halaqas.', 'teacher_halaqa_capacity', 'user', $teacher->id);
            }

            return Halaqa::create([
                'id' => (string) Str::uuid(),
                'teacher_id' => $teacher->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'gender' => $data['gender'],
                'country' => $data['country'],
                'residence' => $data['residence'],
                'status' => $data['status'] ?? 'active',
                'max_students' => $data['max_students'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
            ])->load(['teacher.teacherProfile']);
        });
    }

    public function update(Halaqa $halaqa, array $data): Halaqa
    {
        return DB::transaction(function () use ($halaqa, $data): Halaqa {
            $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($halaqa->id);
            if (array_key_exists('gender', $data) && $data['gender'] !== $halaqa->gender && $halaqa->activeMemberships()->exists()) {
                throw new ApiConflictException('A halaqa with active students cannot change gender.', 'active_membership_gender_conflict', 'halaqa', $halaqa->id);
            }

            $halaqa->fill($data);
            $halaqa->save();

            return $halaqa->load(['teacher.teacherProfile'])->loadCount('activeMemberships');
        });
    }

    public function setStatus(Halaqa $halaqa, string $status): Halaqa
    {
        return DB::transaction(function () use ($halaqa, $status): Halaqa {
            $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($halaqa->id);
            if ($halaqa->status === $status) {
                throw new ApiConflictException('The halaqa already has the requested status.', 'halaqa_state_unchanged', 'halaqa', $halaqa->id);
            }

            $halaqa->update(['status' => $status]);

            return $halaqa->load(['teacher.teacherProfile'])->loadCount('activeMemberships');
        });
    }
}
