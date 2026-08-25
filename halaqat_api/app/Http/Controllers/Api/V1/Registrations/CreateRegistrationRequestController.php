<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registrations\CreateRegistrationRequest;
use App\Http\Resources\Api\V1\Registrations\RegistrationResponseResource;
use App\Services\Registrations\RegistrationService;
use Illuminate\Http\JsonResponse;

class CreateRegistrationRequestController extends Controller
{
    public function __invoke(CreateRegistrationRequest $request, RegistrationService $service): JsonResponse
    {
        $registration = $service->submit($request->user(), $request->validated());

        return RegistrationResponseResource::make($registration)->response()->setStatusCode(201);
    }
}
