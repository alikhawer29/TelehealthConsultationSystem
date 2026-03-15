<?php

namespace App\Http\Controllers\Nurse\Account;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DoctorUpdateAccountRequest;
use App\Http\Requests\Account\NurseUpdateAccountRequest;
use App\Repositories\Account\AccountRepository;
use App\Http\Requests\Password\ChangePasswordRequest;
use App\Http\Requests\Account\UserFamilyAccountRequest;
use App\Http\Requests\Account\UserUpdateAccountRequest;
use App\Models\Specialty;

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

            $data = $this->account
                ->getProfile(relations: ['file:id,path,fileable_id,fileable_type', 'education', 'license.file']);
            if ($data->status == 1) {;
                $data = api_successWithData('Nurse Details',  $data);
                return response()->json($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(NurseUpdateAccountRequest $request)
    {
        try {
            $data = $this->account->updateNurse($request->validated());
            $data = api_successWithData('Nurse Updated', $data);
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

    public function specialities()
    {
        try {
            $data = Specialty::where('status', 1)->get();
            $data = api_successWithData('Specialites', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
