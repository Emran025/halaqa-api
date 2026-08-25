<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Services\Auth\PasswordService;
use Illuminate\Http\Response;

class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request, PasswordService $service): Response
    {
        $service->change(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('password')->toString(),
        );

        return response()->noContent();
    }
}
