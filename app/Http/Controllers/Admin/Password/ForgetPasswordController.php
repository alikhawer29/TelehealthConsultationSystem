<?php

namespace App\Http\Controllers\Admin\Password;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\UserVerifyCodeRequest;
use App\Http\Requests\Password\AdminVerifyCodeRequest;
use App\Http\Requests\Password\UserVerifyEmailRequest;
use App\Http\Requests\Password\AdminVerifyEmailRequest;
use App\Http\Requests\Password\UserUpdatePassowrdRequest;
use App\Repositories\Password\PasswordRecoveryRepository;
use App\Http\Requests\Password\AdminUpdatePassowrdRequest;

class ForgetPasswordController extends Controller
{
    private PasswordRecoveryRepository $password;
    public function __construct(PasswordRecoveryRepository $password, Admin $admin)
    {
        $this->password = $password;
        $this->password->setModel($admin);
    }

    public function verifyEmail(AdminVerifyEmailRequest $request)
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


    public function verifyCode(AdminVerifyCodeRequest $request)
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

    public function updatePassword(AdminUpdatePassowrdRequest $request)
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
}
