<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordService
{
    public function requestReset(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function reset(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function (CanResetPassword $user) use ($password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['The password reset token is invalid or expired.'],
            ]);
        }
    }

    public function change(User $user, string $currentPassword, string $password): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new AuthenticationException('The current password is invalid.');
        }

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
        });
    }
}
