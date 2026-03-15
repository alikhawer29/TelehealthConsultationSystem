<?php

namespace App\Http\Controllers\User\MedicalOrder;

use App\Filters\Admin\AppointmentFilters;
use App\Filters\User\MedicalOrderFilters;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Http\Requests\MedicalOrder\MedicalOrderRequest;
use App\Repositories\MedicalOrder\MedicalOrderRepository;
use App\Models\MedicalOrder;

class MedicalOrderController extends Controller
{
    private MedicalOrderRepository $medicalOrder;

    public function __construct(MedicalOrderRepository $medicalOrderRepo)
    {
        $this->medicalOrder = $medicalOrderRepo;
        $this->medicalOrder->setModel(MedicalOrder::make());
    }

    public function index(MedicalOrderFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'personal' => 1,
            ]);

            $appointments = $this->medicalOrder
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                );


            $data = api_successWithData('medical order listing', $appointments);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(MedicalOrderRequest $request, $id): JsonResponse
    {
        try {
            $this->medicalOrder->update($request->validated(), $id);
            $data = api_success('Successfully updated.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function create(MedicalOrderRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->medicalOrder->create($params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function status($id): JsonResponse
    {
        try {
            $data = $this->medicalOrder->status($id);
            $data = api_successWithData('status has been updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function destory($id): JsonResponse
    {
        try {
            $this->medicalOrder->delete($id);

            $data = api_success('address removed');
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
