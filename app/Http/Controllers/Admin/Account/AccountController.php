<?php

namespace App\Http\Controllers\Admin\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\Account\AccountRepository;
use App\Http\Requests\Password\ChangePasswordRequest;
use App\Http\Requests\Account\AdminUpdateAccountRequest;

class AccountController extends Controller
{
    private AccountRepository $account;

    public function __construct(AccountRepository $account)
    {
        $this->account = $account;
        $this->middleware(function ($request, $next) {
            $this->account->setModel($request->user());
            return $next($request);
        });
    }

    public function index(Request $request)
    {

        try {
            $data = $this->account->getProfile(['file:id,path,fileable_id,fileable_type']);
            $data = new UserResource($data);
            $data = api_successWithData('Account details', $data);
            return response()->json($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(AdminUpdateAccountRequest $request)
    {
        try {
            $this->account->updateProfile($request->validated());
            $data = $this->account->getProfile(['file:id,path,fileable_id,fileable_type']);
            $data = new UserResource($data);
            $data = api_successWithData('account updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $this->account->deleteAccount();
            $data = api_success('account deleted');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {

        try {
            $this->account->changePassword($request->current_password, $request->password);
            $data = api_success('Password has been changed');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
