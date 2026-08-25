<?php

namespace App\Http\Controllers\Api\V1\Halaqas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Halaqas\StoreHalaqaRequest;
use App\Http\Resources\Api\V1\Halaqas\HalaqaResponseResource;
use App\Models\Halaqa;
use App\Services\Halaqas\HalaqaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CreateHalaqaController extends Controller
{
    public function __invoke(StoreHalaqaRequest $request, HalaqaService $service): JsonResponse
    {
        Gate::authorize('create', Halaqa::class);
        $halaqa = $service->create($request->user(), $request->validated());

        return HalaqaResponseResource::make($halaqa)->response()->setStatusCode(201);
    }
}
