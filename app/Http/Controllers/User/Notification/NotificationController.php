<?php

namespace App\Http\Controllers\User\Notification;

use App\Filters\User\NotificationFilters;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Repositories\Notification\NotificationRepository;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    private NotificationRepository $notification;
    public function __construct(NotificationRepository $repo, Notification $notification)
    {
        $this->notification = $repo;
        $this->notification->setModel($notification);
    }

    public function index(NotificationFilters $filter)
    {
        try {
            $filter->extendRequest([
                'personal' => request()->user(),
                'order' => 1
            ]);

            // Main paginated notification result
            $notifications = $this->notification->paginate(
                request('per_page', 10),
                filter: $filter,
            );

            // Get total unread
            $value = request()->user();
            $type = get_class($value);
            $id = $value->id;
            $total_unread_notifications = Notification::where("notifiable_type", $type)->where('notifiable_id', $id)->whereNull('read_at')->count();

            $data = api_successWithData('user notifications', compact('notifications', 'total_unread_notifications'));
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update($id = null)
    {
        try {
            $filter = new NotificationFilters(request());
            $filter->extendRequest([
                'personal' => request()->user(),
            ]);
            $message = $this->notification->markAsRead($id, params: ['read_at' => now()], filter: $filter);
            $data = api_success($message);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create()
    {
        try {
            $filter = new NotificationFilters(request());
            $filter->extendRequest([
                'personal' => request()->user(),
            ]);
            $this->notification->markAsRead(params: ['read_at' => now()], filter: $filter);
            $data = api_success('mark as read');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
