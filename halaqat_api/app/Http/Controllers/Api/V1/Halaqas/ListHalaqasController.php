<?php

namespace App\Http\Controllers\Api\V1\Halaqas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Halaqas\HalaqaCollectionResource;
use App\Models\Halaqa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListHalaqasController extends Controller
{
    public function __invoke(Request $request): HalaqaCollectionResource
    {
        $user = $request->user();
        Gate::authorize('viewAny', Halaqa::class);

        $query = Halaqa::query()
            ->with(['teacher.teacherProfile'])
            ->withCount('activeMemberships')
            ->when($user->isTeacher(), fn ($q) => $q->where('teacher_id', $user->id))
            ->when($user->isStudent(), fn ($q) => $q->whereHas('activeMemberships', fn ($m) => $m->where('student_id', $user->id)))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($nested) use ($request): void {
                $search = $request->string('search')->toString();
                $nested->where('name', 'like', "%{$search}%")->orWhere('country', 'like', "%{$search}%");
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest('created_at');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return new HalaqaCollectionResource($query->paginate($perPage)->withQueryString());
    }
}
