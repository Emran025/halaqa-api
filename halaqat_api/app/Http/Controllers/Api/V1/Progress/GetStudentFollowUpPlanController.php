<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Progress\FollowUpPlanResponseResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GetStudentFollowUpPlanController extends Controller
{
    public function __invoke(User $student): FollowUpPlanResponseResource|JsonResponse
    {
        Gate::authorize('view', $student);
        $plan = $student->studentProfile?->followUpPlan;
        if ($plan === null) {
            return response()->json(['follow_up_plan' => null]);
        }
        $plan->load(['student.studentProfile.availability', 'details.trackingType', 'details.trackingUnit']);

        return new FollowUpPlanResponseResource($plan);
    }
}
