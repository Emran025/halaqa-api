<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResendVerificationRequest;
use App\Http\Resources\Api\V1\Auth\MessageResponseResource;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;

class ResendVerificationController extends Controller
{
    public function __invoke(ResendVerificationRequest $request, EmailVerificationService $service): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();
        if ($user !== null && $user->email_verified_at === null) {
            $service->send($user);
        }

        return (new MessageResponseResource([
            'message' => 'إذا كان الحساب موجوداً وغير مفعّل، أُرسلت رسالة تفعيل جديدة.',
        ]))->response()->setStatusCode(202);
    }
}
