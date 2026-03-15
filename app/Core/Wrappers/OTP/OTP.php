<?php

namespace App\Core\Wrappers\OTP;

use App\Core\Wrappers\OTP\Contracts\OTPContract;
use App\Core\Wrappers\OTP\Notifications\MailOTP;
use App\Core\Wrappers\OTP\Notifications\SMSOTP;
use App\Core\Wrappers\OTP\Traits\Queryable;
use App\Core\Wrappers\OTP\Traits\Sendable;
use App\Core\Wrappers\OTP\Traits\Verifiable;

class OTP extends OTPContract {

	use Sendable, Verifiable ,Queryable;

    protected $defaultNotificationClasses = [
        'mail' => MailOTP::class,
        'sms' => SMSOTP::class,
    ];

    private $config = [];
    protected $type;
    protected $to;
    public $code;
    protected $user;
    protected $message;
    protected $title;
    

} 


?>