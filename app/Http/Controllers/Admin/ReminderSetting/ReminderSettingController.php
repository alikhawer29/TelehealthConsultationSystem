<?php

namespace App\Http\Controllers\Admin\ReminderSetting;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Filters\Admin\ReminderFilters;
use App\Http\Controllers\Controller;
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
                'user_type' => 'admin'
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
            $data = $this->reminder->createReminder('admin', $request->validated());
            $data = api_successWithData('reminder created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
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
