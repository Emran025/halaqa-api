<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\ListTrackingsRequest;
use App\Http\Resources\Api\V1\Progress\TrackingCollectionResource;
use App\Models\DailyTracking;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ListStudentTrackingsController extends Controller
{
    public function __invoke(ListTrackingsRequest $request, User $student): TrackingCollectionResource
    {
        Gate::authorize('view', $student);
        $query = DailyTracking::query()->where('student_id', $student->id)->with('membership')->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->validated('from')))->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->validated('to')))->latest('date');

        return new TrackingCollectionResource($query->paginate(min(max((int) $request->input('per_page', 20), 1), 100))->withQueryString());
    }
}
