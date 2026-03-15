<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $email;
    public $password;
    public $restaurantName;

    /**
     * Create a new message instance.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return void
     */
    public function __construct($username, $email, $password, $restaurantName)
    {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->restaurantName = $restaurantName;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Account Password')
            ->view('emails.send_password');
    }
}
