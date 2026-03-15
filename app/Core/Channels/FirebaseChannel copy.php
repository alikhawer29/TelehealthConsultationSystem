<?php

namespace App\Core\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Contract\Messaging;

class FirebaseChannel
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function send($notifiable, Notification $notification)
    {
        $payload = $notification->toFirebase($notifiable);

        // Retrieve device tokens, excluding null values
        $tokens = $notifiable->tokens()
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->all();

        if (empty($tokens)) {
            return false;
        }

        // Base notification
        $baseNotification = FirebaseNotification::create(
            $payload['title'] ?? '',
            $payload['body'] ?? ''
        );

        // Build message with platform-specific configs for sound
        $message = CloudMessage::new()
            ->withNotification($baseNotification)
            ->withAndroidConfig(AndroidConfig::fromArray([
                'notification' => [
                    'sound' => 'customSound', // file must be in res/raw/customSound.mp3
                ],
            ]))
            ->withApnsConfig(ApnsConfig::fromArray([
                'payload' => [
                    'aps' => [
                        'sound' => 'customSound.caf', // file must exist in iOS app bundle
                    ],
                ],
            ]))
            ->withData($payload['data'] ?? []);

        try {
            return $this->messaging->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            \Log::error('Firebase Notification Error', [
                'error' => $e->getMessage(),
                'tokens' => $tokens,
            ]);
            throw $e;
        }
    }
}
