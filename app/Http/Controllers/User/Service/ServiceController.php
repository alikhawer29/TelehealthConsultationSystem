<?php

namespace App\Http\Controllers\User\Service;


use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\User\ServiceFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Repositories\Service\ServiceRepository;

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

            $services = $this->service
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['file:id,path,fileable_id,fileable_type', 'icon']
                );

            // Convert full pagination with transformed items
            $transformedPaginator = $services->toArray();
            $transformedPaginator['data'] = ServiceResource::collection($services->items());

            // Return with your custom helper
            $data = api_successWithData('services listing', $transformedPaginator);
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
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: ['file:id,path,fileable_id,fileable_type', 'slots', 'reviews', 'icon']
                );

            $serviceType = $service->type;

            // 🔁 Get similar services (exclude current one)
            $similarServices = Service::with('file:id,path,fileable_id,fileable_type') // add other relations if needed
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->where('id', '!=', $id)
                ->where('type', $serviceType)
                ->where('status', 1) // Optional: only active
                ->get();

            // ⛏️ Add similar_service to response
            $service['similar_service'] = $similarServices;

            $data = api_successWithData('service detail', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    //homecare - nursing care slots
    public function slots()
    {
        try {
            $date = request('date');
            $type = request('type');

            $slots = $this->service
                ->slotsNew($date, $type);
            $data = api_successWithData('slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    //lab slots
    public function labSlots()
    {
        try {
            $slots = $this->service
                ->labSlots(request('date'));
            $data = api_successWithData('lab slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    // iv drip slots
    public function ivDripSlots()
    {
        try {
            $slots = $this->service
                ->ivDripSlots(request('date'));
            $data = api_successWithData('iv drip slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function checkSlots()
    {
        try {
            $slots = $this->service->slotsForUser(
                request('id'),          // optional for lab/iv_drip
                request('date'),
                request('type', 'homecare')
            );

            return response()->json(api_successWithData('Slots retrieved successfully', $slots));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()));
        }
    }
}
