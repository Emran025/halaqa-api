<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Memberships\UpdateMembershipRequest;
use App\Http\Resources\Api\V1\Memberships\MembershipResponseResource;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use App\Services\Memberships\MembershipService;
use Illuminate\Support\Facades\Gate;

class UpdateMembershipController extends Controller
{
    public function __invoke(UpdateMembershipRequest $request, Halaqa $halaqa, HalaqaMembership $membership, MembershipService $service): MembershipResponseResource
    {
        Gate::authorize('update', $membership);

        return new MembershipResponseResource($service->update($membership, $request->validated()));
    }
}
