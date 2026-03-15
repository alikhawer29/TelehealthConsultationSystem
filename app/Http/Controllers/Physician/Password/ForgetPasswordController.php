<?php

namespace App\Http\Controllers\Physician\Password;

use App\Models\User;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\UserVerifyCodeRequest;
use App\Http\Requests\Password\UserVerifyEmailRequest;
use App\Http\Requests\Password\UserUpdatePassowrdRequest;
use App\Repositories\Password\PasswordRecoveryRepository;

class ForgetPasswordController extends Controller
{
    private PasswordRecoveryRepository $password;
    public function __construct(PasswordRecoveryRepository $password, User $user)
    {
        $this->password = $password;
        $this->password->setModel($user);
    }

    public function verifyEmail(UserVerifyEmailRequest $request)
    {
        try {
            $this->password->verifyEmail($request->email);
            $data = api_success('Verification code has been sent on your email successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }


    public function verifyCode(UserVerifyCodeRequest $request)
    {
        try {
            $this->password->verifyCode($request->email, $request->code);
            $data = api_success('code verified');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function updatePassword(UserUpdatePassowrdRequest $request)
    {
        try {
            $this->password->updatePassword($request->password, $request->validated());
            $data = api_success('password has been updated successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function verifyEmailUserId(UserVerifyEmailRequest $request)
    {
        try {
            $this->password->verifyEmail($request->email);
            $data = api_success('Verification code has been sent on your email successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function verifyCodeUserId(UserVerifyCodeRequest $request)
    {
        try {
            $this->password->verifyCodeUserId($request->email, $request->code);
            $this->password->sendUserId($request->email); // Sends the user ID if verification passes
            $data = api_success('User ID has been sent to your email.');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }
}
