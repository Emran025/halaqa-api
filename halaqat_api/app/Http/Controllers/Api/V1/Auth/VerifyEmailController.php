<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(User $user, string $hash, EmailVerificationService $service): JsonResponse
    {
        $service->verify($user, $hash);

        return response()->json([
            'message' => 'تم تفعيل البريد الإلكتروني بنجاح. يمكنك الآن تسجيل الدخول.',
            'verified' => true,
        ]);
    }
}
