<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherQueryService
{
    public function listAvailable(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', 'teacher')
            ->whereHas('teacherProfile')
            ->with('teacherProfile')
            ->withCount(['halaqas as active_halaqas_count' => fn ($query) => $query->where('status', 'active')])
            ->when(array_key_exists('code', $filters) && $filters['code'] !== null, function ($query) use ($filters): void {
                $query->whereHas('teacherProfile', fn ($profile) => $profile->where('teacher_code', $filters['code']));
            })
            ->when(! empty($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhereHas('teacherProfile', fn ($profile) => $profile->where('qualification', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name');

        return $query->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1))->withQueryString();
    }

    public function publicProfile(User $teacher): User
    {
        return $teacher->load([
            'teacherProfile',
            'halaqas' => fn ($query) => $query->where('status', 'active')->withCount('activeMemberships'),
        ])->loadCount(['halaqas as active_halaqas_count' => fn ($query) => $query->where('status', 'active')]);
    }
}
