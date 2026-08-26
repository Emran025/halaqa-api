<?php

namespace App\Services\Registrations;

use App\Models\Halaqa;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RegistrationQueryService
{
    public function halaqaInbox(Halaqa $halaqa, array $filters): LengthAwarePaginator
    {
        $query = RegistrationRequest::query()
            ->with(['student', 'student.studentProfile', 'teacher.teacherProfile', 'requestedHalaqa', 'profile', 'availability.slots'])
            ->where('requested_halaqa_id', $halaqa->id)
            ->when(isset($filters['state']), fn ($query) => $query->where('state', $filters['state']))
            ->latest('submitted_at');

        return $query->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1))->withQueryString();
    }

    public function teacherInbox(User $teacher, array $filters): LengthAwarePaginator
    {
        $state = $filters['state'] ?? 'pending';
        $query = RegistrationRequest::query()
            ->with(['student', 'student.studentProfile', 'teacher.teacherProfile', 'requestedHalaqa', 'profile', 'availability.slots'])
            ->where('state', $state)
            ->where(function ($query) use ($teacher): void {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere(function ($nested) use ($teacher): void {
                        $nested->where('routing_mode', 'all_available_teachers')
                            ->where('state', 'pending')
                            ->whereHas('student', fn ($student) => $student
                                ->where('gender', $teacher->gender)
                                ->where('country', $teacher->country));
                    });
            })
            ->when(! empty($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->whereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
            })
            ->latest('submitted_at');

        return $query->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1))->withQueryString();
    }
}
