<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterTeacherRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterTeacherController extends Controller
{
    public function __invoke(RegisterTeacherRequest $request, AuthService $service): JsonResponse
    {
        return (new AuthResponseResource($service->registerTeacher($request->validated())))
            ->response()
            ->setStatusCode(201);
    }
}
