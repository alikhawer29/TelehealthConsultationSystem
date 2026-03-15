<?php

namespace App\Http\Controllers\User\Account;


use App\Models\Insurance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Account\InsuranceRequest;
use App\Repositories\Account\AccountRepository;
use App\Http\Requests\Password\ChangePasswordRequest;
use App\Http\Requests\Account\UserFamilyAccountRequest;
use App\Http\Requests\Account\UserUpdateAccountRequest;

class AccountController extends Controller
{
    private AccountRepository $account;

    public function __construct(
        AccountRepository $account,
    ) {
        $this->account = $account;
        $this->middleware(function ($request, $next) {

            $this->account->setModel($request->user());
            return $next($request);
        });
    }

    public function index()
    {
        try {

            $user = $this->account
                ->getProfile(relations: [
                    'file:id,path,fileable_id,fileable_type',
                    'familyMembers',
                    'insurance.file:id,path,fileable_id,fileable_type'
                ]);
            if ($user->status == 1) {;
                $data = api_successWithData('User Details', new UserResource($user));
                return response()->json($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(UserUpdateAccountRequest $request)
    {
        try {
            $data = $this->account->updateUser($request->validated());
            $data = api_successWithData('User Updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function insurance(InsuranceRequest $request)
    {
        try {
            $data = $this->account->insurance($request->validated());
            $data = api_successWithData('Insurance Updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function familyMember(UserFamilyAccountRequest $request)
    {
        try {
            $data = $this->account->familyMember($request->validated());
            $data = api_successWithData('Family Member Updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function familyMemberEdit(UserFamilyAccountRequest $request, $id)
    {
        try {
            $data = $this->account->familyMemberEdit($request->validated(), $id);
            $data = api_successWithData('Update Successfully', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function familyMemberRemove($id)
    {
        try {
            $data = $this->account->familyMemberRemove($id);
            $data = api_successWithData('Family member removed successfully', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function familyMemberList()
    {
        try {

            $data = $this->account->getfamilyMembers();
            $data = api_successWithData('Family member list', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
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
