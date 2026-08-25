<?php

namespace App\Http\Controllers\Api\V1\Halaqas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Halaqas\HalaqaResponseResource;
use App\Models\Halaqa;
use Illuminate\Support\Facades\Gate;

class GetHalaqaController extends Controller
{
    public function __invoke(Halaqa $halaqa): HalaqaResponseResource
    {
        Gate::authorize('view', $halaqa);
        $halaqa->load(['teacher.teacherProfile'])->loadCount('activeMemberships');

        return new HalaqaResponseResource($halaqa);
    }
}
