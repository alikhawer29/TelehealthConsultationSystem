<?php

namespace App\Http\Controllers\User\Advertisement;

use App\Filters\User\AdvertisementFilters;
use App\Models\Advertisement;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\AdvertisementRequest;
use App\Repositories\Advertisement\AdvertisementRepository;

class AdvertisementController extends Controller
{
    private AdvertisementRepository $advertisement;

    public function __construct(AdvertisementRepository $advertisementRepo)
    {
        $this->advertisement = $advertisementRepo;
        $this->advertisement->setModel(Advertisement::make());
    }

    public function index(AdvertisementFilters $filter)
    {
        try {
            $data = $this->advertisement->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: [
                    'files:id,fileable_type,fileable_id,path',
                ]
            );
            $data = api_successWithData('advertisement data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(AdvertisementRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $data = $this->advertisement->create($params);
            $data = api_successWithData('added successfully.', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $advertisement = $this->advertisement
                ->findById(
                    $id,
                    relations: [
                        'file:id,fileable_type,fileable_id,path',
                    ]
                );
            $data = api_successWithData('advertisement data', $advertisement);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(AdvertisementRequest $request, $id): JsonResponse
    {
        try {
            $this->advertisement->update($id, $request->validated());
            $data = api_success('Successfully updated.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }



    public function status($id): JsonResponse
    {
        try {
            $this->advertisement->status($id);
            $data = api_success('status has been updated');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
