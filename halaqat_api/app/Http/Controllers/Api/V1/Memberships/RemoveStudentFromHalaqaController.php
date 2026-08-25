<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use App\Services\Memberships\MembershipService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RemoveStudentFromHalaqaController extends Controller
{
    public function __invoke(Halaqa $halaqa, HalaqaMembership $membership, MembershipService $service): Response
    {
        Gate::authorize('delete', $membership);
        $service->update($membership, ['status' => 'removed']);

        return response()->noContent();
    }
}
