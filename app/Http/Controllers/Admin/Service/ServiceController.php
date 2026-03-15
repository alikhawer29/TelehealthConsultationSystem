<?php

namespace App\Http\Controllers\Admin\Service;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Filters\Admin\ServiceFilters;
use App\Repositories\Service\ServiceRepository;
use App\Http\Requests\Service\CreateServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Appointment;

class ServiceController extends Controller
{
    private ServiceRepository $service;

    public function __construct(ServiceRepository $serviceRepo)
    {
        $this->service = $serviceRepo;
        $this->service->setModel(Service::make());
    }

    public function index(Request $request, ServiceFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sort' => 1,
            ]);
            $data = $this->service
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                    relations: ['file', 'icon']

                );
            $data = api_successWithData('services data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, UpdateServiceRequest $request)
    {
        try {
            $data = $this->service->updateService($id, $request->validated());
            $data = api_successWithData('service updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(CreateServiceRequest $request)
    {
        try {
            $this->service->create($request->validated());
            $data = api_success('service created');
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
            $this->service->status($id);
            $data = $this->service->findById($id);
            $data = api_successWithData('service status has been updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function show1($id)
    {
        try {
            $data = $this->service
                ->withCount('appointments')
                ->findById(
                    $id,
                    relations: [
                        'file',
                        'appointments',
                        'reviews.reviewer',
                        // Filter only active slots
                        'slots' => function ($query) {
                            $query->where('modification_status', 'active');
                        },
                        'icon'
                    ]
                );

            // Get all booked slot IDs
            $bookedSlots = Appointment::where('service_type', '!=', 'doctor')->pluck('slot_id')->toArray();

            // Add isBooked key to each slot
            $data->slots = $data->slots->map(function ($slot) use ($bookedSlots) {
                $slot->isBooked = in_array($slot->id, $bookedSlots);
                return $slot;
            });

            $data = api_successWithData('service details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function show($id)
    {
        try {
            $data = $this->service
                ->withCount('appointments')
                ->findById(
                    $id,
                    relations: [
                        'file',
                        'appointments',
                        'reviews.reviewer',
                        // Filter only active slots
                        'slots' => function ($query) {
                            $query->where('modification_status', 'active')
                                ->orderBy('day')
                                ->orderBy('start_time');
                        },
                        'icon'
                    ]
                );

            // First: Get unique slots per day by time
            $uniqueSlots = collect();
            $seen = [];

            foreach ($data->slots as $slot) {
                $key = $slot->day . '|' . $slot->start_time . '|' . $slot->end_time;

                if (!in_array($key, $seen)) {
                    $seen[] = $key;
                    $uniqueSlots->push($slot);
                }
            }

            // Second: Get all booked slot IDs
            $bookedSlots = Appointment::where('service_type', '!=', 'doctor')
                ->pluck('slot_id')
                ->toArray();

            // Third: Add isBooked flag to each unique slot
            $uniqueSlots = $uniqueSlots->map(function ($slot) use ($bookedSlots) {
                $slot->isBooked = in_array($slot->id, $bookedSlots);
                return $slot;
            });

            $data->slots = $uniqueSlots->values();

            $data = api_successWithData('service details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
