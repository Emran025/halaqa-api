<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(AuthService $service): Response
    {
        $service->logout(request()->user(), request()->bearerToken());

        return response()->noContent();
    }
}
