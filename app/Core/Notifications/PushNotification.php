<?php

namespace App\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PushNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $title;
    private $body;
    private $data;
    private $sound;
    private $via;
    public function __construct($via, ...$params)
    {
        extract($params);
        $this->title = $title ?? NULL;
        $this->body = $body ?? NULL;
        $this->sound = 'customSound';
        $this->data = $data;
        $this->via = $via;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {

        return $this->via;
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toDatabase($notifiable)
    {

        return [
            'title' => $this->title,
            'body' => $this->body,
            'sound' => $this->sound,
            ...$this->data,
        ];
    }
    public function toFirebase($notifiable)
    {        
        return [
            'title' => $this->title,
            'body' => $this->body,
            'sound' => $this->sound,
            'data' => $this->data,
        ];
    }
}
