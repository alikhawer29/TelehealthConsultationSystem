<?php

namespace App\Repositories\CheckSession;

use App\Models\CheckSessionAttendedUserLog;
use App\Core\Abstracts\Filters;
use App\Core\Abstracts\Repository\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class CheckSessionRepository extends BaseRepository implements CheckSessionRepositoryContract
{
    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function create(array $params): Model
    {
        try {
            return $this->model->create([
                'appointment_id' => $params['appointment_id'],
                'user_id' => $params['user_id']
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getLogsByAppointment($appointmentId, Filters $filter = null)
    {
        try {
            $query = $this->model->where('appointment_id', $appointmentId)
                ->with(['user:id,name,email', 'appointment:id,appointment_date,status']);

            if ($filter) {
                $query = $filter->apply($query);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getUserAttendanceLogs($userId, Filters $filter = null)
    {
        try {
            $query = $this->model->where('user_id', $userId)
                ->with(['appointment:id,appointment_date,status,service_type']);

            if ($filter) {
                $query = $filter->apply($query);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function checkIfUserAttendedSession($appointmentId, $userId): bool
    {
        try {
            return $this->model->where('appointment_id', $appointmentId)
                ->where('user_id', $userId)
                ->exists();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}