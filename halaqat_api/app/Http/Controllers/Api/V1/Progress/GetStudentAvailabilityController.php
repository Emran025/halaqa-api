<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Progress\AttendancePreferencesResponseResource;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class GetStudentAvailabilityController extends Controller
{
    public function __invoke(User $student): AttendancePreferencesResponseResource
    {
        Gate::authorize('view', $student);
        $student->load('studentProfile.availability.slots');

        return new AttendancePreferencesResponseResource($student->studentProfile->availability);
    }
}
