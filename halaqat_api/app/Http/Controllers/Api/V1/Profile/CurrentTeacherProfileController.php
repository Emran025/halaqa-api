<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateTeacherProfileRequest;
use App\Http\Resources\Api\V1\Profile\TeacherProfileResponseResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\Request;

class CurrentTeacherProfileController extends Controller
{
    public function show(Request $request): TeacherProfileResponseResource
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher(), 403);

        $teacher->load([
            'teacherProfile.documents',
            'halaqas' => fn ($query) => $query->where('status', 'active')->withCount('activeMemberships'),
        ])->loadCount(['halaqas as active_halaqas_count' => fn ($query) => $query->where('status', 'active')]);

        return new TeacherProfileResponseResource($teacher);
    }

    public function update(UpdateTeacherProfileRequest $request, ProfileService $service): TeacherProfileResponseResource
    {
        return new TeacherProfileResponseResource($service->updateTeacher($request->user(), $request->validated()));
    }
}
