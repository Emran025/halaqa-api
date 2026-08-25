<?php

namespace App\Http\Controllers\Api\V1\Halaqas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Halaqas\UpdateHalaqaRequest;
use App\Http\Resources\Api\V1\Halaqas\HalaqaResponseResource;
use App\Models\Halaqa;
use App\Services\Halaqas\HalaqaService;
use Illuminate\Support\Facades\Gate;

class UpdateHalaqaController extends Controller
{
    public function __invoke(UpdateHalaqaRequest $request, Halaqa $halaqa, HalaqaService $service): HalaqaResponseResource
    {
        Gate::authorize('update', $halaqa);

        return new HalaqaResponseResource($service->update($halaqa, $request->validated()));
    }
}
