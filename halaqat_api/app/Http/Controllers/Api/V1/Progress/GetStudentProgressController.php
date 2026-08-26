<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\GetStudentProgressRequest;
use App\Http\Resources\Api\V1\Progress\ProgressResponseResource;
use App\Models\User;
use App\Services\Progress\ProgressQueryService;
use Illuminate\Support\Facades\Gate;

class GetStudentProgressController extends Controller
{
    public function __invoke(GetStudentProgressRequest $request, User $studentId, ProgressQueryService $service): ProgressResponseResource
    {
        abort_unless($studentId->isStudent(), 404);
        Gate::authorize('view', $studentId);

        return new ProgressResponseResource($service->forStudent($studentId, $request->validated('task_type')));
    }
}
