<?php

namespace App\Repositories\Password;

use App\Mail\UserIdMail;
use App\Core\Wrappers\OTP\OTP;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Password\PasswordRecoveryRepositoryContract;

class PasswordRecoveryRepository implements PasswordRecoveryRepositoryContract
{
    protected $model;

    public function setModel(Model $model)
    {

        $this->model = $model;
    }

    public function verifyEmail($email)
    {
        try {

            $otp = new OTP();
            $otp->to($email)->send();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function verifyCode($email, $code)
    {

        try {
            $otp = new OTP();
            $result = $otp->to($email)->code($code)->verify();

            if (!$result) throw new \Exception('invalid code verification failed.');

            return $result;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updatePassword($newPassword, $conditionalParams)
    {
        extract($conditionalParams);
        try {
            $verified = $this->verifyCode($email, $code);

            // Use email_hash instead of email for lookup
            $emailHash = hash('sha256', strtolower(trim($email)));
            $user = $this->model->where('email_hash', $emailHash)->firstOrFail();

            $user->password = $newPassword;
            $user->save();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public function verifyCodeUserId($email, $code)
    {

        try {
            $otp = new OTP();
            $result = $otp->to($email)->code($code)->verify();

            if (!$result) throw new \Exception('invalid code verification failed.');

            return $result;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function sendUserId($email)
    {
        try {
            $user = $this->model->where('email', $email)->firstOrFail();
            $userId = $user->user_id; // Assuming `user_id` is the field with the user’s ID
            $username = $user->user_name;

            Mail::to($email)->send(new UserIdMail($userId, $username)); // Use a mailable to send the user ID
        } catch (\Exception $e) {
            throw new \Exception('Failed to send User ID: ' . $e->getMessage());
        }
    }
}
