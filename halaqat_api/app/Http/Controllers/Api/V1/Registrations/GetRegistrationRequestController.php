<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Registrations\RegistrationResponseResource;
use App\Models\RegistrationRequest;
use Illuminate\Support\Facades\Gate;

class GetRegistrationRequestController extends Controller
{
    public function __invoke(RegistrationRequest $registrationRequest): RegistrationResponseResource
    {
        Gate::authorize('view', $registrationRequest);
        $registrationRequest->load(['student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability']);

        return new RegistrationResponseResource($registrationRequest);
    }
}
