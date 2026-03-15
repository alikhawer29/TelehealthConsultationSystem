<?php

namespace App\Http\Controllers\Admin\MedicalOrder;

use App\Filters\Admin\AppointmentFilters;
use App\Filters\User\MedicalOrderFilters;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Http\Requests\MedicalOrder\AdminMedicalOrderRequest;
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

    public function update(AdminMedicalOrderRequest $request, $id): JsonResponse
    {
        try {
            $this->medicalOrder->updateStatus($request->validated(), $id);
            return response()->json(api_success('Successfully updated.'), Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(api_error('Update failed: ' . $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $details = $this->medicalOrder->findById(
                $id,

            );
            $data = api_successWithData('medical order data', $details);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
