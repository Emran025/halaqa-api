<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Profile\StudentProfileResponseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GetStudentProfileController extends Controller
{
    public function __invoke(Request $request, User $studentId): StudentProfileResponseResource
    {
        abort_unless($studentId->isStudent(), 404);
        Gate::authorize('view', $studentId);

        return new StudentProfileResponseResource($studentId->load([
            'studentProfile.availability.slots',
            'studentProfile.followUpPlan.details.trackingType',
            'studentProfile.followUpPlan.details.trackingUnit',
        ]));
    }
}
