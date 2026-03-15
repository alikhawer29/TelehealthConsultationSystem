<?php

namespace App\Http\Controllers\Admin\MedicalReport;

use App\Models\User;
use App\Models\Appointment;
use App\Models\MedicalOrder;
use App\Models\Prescription;
use App\Models\MedicalReport;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\User\MedicalReportsFilters;
use App\Http\Requests\Appointment\MedicalReportRequest;
use App\Repositories\MedicalReport\MedicalReportRepository;

class MedicalReportController extends Controller
{
    private MedicalReportRepository $medical;

    public function __construct(MedicalReportRepository $medical)
    {
        $this->medical = $medical;
        $this->medical->setModel(MedicalReport::make());
    }

    public function index(MedicalReportsFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 'desc',
                //'owner' => 1,
            ]);

            $records = $this->medical
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['file']
                );

            $data = api_successWithData('medical reports listing', $records);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $details = $this->medical->findById(
                $id,
                relations: ['file']
            );
            $data = api_successWithData('medical data', $details);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(MedicalReportRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['user_id'] = auth()->id();
            $data = $this->medical->create($validated);
            $this->sendMedicalReportNotifications($data, 'created');
            $data = api_successWithData('medical reports created successfull', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update(MedicalReportRequest $request, $id): JsonResponse
    {
        try {
            $medicalReport = $this->medical->update($id, $request->validated());
            $this->sendMedicalReportNotifications($medicalReport, 'updated');
            $data = api_success('Successfully updated.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);
            $prescription->delete();

            $data = api_success('Deleted successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    private function sendMedicalReportNotifications($medicalReport, string $action): void
    {
        try {
            $currentUser = auth()->user();
            $fileName = $medicalReport->file_name;
            $fileType = $medicalReport->type;

            // Get patient (owner of the medical report)
            $patient = User::find($medicalReport->user_id);

            if (!$patient) {
                return;
            }

            $patientName = trim("{$patient->first_name} {$patient->last_name}");
            $doctorName = trim("{$currentUser->first_name} {$currentUser->last_name}");

            // Determine if the current user is a doctor or patient
            $isDoctor = $currentUser->id !== $patient->id;

            if ($isDoctor) {
                // Notification for Patient - Doctor uploaded/updated their report
                $this->medical->notification()->send(
                    $patient,
                    title: 'Medical Report ' . ucfirst($action),
                    body: "Dear {$patientName}, Dr. {$doctorName} has {$action} your medical report: {$fileName} ({$fileType}).",
                    data: [
                        'id' => $medicalReport->id,
                        'type' => 'medical_report',
                        'action' => $action,
                        'file_name' => $fileName,
                        'route' => json_encode([
                            'name' => 'medical.reports.details',
                            'params' => ['id' => $medicalReport->id],
                        ]),
                    ]
                );

                // Notification for Doctor - Confirmation
                $this->medical->notification()->send(
                    $currentUser,
                    title: 'Medical Report ' . ucfirst($action),
                    body: "Dr. {$doctorName}, you have {$action} the medical report for {$patientName}: {$fileName}.",
                    data: [
                        'id' => $medicalReport->id,
                        'type' => 'medical_report',
                        'action' => $action,
                        'file_name' => $fileName,
                        'route' => json_encode([
                            'name' => 'doctor.medical.reports.details',
                            'params' => ['id' => $medicalReport->id],
                        ]),
                    ]
                );
            } else {
                // Patient uploaded/updated their own report
                // Notification for Patient - Confirmation
                $this->medical->notification()->send(
                    $patient,
                    title: 'Medical Report ' . ucfirst($action),
                    body: "Dear {$patientName}, you have {$action} your medical report: {$fileName} ({$fileType}).",
                    data: [
                        'id' => $medicalReport->id,
                        'type' => 'medical_report',
                        'action' => $action,
                        'file_name' => $fileName,
                        'route' => json_encode([
                            'name' => 'medical.reports.details',
                            'params' => ['id' => $medicalReport->id],
                        ]),
                    ]
                );

                // If there's an associated appointment, notify the doctor too
                if (isset($medicalReport->appointment_id)) {
                    $appointment = Appointment::with('bookable')->find($medicalReport->appointment_id);

                    if ($appointment && $appointment->bookable) {
                        $doctor = $appointment->bookable;
                        $doctorName = trim("{$doctor->first_name} {$doctor->last_name}");

                        $this->medical->notification()->send(
                            $doctor,
                            title: 'Patient Medical Report ' . ucfirst($action),
                            body: "Dr. {$doctorName}, your patient {$patientName} has {$action} a medical report: {$fileName} ({$fileType}).",
                            data: [
                                'id' => $medicalReport->id,
                                'type' => 'medical_report',
                                'action' => $action,
                                'file_name' => $fileName,
                                'route' => json_encode([
                                    'name' => 'doctor.medical.reports.details',
                                    'params' => ['id' => $medicalReport->id],
                                ]),
                            ]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send medical report notifications: ' . $e->getMessage());
        }
    }
}
