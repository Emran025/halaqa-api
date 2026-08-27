<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registrations\ListTeacherApplicationInboxRequest;
use App\Http\Resources\Api\V1\Registrations\ApplicantCollectionResource;
use App\Services\Registrations\RegistrationQueryService;

class ListTeacherApplicationInboxController extends Controller
{
    public function __invoke(ListTeacherApplicationInboxRequest $request, RegistrationQueryService $service): ApplicantCollectionResource
    {
        return new ApplicantCollectionResource($service->teacherInbox($request->user(), $request->validated()));
    }
}
