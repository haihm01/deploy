<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token;
    protected $email; // Thêm thuộc tính để lưu email

    /**
     * Create a new notification instance.
     */
    public function __construct($token,$email)
    {
        $this->token = $token;
        $this->email = $email; // Lưu email vào thuộc tính

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $encodedEmail = base64_encode($this->email);
        $url = 'http://localhost:5173/reset-password/' . $this->token . '/' . $encodedEmail;

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu')
            ->markdown('emails.reset-password', [
                'url' => $url,
                'email' => $this->email,
            ])
            ->attach(public_path('img/logo-web-header.png'), [
                'as' => 'logo-web-header.png',
                'mime' => 'image/png',
            ]);
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
