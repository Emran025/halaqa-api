<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Memberships\StudentCollectionResource;
use App\Models\Halaqa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListHalaqaStudentsController extends Controller
{
    public function __invoke(Request $request, Halaqa $halaqa): StudentCollectionResource
    {
        Gate::authorize('manageMembers', $halaqa);

        $query = $halaqa->activeMemberships()->with('student')->when($request->filled('search'), function ($q) use ($request): void {
            $search = $request->string('search')->toString();
            $q->whereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
        })->latest('joined_at');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $students = $query->paginate($perPage)->through(fn ($membership) => $membership->student)->withQueryString();

        return new StudentCollectionResource($students);
    }
}
