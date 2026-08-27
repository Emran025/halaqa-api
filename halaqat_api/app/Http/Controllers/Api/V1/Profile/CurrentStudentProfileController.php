<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateStudentProfileRequest;
use App\Http\Resources\Api\V1\Profile\StudentProfileResponseResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CurrentStudentProfileController extends Controller
{
    public function show(Request $request): StudentProfileResponseResource
    {
        $student = $request->user();
        abort_unless($student?->isStudent(), 403);
        Gate::authorize('view', $student);

        return new StudentProfileResponseResource($student->load([
            'studentProfile.availability.slots',
            'studentProfile.followUpPlan.details.trackingType',
            'studentProfile.followUpPlan.details.trackingUnit',
        ]));
    }

    public function update(UpdateStudentProfileRequest $request, ProfileService $service): StudentProfileResponseResource
    {
        $student = $request->user();
        Gate::authorize('update', $student);

        return new StudentProfileResponseResource($service->updateStudent($student, $request->validated()));
    }
}
