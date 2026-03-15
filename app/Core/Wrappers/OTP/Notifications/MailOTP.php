<?php

namespace App\Core\Wrappers\OTP\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Core\Channels\ValueFirstChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MailOTP extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $email_code;
    protected $phone_code;
    protected $type;
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail($notifiable)
    // {
    //     $email = $notifiable?->routes['mail'];
    //     $emailHash = hash('sha256', strtolower(trim($email)));
    //     $user = User::where('email_hash', $emailHash)->firstOrFail();
    //     $role = $user->role;

    //     // Extract OTP from message text (e.g. "Your OTP is 123456")
    //     $otp = null;
    //     if (!empty($this->payload['message'])) {
    //         if (preg_match('/\d{4,}/', $this->payload['message'], $matches)) {
    //             $otp = $matches[0];
    //         }
    //     }
    //     if ($role === 'user') {
    //         return (new MailMessage)
    //             ->subject('Your Password Reset Code')
    //             ->greeting('Hello ' . ($user->first_name ?? 'User') . ',')
    //             ->line('We received a request to reset your password.')
    //             ->line('👉 Your OTP is: ' . ($otp ?? ''))
    //             ->line('This code is valid for 10 minutes.')
    //             ->line('If you did not request this, please ignore this email.')
    //             ->salutation('Regards, ' . config('app.name'));
    //     }
    //     $messageArray = explode(PHP_EOL, $this->payload['message']);

    //     $mailInstance = (new MailMessage)
    //         ->subject($this->payload['title']);
    //     foreach ($messageArray as $line) {
    //         $mailInstance->line($line);
    //     }

    //     return $mailInstance;
    // }

    public function toMail($notifiable)
    {
        $email = $notifiable?->routes['mail'];
        $emailHash = hash('sha256', strtolower(trim($email)));
        $user = User::where('email_hash', $emailHash)->firstOrFail();
        $role = $user->role;

        // Extract OTP from message text
        $otp = null;
        if (!empty($this->payload['message'])) {
            if (preg_match('/\d{4,}/', $this->payload['message'], $matches)) {
                $otp = $matches[0];
            }
        }

        if ($role === 'user') {
            // Use the custom HTML view for users
            return (new MailMessage)
                ->subject('Your Password Reset Code')
                ->view('emails.password_reset', [
                    'firstName' => $user->first_name ?? 'User',
                    'otp' => $otp ?? '',
                    'appName' => config('app.name')
                ]);
        }

        $messageArray = explode(PHP_EOL, $this->payload['message']);
        $mailInstance = (new MailMessage)
            ->subject($this->payload['title']);
        foreach ($messageArray as $line) {
            $mailInstance->line($line);
        }

        return $mailInstance;
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toValueFirst($notifiable): string
    {
        return "Dear Customer, Your OTP is $this->phone_code please use this to complete phone verification. Please do not share your OTP with others for own financial security.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
