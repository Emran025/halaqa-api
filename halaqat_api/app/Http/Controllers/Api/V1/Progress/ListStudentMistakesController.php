<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Progress\MistakeCollectionResource;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListStudentMistakesController extends Controller
{
    public function __invoke(Request $request, User $student): MistakeCollectionResource
    {
        Gate::authorize('view', $student);
        $query = Mistake::query()->whereHas('detail.tracking', fn ($q) => $q->where('student_id', $student->id))->with('mistakeType')->latest();
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return new MistakeCollectionResource($query->paginate($perPage)->withQueryString());
    }
}
