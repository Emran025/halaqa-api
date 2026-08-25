<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\UpdateAvailabilityRequest;
use App\Http\Resources\Api\V1\Progress\AttendancePreferencesResponseResource;
use App\Models\User;
use App\Services\Progress\UpdateStudentAvailabilityService;
use Illuminate\Support\Facades\Gate;

class UpdateStudentAvailabilityController extends Controller
{
    public function __invoke(UpdateAvailabilityRequest $request, User $student, UpdateStudentAvailabilityService $service): AttendancePreferencesResponseResource
    {
        Gate::authorize('update', $student);

        return new AttendancePreferencesResponseResource($service->update($student, $request->validated()));
    }
}
