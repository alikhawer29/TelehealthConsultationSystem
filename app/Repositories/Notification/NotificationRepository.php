<?php

namespace App\Repositories\Notification;

use App\Core\Abstracts\Filters;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Core\Notifications\PushNotification;
use App\Repositories\Notification\NotificationRepositoryContract;
use Illuminate\Support\Facades\Notification;

class NotificationRepository extends BaseRepository implements NotificationRepositoryContract
{

    private $via = ['firebase', 'database'];

    public function markAsRead($id = null, array $params, Filters|null $filter = null)
    {
        try {
            $message = ''; // To store the success message
            if ($id !== null && $id !== ':id') {
                $model = $this->model->filter($filter)->where('id', $id)->first();
                // Check if `read_at` is null or not, and toggle its value
                if ($model->read_at === null) {
                    $model->update(array_merge($params, ['read_at' => now()]));
                    $message = 'marked as read';
                } else {
                    $model->update(array_merge($params, ['read_at' => null]));
                    $message = 'marked as unread';
                }
            } else {
                $model = $this->model->filter($filter)->update($params);
                $message = 'marked all as read';
            }
            return $message;;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function via($via)
    {
        $this->via = $via;
        return $this;
    }

    public function send(mixed $user, ...$params)
    {
        Notification::send($user, new PushNotification($this->via, ...$params));
    }
}