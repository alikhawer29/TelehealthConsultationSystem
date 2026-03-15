<?php

namespace App\Http\Controllers\User\ReminderSetting;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\ServiceBundle;
use Illuminate\Http\Response;
use App\Filters\Admin\BundleFilters;
use App\Filters\Admin\ReminderFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bundle\BundleRequest;
use App\Repositories\Service\ServiceRepository;
use App\Http\Requests\Bundle\UpdateBundleRequest;
use App\Http\Requests\Reminder\ReminderSettingRequest;
use App\Models\ReminderSetting;
use App\Repositories\Reminder\ReminderRepository;

class ReminderSettingController extends Controller
{
    private ReminderRepository $reminder;

    public function __construct(ReminderRepository $reminderRepo, ReminderSetting $reminder)
    {
        $this->reminder = $reminderRepo;
        $this->reminder->setModel($reminder);
    }

    public function index(Request $request, ReminderFilters $filters)
    {
        try {
            $filters->extendRequest([
                'user_type' => 'user',
                'owner' => 1
            ]);

            $data = $this->reminder
                ->findOne(
                    filter: $filters,
                );
            $data = api_successWithData('reminder data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(ReminderSettingRequest $request)
    {
        try {
            $data = $this->reminder->createReminder('user', $request->validated());
            $data = api_successWithData('reminder created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, UpdateBundleRequest $request)
    {
        try {
            $data = $this->bundle->updateBundle($id, $request->validated());
            $data = api_successWithData('slot updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $bundle = $this->bundle->withCount([
                'appointments as total_bookings' => function ($query) {
                    $query->where('bookable_type', 'App\Models\ServiceBundle');
                }
            ])->findById($id, relations: ['file', 'services', 'reviews']);

            return response()->json(api_successWithData('bundles details', $bundle), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function status($id)
    {
        try {
            $this->bundle->status($id);
            $bundle = $this->bundle->findById($id);
            $data = api_successWithData('status has been updated', $bundle);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function services()
    {
        try {
            $services = Service::where('type', 'lab')
                ->where('status', 1)
                ->select('id', 'name')
                ->get();

            return response()->json(api_successWithData('lab services', $services), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
