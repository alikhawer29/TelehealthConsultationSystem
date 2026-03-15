<?php

namespace App\Http\Controllers\Admin\HealthProfessional;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\Admin\UsersFilters;
use App\Filters\Admin\BranchFilters;
use App\Filters\Admin\HealthCareServiceFilters;
use App\Http\Controllers\Controller;
use App\Filters\Admin\UserServiceFilters;
use App\Http\Requests\HealthCare\CreateHealthCareRequest;
use App\Http\Requests\HealthCare\UpdateHealthCareRequest;
use App\Models\Appointment;
use App\Repositories\Appointment\AppointmentRepository;
use App\Repositories\User\UserRepository;

class HealthProfessionalController extends Controller
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
                'notUser' => 'user'
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
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                ])
                ->findById(
                    $id,
                    relations: ['education', 'license.file', 'file', 'sessionType', 'reviews']

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
            $this->user->status($id);
            $user = $this->user->findById($id);
            $data = api_successWithData('status has been updated', $user);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function create(CreateHealthCareRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $data = $this->user->createHealthCare($params);
            $data = api_successWithData('Successfully created.', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update(UpdateHealthCareRequest $request, $id): JsonResponse
    {
        try {
            $params = $request->validated();
            $data = $this->user->updateHealthCare($id, $params);
            $data = api_successWithData('Successfully updated.', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function appointments(HealthCareServiceFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sortBy' => 1,
                'bookable_id' => request('id'),
                'status' => 1
            ]);

            $service = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: [
                        'serviceProvider',
                        'user',
                        // 'bookable'
                    ],
                );
            $data = api_successWithData('appointment listing', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
