<?php

namespace App\Services\Sessions;

use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SessionQueryService
{
    public function listFor(User $viewer, array $filters): LengthAwarePaginator
    {
        $query = LiveSession::query()
            ->with(['teacher', 'student', 'taskType'])
            ->when($viewer->isTeacher(), fn ($query) => $query->where('teacher_id', $viewer->id))
            ->when($viewer->isStudent(), fn ($query) => $query->where('student_id', $viewer->id))
            ->when(isset($filters['halaqa_id']), fn ($query) => $query->where('halaqa_id', $filters['halaqa_id']))
            ->when(isset($filters['student_id']), fn ($query) => $query->where('student_id', $filters['student_id']))
            ->when(isset($filters['state']), fn ($query) => $query->where('state', $filters['state']))
            ->when(isset($filters['from']), fn ($query) => $query->where('scheduled_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->where('scheduled_at', '<=', $filters['to']))
            ->latest('scheduled_at')
            ->latest('requested_at');

        return $query->paginate((int) ($filters['per_page'] ?? 20), ['*'], 'page', (int) ($filters['page'] ?? 1))->withQueryString();
    }
}
