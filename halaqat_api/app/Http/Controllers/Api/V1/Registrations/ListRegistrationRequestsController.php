<?php

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Registrations\RegistrationCollectionResource;
use App\Models\RegistrationRequest;
use Illuminate\Http\Request;

class ListRegistrationRequestsController extends Controller
{
    public function __invoke(Request $request): RegistrationCollectionResource
    {
        $user = $request->user();
        $query = RegistrationRequest::query()->with(['student', 'student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } else {
            $query->where(function ($q) use ($user): void {
                $q->where('teacher_id', $user->id)
                    ->orWhere(function ($nested) use ($user): void {
                        $nested->where('routing_mode', 'all_available_teachers')->whereIn('state', ['pending', 'completion_requested'])
                            ->whereHas('profile', fn ($profile) => $profile->where('gender', $user->gender)->where('country', $user->country));
                    })
                    ->orWhereHas('requestedHalaqa', fn ($halaqa) => $halaqa->where('teacher_id', $user->id));
            });
        }

        $query->when($request->filled('state'), fn ($q) => $q->where('state', $request->string('state')->toString()))
            ->when($request->filled('halaqa_id'), fn ($q) => $q->where('requested_halaqa_id', $request->string('halaqa_id')->toString()))
            ->latest('submitted_at');
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return new RegistrationCollectionResource($query->paginate($perPage)->withQueryString());
    }
}
