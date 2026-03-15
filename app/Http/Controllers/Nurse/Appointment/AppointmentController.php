<?php

namespace App\Http\Controllers\Nurse\Appointment;

use App\Models\Slot;
use App\Models\Admin;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\Nurse\AppointmentFilters;
use App\Http\Resources\AppointmentResource;
use App\Core\Notifications\DatabaseNotification;
use App\Http\Requests\Appointment\RescheduleRequest;
use App\Repositories\Appointment\AppointmentRepository;
use App\Http\Requests\Appointment\GetAppointmentRequest;
use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Http\Requests\Payment\CreateAppointmentPaymentRequest;

class AppointmentController extends Controller
{
    private AppointmentRepository $appointment;

    public function __construct(AppointmentRepository $appointmentRepo)
    {
        $this->appointment = $appointmentRepo;
        $this->appointment->setModel(Appointment::make());
    }

    public function index(AppointmentFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
                'owner' => 1,
                'payment_status' => 'paid'

            ]);
            $appointment = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['bookable', 'user']

                );
            $data = api_successWithData('appointment listing', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }


    public function show($id)
    {
        try {

            $appointment = $this->appointment
                ->findById(
                    $id,
                    relations: [
                        'user',
                        'bookable',
                        'familyMember',
                        'address',
                        'reviews',
                        'bundleServices.service.file',
                        'serviceProvider.file',
                        'serviceProvider',
                    ]
                );
            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function updateAppointmentStatus(Request $request, $id, $action)
    {
        try {
            $appointment = $this->appointment->findById(
                $id,
                relations: [
                    'user',
                    'bookable',
                    'familyMember',
                    'address',
                    'reviews',
                    'bundleServices.service.file',
                    'serviceProvider.file',
                    'serviceProvider',
                ]
            );

            if (!$appointment) {
                return response()->json(api_error('Booking Not Found'), Response::HTTP_OK);
            }

            $statusUpdate = [];
            $message = '';

            switch ($action) {
                case 'cancel':
                    // Validate reason
                    $request->validate([
                        'reason' => 'required|string|max:255',
                    ]);

                    $statusUpdate = [
                        'status' => 'cancelled',
                        'provider_reason' => $request->input('reason'),
                    ];
                    $message = 'Booking cancelled successfully';
                    $this->appointment->sendServiceProviderCancelNotification($appointment, 'nurse');
                    break;

                case 'complete':
                    $statusUpdate = [
                        'appointment_status' => 'completed',
                    ];
                    $message = 'Booking completed successfully';
                    break;

                case 'ontheway':
                    $statusUpdate = [
                        'appointment_status' => 'ontheway',
                    ];
                    $message = 'Your booking is on the way.';
                    $this->appointment->sendOnTheWayNotification($appointment);
                    break;

                default:
                    return response()->json(api_error('Invalid action'), Response::HTTP_BAD_REQUEST);
            }

            $this->appointment->update($id, $statusUpdate);

            return response()->json(api_success($message), Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(api_error($e->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function notes($id)
    {
        try {

            $appointment = $this->appointment->findById($id);
            if ($appointment) {
                $appointment = $this->appointment
                    ->update($id, [
                        'notes' => request('notes')

                    ]);
                $appointment =  api_success('Note submitted successfully');
            } else {
                $appointment = api_error('Booking Not Found');
            }

            return response()->json($appointment, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMonthlyAppointments()
    {
        try {
            $appointments = $this->appointment->getNurseAppointmentsGroupedByMonth();
            return response()->json(api_successWithData('Appointments fetched successfully.', $appointments));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
