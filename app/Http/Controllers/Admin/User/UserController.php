<?php

namespace App\Http\Controllers\Admin\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\Admin\UsersFilters;
use App\Filters\Admin\BranchFilters;
use App\Http\Controllers\Controller;
use App\Filters\Admin\UserServiceFilters;
use App\Models\Appointment;
use App\Repositories\Appointment\AppointmentRepository;
use App\Repositories\User\UserRepository;

class UserController extends Controller
{

    private UserRepository $user;
    private AppointmentRepository $appointment;


    public function __construct(UserRepository $userRepo, User $user, AppointmentRepository $appointmentRepo, Appointment $appointment)
    {
        $this->user = $userRepo;
        $this->user->setModel($user);

        $this->appointment = $appointmentRepo;
        $this->appointment->setModel($appointment);
    }

    public function index(Request $request, UsersFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'role' => 'user'
            ]);

            $userListing = $this->user->paginate(
                request('per_page', 10),
                filter: $filter,
            );
            $data = api_successWithData('user listing', $userListing);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }
    public function show($id): JsonResponse
    {
        try {

            $user = $this->user
                ->findById(
                    $id,
                    relations: [
                        'file:id,fileable_type,fileable_id,path,name',
                        'insurance.file:id,fileable_type,fileable_id,path,name',
                        'passport'
                    ]
                );

            $data = api_successWithData('user data', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function status($id)
    {
        try {
            $user = $this->user->status($id);
            $data = api_successWithData('status has been updated', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function services($id, UserServiceFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sortBy' => 1,
                'user_id' => $id,
                'status' => 1
            ]);

            $service = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: [
                        'serviceProvider'
                    ],
                );
            $data = api_successWithData('service listing', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function services2(UserServiceFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sortBy' => 1,
                'user_id' => request('user_id'),
                'status' => 1
            ]);

            $service = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: [
                        'serviceProvider'
                    ],
                );
            $data = api_successWithData('service listing', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
