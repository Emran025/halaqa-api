<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ListAvailableTeachersRequest;
use App\Http\Resources\Api\V1\Halaqas\TeacherPublicCollectionResource;
use App\Services\Profile\TeacherQueryService;

class ListAvailableTeachersController extends Controller
{
    public function __invoke(ListAvailableTeachersRequest $request, TeacherQueryService $service): TeacherPublicCollectionResource
    {
        return new TeacherPublicCollectionResource($service->listAvailable($request->validated()));
    }
}
