<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::route('password.reset.page', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور في حلقة القرآن')
            ->greeting('مرحباً '.$notifiable->name)
            ->line('تلقينا طلباً لإعادة تعيين كلمة المرور لحسابك.')
            ->action('فتح صفحة إعادة التعيين', $url)
            ->line('يمكنك أيضاً نسخ رمز التحقق الموجود أدناه إلى تطبيق حلقة القرآن:')
            ->line($this->token)
            ->line('ينتهي هذا الطلب حسب إعدادات أمان الخادم. إذا لم تطلبه، تجاهل الرسالة بأمان.')
            ->salutation('مع تحيات فريق حلقة القرآن');
    }
}
