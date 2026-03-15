<?php

namespace App\Core\Wrappers\OTP\Traits;

trait Sendable
{

    public function __construct($type = 'mail')
    {
        $this->config = config("otp.{$type}");
        $this->type = $type;
        $this->code();
        $this->message = $this->config['message'];
        $this->title = $this->config['subject'] ?? null;
    }

    function to($value)
    {
        $this->to = $value;
        return $this;
    }

    public function code($code = null, $prefix = null, $length = 4)
    {
        if (!$code) {

            $code = mt_rand(10000, 999999999999);
            $currentTime = time();
            $this->code = substr($prefix . str_shuffle($currentTime . $code), 0, $length);
            return $this;
        }
        $this->code = $code;
        return $this;
    }

    /*
    @params $user = Model | Array of models
    @return $this;
    */

    public function user($user)
    {
        $this->user = $user;
        return $this;
    }

    public function subject($title)
    {

        if ($this->type != 'mail') {
            throw new \Exception("subject can only be called with type mail");
        }

        $this->title = $title;
        return $this;
    }

    public function message($message)
    {
        $this->message = $message;
        return $this;
    }


    function send(String $userDefindedClass = null)
    {
        $notificationClass =  $this->getNotificationClass($userDefindedClass);

        $message = $this->prepare_message($this->message, [
            '[code]' => $this->code,
        ]);
        $title = $this->title;

        if (!$this->user) {

            \Notification::route($this->type, $this->to)
                ->notify(
                    resolve($notificationClass, ['payload' => compact('message', 'title')])
                );
        } else {
            \Notification::send(
                $this->user,
                resolve($notificationClass, ['payload' => compact('message', 'title')])
            );
        }

        $this->storeOTP();
    }
}
