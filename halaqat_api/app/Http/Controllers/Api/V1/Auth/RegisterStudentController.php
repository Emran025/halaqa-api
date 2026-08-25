<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterStudentRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterStudentController extends Controller
{
    public function __invoke(RegisterStudentRequest $request, AuthService $service): JsonResponse
    {
        return (new AuthResponseResource($service->registerStudent($request->validated())))
            ->response()
            ->setStatusCode(201);
    }
}
