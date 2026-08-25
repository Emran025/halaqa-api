<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Memberships\ListHalaqaMembershipsRequest;
use App\Http\Resources\Api\V1\Memberships\MembershipCollectionResource;
use App\Models\Halaqa;
use App\Services\Memberships\MembershipQueryService;
use Illuminate\Support\Facades\Gate;

class ListHalaqaMembershipsController extends Controller
{
    public function __invoke(ListHalaqaMembershipsRequest $request, Halaqa $halaqa, MembershipQueryService $service): MembershipCollectionResource
    {
        Gate::authorize('manageMembers', $halaqa);

        return new MembershipCollectionResource($service->paginate($halaqa, $request->validated()));
    }
}
