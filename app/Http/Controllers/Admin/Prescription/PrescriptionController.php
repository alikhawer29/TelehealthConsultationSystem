<?php

namespace App\Http\Controllers\Admin\Prescription;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Prescription\PrescriptionRepository;
use App\Filters\Admin\PrescriptionFilters;
use App\Http\Resources\PrescriptionResource;
use App\Models\Appointment;

class PrescriptionController extends Controller
{
    private PrescriptionRepository $prescription;

    public function __construct(PrescriptionRepository $prescriptionRepo)
    {
        $this->prescription = $prescriptionRepo;
        $this->prescription->setModel(Prescription::make());
    }

    public function index(PrescriptionFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
            ]);

            $prescriptions = $this->prescription
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['doctor.file', 'patient.file', 'appointment', 'file', 'creator'] // Add relations here
                );

            return response()->json([
                'success' => true,
                'message' => 'Prescriptions listing',
                'data' => $prescriptions
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $prescription = $this->prescription->findById($id, relations: ['doctor.file', 'patient.file', 'appointment', 'file', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Prescription detail',
                'data' => new PrescriptionResource($prescription)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'nullable|exists:users,id',
                'patient_id' => 'nullable|exists:users,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'medication' => 'nullable|string',
                'dosage' => 'nullable|string',
                'status' => 'nullable|boolean',
                'role' => 'required|in:user,doctor,admin',
                'file_name' => 'nullable|string',
                'type' => 'nullable|string',
            ]);

            $prescription = $this->prescription->create($validated);

            $this->sendPrescriptionNotifications($prescription, 'created');

            return response()->json([
                'success' => true,
                'message' => 'Prescription created successfully',
                'data' => new PrescriptionResource($prescription)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'medication' => 'sometimes|string',
                'dosage' => 'sometimes|string',
                'status' => 'sometimes|boolean'
            ]);

            $prescription = $this->prescription->updatePrescription($id, $validated);
            $this->sendPrescriptionNotifications($prescription, 'updated');

            return response()->json([
                'success' => true,
                'message' => 'Prescription updated successfully',
                'data' => new PrescriptionResource($prescription)
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        try {
            $status = $this->prescription->status($id);

            return response()->json([
                'success' => true,
                'message' => 'Prescription status updated',
                'data' => ['status' => $status]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendPrescriptionNotifications($prescription, string $action): void
    {
        try {
            $doctor = User::find($prescription->doctor_id);
            $patient = User::find($prescription->patient_id);

            if (!$doctor || !$patient) {
                return;
            }

            $role = null;

            if ($doctor->role == 'doctor') {
                $role = 'Dr.';
            } else {
                $role = 'Healthcare Professional';
            }

            $doctorName = trim("{$doctor->first_name} {$doctor->last_name}");
            $patientName = trim("{$patient->first_name} {$patient->last_name}");

            // Notification for Patient
            $this->prescription->notification()->send(
                $patient,
                title: 'Prescription ' . ucfirst($action),
                body: "Dear Patient {$patientName}, your prescription has been uploaded.",
                data: [
                    'id' => $prescription->id,
                    'type' => 'prescription',
                    'action' => $action,
                    'route' => json_encode([
                        'name' => 'prescription.details',
                        'params' => ['id' => $prescription->id],
                    ]),
                ]
            );


            // Notification for Doctor
            $this->prescription->notification()->send(
                $doctor,
                title: 'Prescription ' . ucfirst($action),
                body: "{$role} {$doctorName}, the Admin has uploaded a prescription for patient {$patientName}.",
                data: [
                    'id' => $prescription->id,
                    'type' => 'prescription',
                    'action' => $action,
                    'route' => json_encode([
                        'name' => 'doctor.prescription.details',
                        'params' => ['id' => $prescription->id],
                    ]),
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send prescription notifications: ' . $e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $prescription = $this->prescription->findById($id);

            if (!$prescription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prescription not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $prescription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Prescription deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function patients(): JsonResponse
    {
        try {

            $patients = User::where('role', 'user')
                ->where('status', 1)
                ->get()
                ->sortBy(fn($user) => strtolower($user->first_name)) // 👈 force lowercase before comparing
                ->values(); // reindex collection
            $data = api_successWithData('patients data', $patients);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function users(): JsonResponse
    {
        try {

            $users = User::whereIn('role', ['doctor', 'nurse', 'physician'])
                ->where('status', 1)
                ->get()
                ->sortBy(fn($user) => strtolower($user->first_name)) // 👈 force lowercase before comparing
                ->values(); // reindex collection
            $data = api_successWithData('users data', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
