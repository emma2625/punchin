<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected string|null $password;

    public function __construct($password = null)
    {
        $this->password = $password;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (!$this->password) {
            return (new MailMessage)
                ->subject('Your New Staff Account')
                ->greeting('Hello ' . $notifiable->first_name . ',')
                ->line('Your staff account has been created.')
                ->line('You can login with the to view the account:');
        }

        return (new MailMessage)
            ->subject('Your New Staff Account')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Your staff account has been created.')
            ->line('You can login with the following credentials:')
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Password:** ' . $this->password)
            ->line('For security, please change your password after first login.');
    }
}
