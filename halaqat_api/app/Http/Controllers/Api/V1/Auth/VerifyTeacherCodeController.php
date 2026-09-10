<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyTeacherCodeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json([
                'valid' => false,
                'message' => 'يرجى إدخال معرف المعلم للتحقق.',
            ], 422);
        }

        $profile = TeacherProfile::query()
            ->where('teacher_code', $code)
            ->with('user')
            ->first();

        if (! $profile || ! $profile->user || ! $profile->user->isActive() || ! $profile->user->isTeacher()) {
            return response()->json([
                'valid' => false,
                'message' => 'معرف المعلم غير صحيح أو غير مسجل في المنصة.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'teacher' => [
                'id' => (string) $profile->user->id,
                'name' => $profile->user->name,
                'teacher_code' => $profile->teacher_code,
                'gender' => $profile->user->gender,
            ],
            'message' => 'تم التحقق من معرف المعلم بنجاح.',
        ]);
    }
}
