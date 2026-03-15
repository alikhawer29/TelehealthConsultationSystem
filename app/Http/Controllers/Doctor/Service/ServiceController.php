<?php

namespace App\Http\Controllers\Doctor\Service;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Service\ServiceRepository;
use App\Filters\User\ServiceFilters;
use App\Models\Service;

class ServiceController extends Controller
{
    private ServiceRepository $service;

    public function __construct(ServiceRepository $serviceRepo)
    {
        $this->service = $serviceRepo;
        $this->service->setModel(Service::make());
    }

    public function index(ServiceFilters $filter)
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
            ]);

            $service = $this->service
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['file:id,path,fileable_id,fileable_type']
                );

            $data = api_successWithData('services listing', $service);

            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, ServiceFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
            ]);

            $service = $this->service
                ->findById(
                    $id,
                    filter: $filter,
                    relations: ['file:id,path,fileable_id,fileable_type', 'slots']
                );

            $data = api_successWithData('service detail', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function slots($id)
    {
        try {
            $slots = $this->service
                ->slots($id, request('date'));
            $data = api_successWithData('slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function labSlots()
    {
        try {
            $slots = $this->service
                ->labSlots(request('date'));
            $data = api_successWithData('slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }
}
