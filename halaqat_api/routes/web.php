<?php

use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/email/verify/{user}/{hash}', function (User $user, string $hash, EmailVerificationService $service) {
    try {
        $service->verify($user, $hash);

        return response()->view('auth.email-verified', [
            'success' => true,
            'message' => 'تم تأكيد عنوان البريد الإلكتروني وربط الحساب به بنجاح.',
        ]);
    } catch (\Throwable) {
        return response()->view('auth.email-verified', [
            'success' => false,
            'message' => 'رابط التفعيل غير صالح أو انتهت صلاحيته.',
        ], 410);
    }
})->middleware('signed')->name('verification.verify');

Route::get('/password/reset/{token}', function (Request $request, string $token) {
    return view('auth.password-reset', [
        'token' => $token,
        'email' => $request->string('email')->toString(),
    ]);
})->name('password.reset.page');

Route::post('/password/reset', function (Request $request, PasswordService $service) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'token' => ['required', 'string'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);
    $service->reset($validated['email'], $validated['token'], $validated['password']);

    return view('auth.email-verified', [
        'success' => true,
        'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك الآن فتح التطبيق وتسجيل الدخول.',
    ]);
})->name('password.reset.submit');
