<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Memberships\AssignStudentRequest;
use App\Http\Resources\Api\V1\Memberships\MembershipResponseResource;
use App\Models\Halaqa;
use App\Models\User;
use App\Services\Memberships\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AssignStudentToHalaqaController extends Controller
{
    public function __invoke(AssignStudentRequest $request, Halaqa $halaqa, MembershipService $service): JsonResponse
    {
        Gate::authorize('manageMembers', $halaqa);
        $student = User::query()->findOrFail($request->validated('student_id'));
        $membership = $service->assign($halaqa, $student);

        return MembershipResponseResource::make($membership)->response()->setStatusCode(201);
    }
}
