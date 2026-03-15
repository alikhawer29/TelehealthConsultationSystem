<?php

namespace App\Http\Controllers\User\CheckSession;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\CheckSession\CheckSessionRepository;
use App\Filters\User\CheckSessionFilters;
use App\Models\CheckSessionAttendedUserLog;

class CheckSessionAttendedUserLogController extends Controller
{
    private CheckSessionRepository $checkSessionRepo;

    public function __construct(CheckSessionRepository $checkSessionRepo)
    {
        $this->checkSessionRepo = $checkSessionRepo;
        $this->checkSessionRepo->setModel(CheckSessionAttendedUserLog::make());
    }

    public function index(CheckSessionFilters $filter): JsonResponse
    {
        try {
            $logs = $this->checkSessionRepo->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: ['user', 'appointment']
            );

            $data = api_successWithData('Attendance logs listing', $logs);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(): JsonResponse
    {
        try {
            $validated = request()->validate([
                'appointment_id' => 'required|exists:appointments,id',
                'user_id' => 'required|exists:users,id'
            ]);

            $log = $this->checkSessionRepo->create($validated);

            $data = api_successWithData('Attendance log created successfully', $log);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $log = $this->checkSessionRepo->findById(
                $id,
                relations: ['user', 'appointment']
            );

            $data = api_successWithData('Attendance log detail', $log);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAppointmentLogs($appointmentId): JsonResponse
    {
        try {
            $logs = $this->checkSessionRepo->getLogsByAppointment($appointmentId);

            $data = api_successWithData('Appointment attendance logs', $logs);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserLogs($userId): JsonResponse
    {
        try {
            $logs = $this->checkSessionRepo->getUserAttendanceLogs($userId);

            $data = api_successWithData('User attendance logs', $logs);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkAttendance($appointmentId, $userId): JsonResponse
    {
        try {
            $attended = $this->checkSessionRepo->checkIfUserAttendedSession($appointmentId, $userId);

            $data = api_successWithData('Attendance check result', ['attended' => $attended]);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
