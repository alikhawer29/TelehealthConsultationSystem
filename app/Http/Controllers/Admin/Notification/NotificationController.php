<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Filters\Admin\NotificationFilters;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Repositories\Notification\NotificationRepository;
use Illuminate\Http\Request;
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
            $notifications = $this->notification->paginate(
                request('per_page', 10),
                filter: $filter,
            );
            $total_notifications = 0;
            if (request()->filled('unread_only') == 1) {
                $total_notifications = $this->notification->getTotal($filter);
            }
            $data = api_successWithData('user notifications', compact('notifications', 'total_notifications'));
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
}
