<?php

namespace App\Http\Controllers\Physician\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorLoginRequest;
use App\Repositories\Auth\AuthRepository;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;

class AuthController extends Controller
{
    private AuthRepository $auth;

    public function __construct(User $user)
    {
        $this->auth = new AuthRepository($user);
    }

    public function login(DoctorLoginRequest $request)
    {
        try {
            $token = $this->auth->login($request->validated(), true);

            $data = api_successWithData('login successfully', [
                'role' => $token['role'],
                'token' => $token['token'],
                'user' =>  $token['user'],
            ]);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function register(UserRegisterRequest $request)
    {
        try {

            $validatedData = array_merge($request->validated(), ['role' => 'user']);
            $this->auth->registerUser($validatedData, true);
            $data = api_success('registered successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Set the user's login status to 'inactive'
            $user = $request->user();
            $user->update(['login_status' => 'inactive']);

            $this->auth
                ->setModel($request->user())
                ->logout();

            $data = api_success('logout successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

}
