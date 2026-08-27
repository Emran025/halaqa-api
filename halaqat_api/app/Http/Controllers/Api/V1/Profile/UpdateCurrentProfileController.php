<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Auth\UserResponseResource;
use App\Services\Profile\ProfileService;

class UpdateCurrentProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, ProfileService $service): UserResponseResource
    {
        return new UserResponseResource($service->update($request->user(), $request->validated()));
    }
}
