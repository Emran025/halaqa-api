<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Resources\Api\V1\Auth\MessageResponseResource;
use App\Services\Auth\PasswordService;
use Illuminate\Http\JsonResponse;

class RequestPasswordResetController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, PasswordService $service): JsonResponse
    {
        $service->requestReset($request->string('email')->toString());

        return (new MessageResponseResource([
            'message' => 'If the account exists, password reset instructions have been sent.',
        ]))->response()->setStatusCode(202);
    }
}
