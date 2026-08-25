<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use App\Services\Registrations\RegistrationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CancelRegistrationRequestController extends Controller
{
    public function __invoke(RegistrationRequest $registrationRequest, RegistrationService $service): Response
    {
        Gate::authorize('cancel', $registrationRequest);
        $service->cancel($registrationRequest);

        return response()->noContent();
    }
}
