<?php

namespace App\Http\Controllers\Physician\Nurse;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\User\UsersFilters;
use App\Http\Controllers\Controller;
use App\Repositories\User\UserRepository;
use App\Http\Requests\Auth\UserCreateRequest;
use App\Http\Requests\Auth\UserUpdateRequest;

class NurseController extends Controller
{

    private UserRepository $user;


    public function __construct(UserRepository $userRepo, User $user)
    {
        $this->user = $userRepo;
        $this->user->setModel($user);
    }


    public function index(Request $request, UsersFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'role' => 'nurse',
            ]);

            $users = $this->user->paginate(
                request('per_page', 10),
                filter: $filter,
            );

            $data = api_successWithData('Nurse listing', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $user = $this->user
                ->findById(
                    $id,
                    relations: ['file', 'education', 'license']
                );
            $data = api_successWithData('Nurse details', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(UserCreateRequest $request)
    {
        try {

            $this->user->create($request->validated(), true);
            $data = api_success('User created successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function update($id, UserUpdateRequest $request)
    {
        try {
            $this->user->updateUser($id, $request->validated());
            $data = $this->user->findById($id);
            $data = api_successWithData('updated successfully', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function status($id)
    {
        try {
            $this->user->status($id);
            $user = $this->user->findById($id);
            $data = api_successWithData('status has been updated', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->user->findById($id); // Find the user or fail if not found
            $user->email = $user->email . '_' . now()->timestamp;
            $user->save();
            $user->delete();
            $data = api_success('Deleted successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }
}
