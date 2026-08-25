<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registrations\CompletionRequest;
use App\Http\Requests\Api\V1\Registrations\DecisionNoteRequest;
use App\Http\Resources\Api\V1\Registrations\RegistrationResponseResource;
use App\Models\RegistrationRequest;
use App\Services\Registrations\RegistrationService;
use Illuminate\Support\Facades\Gate;

class RegistrationDecisionController extends Controller
{
    public function accept(RegistrationRequest $registrationRequest, RegistrationService $service): RegistrationResponseResource
    {
        Gate::authorize('decide', $registrationRequest);

        return new RegistrationResponseResource($service->accept($registrationRequest, request()->user()));
    }

    public function reject(DecisionNoteRequest $request, RegistrationRequest $registrationRequest, RegistrationService $service): RegistrationResponseResource
    {
        Gate::authorize('decide', $registrationRequest);

        return new RegistrationResponseResource($service->reject($registrationRequest, $request->user(), $request->validated('note')));
    }

    public function requestCompletion(CompletionRequest $request, RegistrationRequest $registrationRequest, RegistrationService $service): RegistrationResponseResource
    {
        Gate::authorize('decide', $registrationRequest);

        return new RegistrationResponseResource($service->requestCompletion($registrationRequest, $request->user(), $request->validated()));
    }
}
