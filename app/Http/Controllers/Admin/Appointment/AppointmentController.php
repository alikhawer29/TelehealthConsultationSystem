<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Models\Slot;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Filters\Admin\AppointmentFilters;
use App\Http\Requests\Appointment\AssignPhysicianRequest;
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
                // 'payment_status' => 'paid'

            ]);
            $appointment = $this->appointment
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['bookable', 'user', 'serviceProvider', 'familyMember']

                );
            $data = api_successWithData('appointment listing', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function create(BookAppointmentRequest $request)
    {
        try {
            $data = $this->appointment->create($request->validated());
            $data = api_successWithData('booking successfull', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update(RescheduleRequest $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $validatedData = $request->validated();
            $data = $this->appointment->reschedule($id, $validatedData);
            $data = api_successWithData('Successfully updated.', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function purchase(CreateAppointmentPaymentRequest $request): JsonResponse
    {
        try {
            $paymentResponse = $this->appointment->payment($request->validated());

            return response()->json(api_successWithData('Payment confirmed successfully', $paymentResponse), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }



    public function show($id)
    {
        try {

            $appointment = $this->appointment
                ->findById(
                    $id,
                    relations: [
                        'bookable.file',
                        'familyMember',
                        'serviceProvider.file',
                        'serviceProvider',
                        'address',
                        'bundleServices.service.file',
                        'reviews',
                        'user'
                    ]
                );
            // $appointment = new AppointmentResource($appointment);
            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function approveReject()
    {
        try {

            $status = request('approve') == '1' ? 'approved' : 'rejected';
            $get_requested = $this->appointment->findById(request('appointment_id'));

            if ($get_requested && $get_requested->appointment_status == 'requested') {
                $appointment = $this->appointment
                    ->update(request('appointment_id'), [
                        'appointment_status' => $status,
                        'appointment_date' => request('approve') == '1' ? $get_requested->request_date : $get_requested->appointment_date,
                        'appointment_start_time' => request('approve') == '1' ? $get_requested->request_start_time : $get_requested->appointment_start_time,
                        'appointment_end_time' => request('approve') == '1' ? $get_requested->request_end_time : $get_requested->appointment_end_time,

                    ]);
                if (request('approve') == '1') {
                    $slot = Slot::where('start_time', $get_requested->request_start_time)
                        ->where('end_time', $get_requested->request_end_time)
                        ->where('slotable_type', $get_requested->bookable_type)
                        ->where('slotable_id', $get_requested->bookable_id)
                        ->first();
                    $slot->booking_status = 1;
                    $slot->save();
                    $appointment =  api_success('appointment date and time has been updated successfully');
                } else {
                    $appointment = api_success('appointment request has been rejected');
                }
            } else {
                $appointment = api_error('New slot is not requested by Coach, wait for approval');
            }

            return response()->json($appointment, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancel($id)
    {
        try {

            $appointment = $this->appointment->findById($id);
            if ($appointment) {
                $appointment = $this->appointment
                    ->update($id, [
                        'status' => 'cancelled',
                        'reason' => request('reason')

                    ]);
                $appointment =  api_success('Booking cancelled successfully');
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
            $appointments = $this->appointment->getAppointmentsGroupedByMonth();
            return response()->json(api_successWithData('Appointments fetched successfully.', $appointments));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function physicians()
    {
        try {
            $physicians = User::where('role', 'physician')
                ->where('status', 1)
                ->with('file:id,path,fileable_id,fileable_type')
                ->get();

            return response()->json(api_successWithData('Physicians', $physicians));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function nurses()
    {
        try {
            $physicians = User::where('role', 'nurse')
                ->where('status', 1)
                ->with('file:id,path,fileable_id,fileable_type')
                ->get()
                ->map(function ($user) {
                    $user->name = $user->first_name . ' ' . $user->last_name;
                    return $user;
                });

            return response()->json(api_successWithData('Physicians', $physicians));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignPhysician(AssignPhysicianRequest $request)
    {

        try {
            $data = $this->appointment->assignPhysician($request->validated());
            return response()->json(api_successWithData('Assign Successfully', $data));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //for admin sidebar
    public function allNewAppointments($id)
    {
        try {
            $appointment = Appointment::whereIn('status', ['scheduled', 'requested'])
                ->where('appointment_status', 'upcoming')
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('status', 'requested')
                            ->whereDate('request_date', '>=', now());
                    })->orWhere(function ($q) {
                        $q->where('status', 'scheduled')
                            ->whereDate('appointment_date', '>=', now());
                    });
                })
                ->count();
            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    //for admin sidebar
    public function serviceNewAppointments($type)
    {
        try {
            $serviceType = $type;

            $appointment = Appointment::whereIn('status', ['scheduled', 'requested'])
                ->where('appointment_status', 'upcoming')
                ->when($serviceType, function ($query) use ($serviceType) {
                    if ($serviceType === 'doctor') {
                        $query->where('service_type', 'doctor');
                    } else {
                        $query->where('service_type', '!=', 'doctor');
                    }
                })
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('status', 'requested')
                            ->whereDate('request_date', '>=', now());
                    })->orWhere(function ($q) {
                        $q->where('status', 'scheduled')
                            ->whereDate('appointment_date', '>=', now());
                    });
                })
                ->count();
            $data = api_successWithData('appointment details', $appointment);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }
}
