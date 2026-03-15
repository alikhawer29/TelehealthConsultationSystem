<?php

namespace App\Http\Controllers\User\Nurse;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\User\UsersFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\NurseResource;
use App\Repositories\User\UserRepository;

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
            $data = api_successWithData('Nurse details', new NurseResource($user));
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
