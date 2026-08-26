<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registrations\CreateRegistrationRequest;
use App\Http\Resources\Api\V1\Registrations\RegistrationResponseResource;
use App\Models\Halaqa;
use App\Services\Registrations\RegistrationService;
use Illuminate\Http\JsonResponse;

class CreateHalaqaRegistrationRequestController extends Controller
{
    public function __invoke(CreateRegistrationRequest $request, Halaqa $halaqaId, RegistrationService $service): JsonResponse
    {
        $data = $request->validated();
        $data['requested_halaqa_id'] = $halaqaId->id;
        $registration = $service->submit($request->user(), $data);

        return RegistrationResponseResource::make($registration)->response()->setStatusCode(201);
    }
}
