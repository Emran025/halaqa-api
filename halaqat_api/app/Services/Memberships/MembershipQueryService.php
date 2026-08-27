<?php

namespace App\Services\Memberships;

use App\Models\Halaqa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MembershipQueryService
{
    /** @param array{status?:string,search?:string,page?:int,per_page?:int} $filters */
    public function paginate(Halaqa $halaqa, array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);
        $query = $halaqa->memberships()->with('student')
            ->when(isset($filters['status']), fn ($builder) => $builder->where('status', $filters['status']))
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($builder) use ($filters): void {
                $search = $filters['search'];
                $builder->whereHas('student', fn ($student) => $student->where(function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', "%{$search}%");
                }));
            })
            ->orderByDesc('joined_at')
            ->orderByDesc('id');

        return $query->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1))->withQueryString();
    }
}
