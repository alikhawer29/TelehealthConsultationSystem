<?php

namespace App\Http\Controllers\Admin\PushNotification;

use App\Filters\Admin\PushNotificationFilters;
use App\Models\PushNotification;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PushNotification\PushNotificationRequest;
use App\Repositories\PushNotification\PushNotificationRepository;

class PushNotificationController extends Controller
{
    private PushNotificationRepository $pushNotification;

    public function __construct(PushNotificationRepository $pushNotificationRepo, PushNotification $pushNotification)
    {
        $this->pushNotification = $pushNotificationRepo;
        $this->pushNotification->setModel(PushNotification::make());
    }

    public function index(PushNotificationFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
            ]);
            $data = $this->pushNotification->paginate(
                request('per_page', 10),
                filter: $filter,
            );
            $data = api_successWithData('notification data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(PushNotificationRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->pushNotification->create($params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
    public function show($id): JsonResponse
    {
        try {

            $data = $this->pushNotification->findById($id);
            $data = api_successWithData('details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
