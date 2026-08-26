<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Halaqas\TeacherPublicResponseResource;
use App\Models\User;
use App\Services\Profile\TeacherQueryService;
use Illuminate\Http\Request;

class GetPublicTeacherController extends Controller
{
    public function __invoke(Request $request, User $teacherId, TeacherQueryService $service): TeacherPublicResponseResource
    {
        abort_unless($teacherId->isTeacher(), 404);

        return new TeacherPublicResponseResource($service->publicProfile($teacherId));
    }
}
