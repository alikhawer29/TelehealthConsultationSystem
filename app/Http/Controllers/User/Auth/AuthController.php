<?php

namespace App\Http\Controllers\User\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\Auth\AuthRepository;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Requests\Password\UserVerifyTokenRequest;

class AuthController extends Controller
{
    private AuthRepository $auth;

    public function __construct(User $user)
    {
        $this->auth = new AuthRepository($user);
    }

    public function login(UserLoginRequest $request)
    {

        try {
            $validatedData = array_merge($request->validated(), ['role' => 'user']);
            $token = $this->auth->login($validatedData);

            $data = api_successWithData('login successfully', [
                'role' => $token['role'],
                'token' => $token['token'],
                'user' => new UserResource($token['user']->load(['file', 'insurance.file'])),
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
