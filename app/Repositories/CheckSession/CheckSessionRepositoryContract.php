<?php

namespace App\Repositories\CheckSession;

use App\Core\Abstracts\Filters;
use Illuminate\Database\Eloquent\Model;

interface CheckSessionRepositoryContract
{
    public function setModel(Model $model);
    public function create(array $params): Model;
    public function getLogsByAppointment($appointmentId, Filters $filter = null);
    public function getUserAttendanceLogs($userId, Filters $filter = null);
    public function checkIfUserAttendedSession($appointmentId, $userId): bool;
}