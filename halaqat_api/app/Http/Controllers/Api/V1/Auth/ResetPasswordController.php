<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\Auth\MessageResponseResource;
use App\Services\Auth\PasswordService;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, PasswordService $service): JsonResponse
    {
        $service->reset(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return (new MessageResponseResource([
            'message' => 'Password reset successfully.',
        ]))->response()->setStatusCode(200);
    }
}
