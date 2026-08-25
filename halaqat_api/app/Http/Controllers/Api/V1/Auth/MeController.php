<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Auth\UserResponseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MeController extends Controller
{
    public function __invoke(): JsonResource
    {
        return new UserResponseResource(request()->user());
    }
}
