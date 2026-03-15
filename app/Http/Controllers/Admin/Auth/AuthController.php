<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Repositories\Auth\AuthRepository;
use App\Http\Requests\Auth\AdminLoginRequest;

class AuthController extends Controller
{
    private AuthRepository $auth;

    public function __construct(Admin $admin)
    {
        $this->auth = new AuthRepository($admin);
    }

    public function login(AdminLoginRequest $request)
    {
        try {
            $validatedData = array_merge($request->validated(), ['role' => 'admin']);
            $token = $this->auth->login($validatedData, true);
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



    public function logout(Request $request)
    {
        try {
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
