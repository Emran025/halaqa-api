<?php

namespace App\Http\Controllers\Api\V1\Halaqas;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Halaqas\HalaqaResponseResource;
use App\Models\Halaqa;
use App\Services\Halaqas\HalaqaService;
use Illuminate\Support\Facades\Gate;

class SetHalaqaStatusController extends Controller
{
    public function activate(Halaqa $halaqa, HalaqaService $service): HalaqaResponseResource
    {
        return $this->set($halaqa, $service, 'active');
    }

    public function deactivate(Halaqa $halaqa, HalaqaService $service): HalaqaResponseResource
    {
        return $this->set($halaqa, $service, 'inactive');
    }

    private function set(Halaqa $halaqa, HalaqaService $service, string $status): HalaqaResponseResource
    {
        Gate::authorize('update', $halaqa);

        return new HalaqaResponseResource($service->setStatus($halaqa, $status));
    }
}
