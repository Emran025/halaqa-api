<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $verificationUrl)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('فعّل حسابك في حلقة القرآن')
            ->greeting('مرحباً '.$notifiable->name)
            ->line('أهلاً بك في منصة حلقة القرآن. أكمل تفعيل بريدك الإلكتروني للوصول إلى حسابك بأمان.')
            ->action('تفعيل البريد الإلكتروني', $this->verificationUrl)
            ->line('ينتهي هذا الرابط خلال 24 ساعة. إذا لم تنشئ هذا الحساب، يمكنك تجاهل الرسالة.')
            ->salutation('مع تحيات فريق حلقة القرآن');
    }
}
