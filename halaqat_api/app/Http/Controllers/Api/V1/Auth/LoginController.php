<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, AuthService $service): JsonResource
    {
        return new AuthResponseResource($service->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        ));
    }
}
