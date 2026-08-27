<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registrations\ListHalaqaRegistrationRequestsRequest;
use App\Http\Resources\Api\V1\Registrations\RegistrationCollectionResource;
use App\Models\Halaqa;
use App\Services\Registrations\RegistrationQueryService;

class ListHalaqaRegistrationRequestsController extends Controller
{
    public function __invoke(ListHalaqaRegistrationRequestsRequest $request, Halaqa $halaqaId, RegistrationQueryService $service): RegistrationCollectionResource
    {
        abort_unless($halaqaId->teacher_id === $request->user()->id, 403);

        return new RegistrationCollectionResource($service->halaqaInbox($halaqaId, $request->validated()));
    }
}
