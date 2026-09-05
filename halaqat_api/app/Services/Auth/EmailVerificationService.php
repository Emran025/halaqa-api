<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function send(User $user): void
    {
        if ($user->email_verified_at !== null) {
            return;
        }

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addDay(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $user->notify(new VerifyEmailNotification($url));
    }

    public function verify(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->email), $hash)) {
            throw ValidationException::withMessages(['email' => ['رابط تفعيل البريد غير صالح.']]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now(), 'status' => 'active'])->save();
        }
    }
}
