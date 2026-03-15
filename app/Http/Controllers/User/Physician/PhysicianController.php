<?php

namespace App\Http\Controllers\User\Physician;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\User\UsersFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\PhysicianResource;
use App\Repositories\User\UserRepository;


class PhysicianController extends Controller
{

    private UserRepository $user;


    public function __construct(UserRepository $userRepo, User $user)
    {
        $this->user = $userRepo;
        $this->user->setModel($user);
    }


    public function index(UsersFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'role' => 'physician',
                'is_profile_completed' => 1
            ]);

            $users = $this->user->paginate(
                request('per_page', 10),
                filter: $filter,
            );

            $data = api_successWithData('Physician listing', $users);
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
            $data = api_successWithData('Physician details', new PhysicianResource($user));
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
