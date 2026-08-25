<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\UpdateFollowUpPlanRequest;
use App\Http\Resources\Api\V1\Progress\FollowUpPlanResponseResource;
use App\Models\User;
use App\Services\Progress\FollowUpPlanService;
use Illuminate\Support\Facades\Gate;

class UpdateStudentFollowUpPlanController extends Controller
{
    public function __invoke(UpdateFollowUpPlanRequest $request, User $student, FollowUpPlanService $service): FollowUpPlanResponseResource
    {
        Gate::authorize('update', $student);

        return new FollowUpPlanResponseResource($service->update($student, $request->user(), $request->validated()));
    }
}
