<?php

namespace App\Repositories\Appointment;

use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\Cart;
use App\Models\Slot;
use App\Models\User;
use App\Models\Admin;
use App\Models\Coach;
use App\Models\Order;
use App\Models\Comment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Service;
use App\Models\BreedType;
use App\Models\Insurance;
use Stripe\PaymentIntent;
use App\Models\Appointment;
use App\Models\Commissions;
use App\Models\UserSession;
use App\Models\OrderProduct;
use App\Models\Psychologist;
use App\Models\Subscription;
use App\Models\BundleService;
use App\Models\ServiceBundle;
use App\Core\Abstracts\Filters;
use App\Models\AppointmentSession;
use Illuminate\Support\Facades\Crypt;
use App\Core\Wrappers\Payment\Gateway;
use App\Models\AppointmentFamilyMember;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Slot\SlotRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Core\Notifications\DatabaseNotification;
use App\Core\Abstracts\Repository\BaseRepository;

class AppointmentRepository extends BaseRepository implements AppointmentRepositoryContract
{

    protected $model;
    private PaymentRepository $payment;
    private OrderRepository $order;
    private SlotRepository $slot;

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->payment = new PaymentRepository();
        $this->payment->setModel(Payment::make());
        $this->order = new OrderRepository();
        $this->order->setModel(Order::make());
        $this->slot = new SlotRepository();
        $this->slot->setModel(Slot::make());
    }



    //physician and nurse both
    public function assignPhysician(array $params): ?Appointment
    {
        \DB::beginTransaction();
        try {
            $user = request()->user();
            $appointmentId = $params['appointment_id'];
            $physicianId   = $params['physician_id'];


            // Fetch appointment with lock
            $appointment = $this->model
                ->with(['bookable', 'user', 'serviceProvider', 'familyMember'])
                ->lockForUpdate()
                ->findOrFail($appointmentId);

            // Check if appointment already has a provider
            if (!empty($appointment->provider)) {
                throw new \Exception('Appointment already assigned.');
            }
            $patient   = $appointment->user;      // Patient

            // Update appointment details
            $appointment->update([
                'provider' => $physicianId,
                'status'   => 'scheduled',
            ]);

            // Prepare patient name
            $patientName = $appointment->family_member->name
                ?? trim(($appointment->user->first_name ?? '') . ' ' . ($appointment->user->last_name ?? ''));

            $commonData = [
                'id'   => $appointment->id,
                'type' => $appointment->service_type,
            ];
            $body = "Patient {$patientName}, has scheduled an appointment with you.";
            $payload = [
                'title' => 'New Booking',
                'body'  => $body,
                'data'  => array_merge($commonData, ['status' => 'scheduled'])
            ];

            // Notify physician
            $doctor = User::find($appointment->provider);
            $doctor->notify(new DatabaseNotification($payload));

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
            }

            // Notify patient
            $this->notification()->send(
                $patient,
                title: 'New Booking',
                body: "Patient {$patientName}, your appointment with Healthcare Care Professional. {$doctorName} has been booked successfully!",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'scheduled',
                    'sound'  => 'customSound',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );

            \DB::commit();
            return $appointment->fresh(); // Return updated model

        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    //user

    public function getUserAppointmentsGroupedByMonth()
    {
        $today = now()->toDateString();

        return Appointment::where('appointments.user_id', request()->user()->id)
            ->whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.payment_status', 'paid')
            ->where('appointments.appointment_status', 'upcoming')
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->where('appointments.status', 'requested')
                        ->whereDate('appointments.request_date', '>=', $today);
                })
                    ->orWhere(function ($q) use ($today) {
                        $q->where('appointments.status', 'scheduled')
                            ->whereDate('appointments.appointment_date', '>=', $today);
                    });
            })
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(
                CASE
                    WHEN appointments.status = "requested" THEN appointments.request_date
                    ELSE appointments.appointment_date
                END, "%Y-%m"
            ) as month,
            DATE_FORMAT(
                CASE
                    WHEN appointments.status = "requested" THEN appointments.request_date
                    ELSE appointments.appointment_date
                END, "%Y-%m-%d"
            ) as date,
            JSON_ARRAYAGG(JSON_OBJECT(
                "booking_id", appointments.booking_id,
                "service_type", appointments.service_type,
                "session_type", appointments.session_type,
                "bookable_id", appointments.bookable_id,
                "bookable_type", appointments.bookable_type,
                "start_time", appointments.appointment_start_time,
                "end_time", appointments.appointment_end_time,
                "status", appointments.status,
                "service_name",
                    CASE
                        WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN
                            CASE
                                WHEN doctors.first_name IS NOT NULL AND doctors.first_name != "" THEN doctors.first_name
                                ELSE "Doctor Name"
                            END
                        WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                        WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                        ELSE NULL
                    END
            )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                $appointments = json_decode($appointment->appointments, true);

                // Decrypt doctor names for each appointment
                $decryptedAppointments = array_map(function ($apt) {
                    if ($apt['bookable_type'] === 'App\\Models\\User') {
                        // If it's a doctor appointment, decrypt the name
                        $apt['service_name'] = $this->decryptDoctorName($apt['service_name']);
                    }
                    return $apt;
                }, $appointments);

                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => $decryptedAppointments,
                ];
            });
    }

    /**
     * Decrypt doctor's name from hash
     */
    private function decryptDoctorName($encryptedName)
    {
        try {

            // If you're using encryption (AES)
            return Crypt::decryptString($encryptedName);
        } catch (\Exception $e) {
            \Log::error('Error decrypting doctor name: ' . $e->getMessage());
            return 'Doctor';
        }
    }

    public function getAppointmentsGroupedByMonth()
    {
        return Appointment::where('appointments.user_id', request()->user()->id)
            ->whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.payment_status', 'paid')
            ->where('appointments.appointment_status', 'upcoming')
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(
                CASE
                    WHEN appointments.status = "requested" THEN appointments.request_date
                    ELSE appointments.appointment_date
                END, "%Y-%m"
            ) as month,
            DATE_FORMAT(
                CASE
                    WHEN appointments.status = "requested" THEN appointments.request_date
                    ELSE appointments.appointment_date
                END, "%Y-%m-%d"
            ) as date,
            JSON_ARRAYAGG(JSON_OBJECT(
                "booking_id", appointments.booking_id,
                "service_type", appointments.service_type,
                "session_type", appointments.session_type,
                "bookable_id", appointments.bookable_id,
                "bookable_type", appointments.bookable_type,
                "start_time", appointments.appointment_start_time,
                "end_time", appointments.appointment_end_time,
                "status", appointments.status,
                "service_name",
                    CASE
                        WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN doctors.first_name
                        WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                        WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                        ELSE NULL
                    END
            )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => json_decode($appointment->appointments),
                ];
            });
    }


    public function getDoctorAppointmentsGroupedByMonth()
    {
        return Appointment::where('appointments.bookable_id', request()->user()->id)
            ->where('appointments.bookable_type', 'App\Models\User')
            ->whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.appointment_status', 'upcoming')
            ->where('appointments.service_type', 'doctor')
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(appointments.appointment_date, "%Y-%m") as month,
                 DATE_FORMAT(appointments.appointment_date, "%Y-%m-%d") as date,
                 JSON_ARRAYAGG(JSON_OBJECT(
                    "booking_id", appointments.booking_id,
                    "service_type", appointments.service_type,
                    "session_type", appointments.session_type,
                    "bookable_id", appointments.bookable_id,
                    "bookable_type", appointments.bookable_type,
                    "start_time", appointments.appointment_start_time,
                    "end_time", appointments.appointment_end_time,
                    "service_name",
                        CASE
                            WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN doctors.first_name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                            ELSE NULL
                        END
                 )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => json_decode($appointment->appointments),
                ];
            });
    }

    public function getNurseAppointmentsGroupedByMonth()
    {
        return Appointment::where('appointments.provider', request()->user()->id)
            ->whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.appointment_status', 'upcoming')
            ->whereIn('appointments.service_type', ['lab', 'lab_bundle', 'lab_custom'])
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(appointments.appointment_date, "%Y-%m") as month,
                 DATE_FORMAT(appointments.appointment_date, "%Y-%m-%d") as date,
                 JSON_ARRAYAGG(JSON_OBJECT(
                    "booking_id", appointments.booking_id,
                    "service_type", appointments.service_type,
                    "session_type", appointments.session_type,
                    "bookable_id", appointments.bookable_id,
                    "bookable_type", appointments.bookable_type,
                    "start_time", appointments.appointment_start_time,
                    "end_time", appointments.appointment_end_time,
                    "service_name",
                        CASE
                            WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN doctors.first_name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                            ELSE NULL
                        END
                 )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => json_decode($appointment->appointments),
                ];
            });
    }

    public function getPhysicianAppointmentsGroupedByMonth()
    {
        return Appointment::where('appointments.provider', request()->user()->id)
            ->whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.appointment_status', 'upcoming')
            ->where('appointments.service_type', 'homecare')
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(appointments.appointment_date, "%Y-%m") as month,
                 DATE_FORMAT(appointments.appointment_date, "%Y-%m-%d") as date,
                 JSON_ARRAYAGG(JSON_OBJECT(
                    "booking_id", appointments.booking_id,
                    "service_type", appointments.service_type,
                    "session_type", appointments.session_type,
                    "bookable_id", appointments.bookable_id,
                    "bookable_type", appointments.bookable_type,
                    "start_time", appointments.appointment_start_time,
                    "end_time", appointments.appointment_end_time,
                    "service_name",
                        CASE
                            WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN doctors.first_name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                            ELSE NULL
                        END
                 )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => json_decode($appointment->appointments),
                ];
            });
    }

    public function getAdminCalender()
    {
        return Appointment::whereIn('appointments.status', ['scheduled', 'requested'])
            ->where('appointments.appointment_status', 'upcoming')
            ->leftJoin('users as doctors', function ($join) {
                $join->on('appointments.bookable_id', '=', 'doctors.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('service', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\Service');
            })
            ->leftJoin('service_bundles', function ($join) {
                $join->on('appointments.bookable_id', '=', 'service_bundles.id')
                    ->where('appointments.bookable_type', '=', 'App\\Models\\ServiceBundle');
            })
            ->selectRaw(
                'DATE_FORMAT(appointments.appointment_date, "%Y-%m") as month,
                 DATE_FORMAT(appointments.appointment_date, "%Y-%m-%d") as date,
                 JSON_ARRAYAGG(JSON_OBJECT(
                    "booking_id", appointments.booking_id,
                    "service_type", appointments.service_type,
                    "session_type", appointments.session_type,
                    "bookable_id", appointments.bookable_id,
                    "bookable_type", appointments.bookable_type,
                    "start_time", appointments.appointment_start_time,
                    "end_time", appointments.appointment_end_time,
                    "service_name",
                        CASE
                            WHEN appointments.bookable_type = "App\\\\Models\\\\User" THEN doctors.first_name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\Service" THEN service.name
                            WHEN appointments.bookable_type = "App\\\\Models\\\\ServiceBundle" THEN service_bundles.bundle_name
                            ELSE NULL
                        END
                 )) as appointments'
            )
            ->groupBy('month', 'date')
            ->orderBy('month', 'asc')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'month' => $appointment->month,
                    'date' => $appointment->date,
                    'appointments' => json_decode($appointment->appointments),
                ];
            });
    }


    public function reschedule($id, array $params)
    {
        \DB::beginTransaction();
        try {
            $user = request()->user();
            $slotId = $params['slot_id'];
            $appointmentDate = $params['appointment_date'];

            // Fetch slot & appointment
            $slot = $this->slot->findById($slotId);

            $appointment = $this->model->where('id', $id)->with([
                'bookable.file',
                'familyMember',
                'serviceProvider.file',
                'serviceProvider',
                'address',
                'bundleServices.service.file',
                'reviews',
                'user'
            ])->first();

            if (!$slot || !$appointment) {
                throw new \Exception('Invalid slot or appointment ID.');
            }

            // Check if appointment already rescheduled
            if (!is_null($appointment->request_date)) {
                throw new \Exception('This appointment has already been rescheduled once.');
            }

            // Check for slot conflicts with other appointments (both scheduled & requested)
            $conflictExists = $this->model
                ->where('user_id', $user->id)
                ->where(function ($query) use ($appointmentDate, $slot) {
                    $query->where(function ($q) use ($appointmentDate, $slot) {
                        $q->where('status', 'scheduled')
                            ->whereDate('appointment_date', $appointmentDate)
                            ->where('appointment_start_time', $slot->start_time);
                    })->orWhere(function ($q) use ($appointmentDate, $slot) {
                        $q->where('status', 'requested')
                            ->whereDate('request_date', $appointmentDate)
                            ->where('request_start_time', $slot->start_time);
                    });
                })
                ->exists();

            if ($conflictExists) {
                throw new \Exception('You have another booking on the same slot.');
            }

            // Update appointment
            $appointment->update([
                'slot_id' => $slotId,
                'status' => 'requested',
                'appointment_status' => 'upcoming',
                'request_start_time' => $slot->start_time,
                'request_end_time' => $slot->end_time,
                'request_date' => $appointmentDate,
            ]);

            \DB::commit();

            return [
                'message' => 'Appointment successfully rescheduled.',
                'appointment_id' => $appointment->id,
                'new_slot_time' => "{$slot->start_time} - {$slot->end_time}",
                'new_date' => $appointmentDate,
            ];
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }



    public function create(array $params)
    {
        \DB::beginTransaction();
        try {
            extract($params);
            $user = request()->user();
            $data = [];
            $paymentType = $params['payment_type'];

            if ($paymentType == 'insurance') {
                $insurance = Insurance::where('user_id', $user->id)->first();
                if ($insurance->status == 0) {
                    throw new \Exception('Insurance is not approved by client');
                }
            }

            $slots = $this->slot->findById($params['slot_id']);
            if ($params['service_type'] === 'doctor') {
                $doctor = User::find($params['doctor_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'session_type' => $params['session_type'],
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($doctor),
                    'bookable_id' => $doctor->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = 'doctor';
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;

                    // $this->sendUserNotification($appointment, 'doctor');
                } else {

                    $appointment->update([
                        'appointment_status' => 'upcoming',
                        'payment_status' => 'pending',
                        'status' => 'pending'
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => 'doctor',
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendUserNotification($appointment, 'doctor');
                }
            }

            if ($params['service_type'] === 'homecare') {
                $service = Service::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'session_type' => null,
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;

                    // $this->sendUserNotification($appointment, 'homecare');
                } else {

                    $appointment->update([
                        'payment_status' => 'pending',
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendUserNotification($appointment, 'homecare');
                }
            }

            if ($params['service_type'] === 'iv_drip') {
                $service = Service::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'session_type' => null,
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;
                    // $this->sendUserNotification($appointment, 'iv_drip');
                } else {

                    $appointment->update([
                        'payment_status' => 'pending',
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendNotification($appointment);
                }
            }

            if ($params['service_type'] === 'nursing_care') {
                $service = Service::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'session_type' => null,
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;
                    // $this->sendUserNotification($appointment, 'nursing_care');
                } else {

                    $appointment->update([
                        // 'payment_status' => 'paid',
                        'payment_status' => 'pending',

                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        // 'message' => 'Appointment scheduled sucessfully',
                        'message' => 'Waiting for Admin Approval',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendNotification($appointment);
                }
            }

            if ($params['service_type'] === 'lab') {
                $service = Service::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'session_type' => null,
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);



                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;
                    // $this->sendUserNotification($appointment, 'lab');
                } else {

                    $appointment->update([
                        'payment_status' => 'pending',
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendNotification($appointment);
                }
            }

            if ($params['service_type'] === 'lab_bundle' || $params['service_type'] === 'lab_custom') {
                $service = ServiceBundle::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;
                    // $this->sendUserNotification($appointment, 'lab_bundle');
                } else {

                    $appointment->update([
                        'payment_status' => 'pending',
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendNotification($appointment);
                }
            }

            if ($params['service_type'] === 'iv_drip_custom') {
                $service = ServiceBundle::find($params['service_id']);
                $charges = $params['appointment_charges'];
                $appointment_date = $params['appointment_date'];

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                // Create appointment record
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'family_member_id' => isset($params['family_member_id']) ? $params['family_member_id'] : null,
                    'address_id' => $params['address_id'],
                    'amount' => $charges,
                    'slot_id' => $params['slot_id'],
                    'service_type' => $params['service_type'],
                    'user_id' => $user->id,
                    'bookable_type' => get_class($service),
                    'bookable_id' => $service->id,
                    'status' => 'pending',
                    'appointment_status' => 'upcoming',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date,
                    'payment_type' => $paymentType,
                ]);

                if ($paymentType === 'card') {
                    // 🔹 Generate Stripe Payment Intent
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $charges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'booking_id' => (int) $appointment->booking_id,
                            'user_id' => $user->id,
                        ],
                    ]);

                    // 🔹 Include `client_secret` in response
                    $data['type'] = $params['service_type'];
                    $data['message'] = 'Please pay the charges';
                    $data['booking_id'] = (int) $appointment->booking_id;
                    $data['client_secret'] = $paymentIntent->client_secret;
                    // $this->sendUserNotification($appointment, 'iv_drip_custom');
                } else {

                    $appointment->update([
                        'payment_status' => 'pending',
                    ]);

                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => 0, // Convert cents to dollars
                        'status' => 'pending',
                        'transaction_id' => $insurance->id,
                        'customer_payment_data' => null,
                        'payment_method' => 'insurance-card', // Store payment method type

                    ]);

                    $appointment->load(['user']); // e.g., load('user', 'bookable') if needed

                    // ✅ Response
                    $data = [
                        'type' => $params['service_type'],
                        'message' => 'Appointment scheduled sucessfully',
                        'booking_id' => (int) $appointment->booking_id,
                        'payment-type' => 'insurance',
                    ] + $appointment->toArray();

                    // $this->sendNotification($appointment);
                }
            }

            if ($params['service_type'] === 'custom') {

                $bundle = ServiceBundle::findOrFail($params['service_id']);
                // Fetch all services inside this bundle
                $bundleServices = BundleService::where('bundle_id', $bundle->id)->get(['service_id', 'type']);

                $services = collect();

                foreach ($bundleServices as $bundleService) {
                    if ($bundleService->type === 'lab_bundle') {
                        // If the service itself is another bundle, expand it
                        $nestedServices = ServiceBundle::where('id', $bundleService->service_id)->get();
                        $services = $services->merge($nestedServices);
                    } else {
                        // Otherwise it's a normal service
                        $nestedServices = Service::where('id', $bundleService->service_id)->get();
                        $services = $services->merge($nestedServices);
                    }
                }

                if ($services->isEmpty()) {
                    throw new \Exception('No services found in this bundle.');
                }

                // 🔹 Total charges = sum of service prices
                $totalCharges = $services->sum('price');

                $appointment_date = $params['appointment_date'];
                $bookingId = generateTicketID(2, length: 6);

                // Check for existing bookings at the same slot
                $check_old = $this->model->where('user_id', $user->id)->where('status', 'scheduled')->get();
                foreach ($check_old as $same_slot) {
                    $date1 = Carbon::createFromFormat('Y-m-d', $same_slot->appointment_date);
                    $date2 = Carbon::createFromFormat('Y-m-d', $appointment_date);
                    if ($slots->start_time == $same_slot->appointment_start_time && $date1->eq($date2)) {
                        throw new \Exception('You have another booking on the same slot');
                    }
                }

                $appointments = [];

                // 🔹 Create one Stripe PaymentIntent if payment type is card
                $paymentIntent = null;
                if ($paymentType === 'card') {
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $totalCharges * 100, // Convert to cents
                        'currency' => 'aed',
                        'payment_method_types' => ['card'],
                        'metadata' => [
                            'booking_id' => (int) $bookingId,
                            'user_id' => $user->id,
                        ],
                    ]);
                }

                // 🔹 Create appointment per service in bundle
                foreach ($services as $service) {

                    $service_type = $service->type;
                    $appointment = $this->model->create([
                        'booking_id'            => $bookingId,
                        'family_member_id'      => $params['family_member_id'] ?? null,
                        'address_id'            => $params['address_id'],
                        'amount'                => $service->price, // individual service price
                        'slot_id'               => $params['slot_id'],
                        'service_type'          => $service_type,
                        'user_id'               => $user->id,
                        'bookable_type'         => get_class($service),
                        'bookable_id'           => $service->id,
                        'status'                => 'pending',
                        'appointment_status'    => 'upcoming',
                        'appointment_start_time' => $slots->start_time,
                        'appointment_end_time'  => $slots->end_time,
                        'appointment_date'      => $appointment_date,
                        'payment_type'          => $paymentType,
                        'is_custom'          => 1,
                    ]);

                    $appointments[] = $appointment;

                    // If insurance, log payment as pending
                    if ($paymentType !== 'card') {
                        $appointment->update(['payment_status' => 'pending']);

                        $this->payment->create([
                            'payer_id'             => $user->id,
                            'payer_type'           => get_class($user),
                            'payable_type'         => get_class($appointment),
                            'payable_id'           => $appointment->id,
                            'amount'               => 0,
                            'status'               => 'pending',
                            'transaction_id'       => $insurance->id ?? null,
                            'customer_payment_data' => null,
                            'payment_method'       => 'insurance-card',
                        ]);

                        // $this->sendNotification($appointment);
                    }
                }

                // ✅ Response
                if ($paymentType === 'card') {
                    $data = [
                        'type'          => $params['service_type'],
                        'message'       => 'Please pay the charges',
                        'booking_id'    => (int) $bookingId,
                        'client_secret' => $paymentIntent->client_secret,
                        'total_amount'  => $totalCharges,
                        'appointments'  => $appointments,
                    ];
                    // $this->sendUserNotification($appointment, 'custom');
                } else {
                    $data = [
                        'type'         => $params['service_type'],
                        'message'      => 'Appointments scheduled successfully',
                        'booking_id'   => (int) $bookingId,
                        'payment_type' => 'insurance',
                        'total_amount' => $totalCharges,
                        'appointments' => $appointments,
                    ];
                }
            }

            \DB::commit();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function payment(array $params)
    {
        try {
            $user = request()->user();
            \DB::beginTransaction();

            Stripe::setApiKey(config('services.stripe.secret'));

            // Retrieve the payment intent
            $paymentIntent = PaymentIntent::retrieve($params['payment_intent_id']);

            // Ensure the payment method is passed in the request
            if (!isset($params['payment_method_id'])) {
                throw new \Exception("Payment method is required.");
            }

            // Attach the correct payment method dynamically
            if ($paymentIntent->status === 'requires_payment_method') {
                $paymentIntent = PaymentIntent::update($params['payment_intent_id'], [
                    'payment_method' => $params['payment_method_id'], // Accepts Card, Apple Pay, Google Pay
                ]);
            }

            // Confirm payment if required
            if ($paymentIntent->status === 'requires_confirmation') {
                $paymentIntent->confirm();
            }

            if ($paymentIntent->status === 'succeeded') {
                // $appointment = $this->model->where('id', $paymentIntent->metadata->appointment_id)->first();
                $appointmentId = $paymentIntent->metadata->appointment_id ?? null;
                $bookingId     = $paymentIntent->metadata->booking_id ?? null;

                $appointments = collect();

                if ($appointmentId) {
                    $appointments = $this->model->where('id', $appointmentId)->get(); // will return 0 or 1 row in a collection
                } elseif ($bookingId) {
                    $appointments = $this->model->where('booking_id', $bookingId)->get(); // may return multiple appointments
                }

                // Now $appointments is always a collection
                foreach ($appointments as $appointment) {

                    if ($appointment->service_type === 'doctor') {
                        $appointment->update([
                            'appointment_status' => 'upcoming',
                            'payment_status' => 'paid',
                            'status' => 'scheduled'
                        ]);
                    } else {

                        $appointment->update([
                            'appointment_status' => 'upcoming',
                            'payment_status'     => 'paid',
                            'status'             => 'pending',
                        ]);
                    }

                    $this->payment->create([
                        'payer_id'             => $user->id,
                        'payer_type'           => get_class($user),
                        'payable_type'         => get_class($appointment),
                        'payable_id'           => $appointment->id,
                        'amount'               => $appointment->is_custom === 1 ? $appointment->amount : $paymentIntent->amount / 100,
                        'status'               => 'paid',
                        'transaction_id'       => $paymentIntent->id ?? null,
                        'customer_payment_data' => json_encode($paymentIntent),
                        'payment_method'       => $paymentIntent->payment_method_types[0] ?? 'unknown',
                    ]);

                    Cart::where('user_id', $user->id)->delete();

                    $this->sendPaymentNotification($appointment, $appointment->service_type);
                }

                \DB::commit();
                return [
                    'booking_id' => $appointment->booking_id ?? null,
                    'transaction_id' => $paymentIntent->id ?? null,
                    'amount' => $paymentIntent->amount / 100,
                    'status' => $paymentIntent->status,
                ];
            }

            \DB::rollBack();
            throw new \Exception('Payment failed or pending.');
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendCancelNotification($appointment, $owner = null)
    {
        try {
            $user    = $appointment->user;       // Patient
            $doctor  = $appointment->bookable;   // Doctor

            // Names
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else if ($appointment->service_type === 'lab_bundle') {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            $commonData = [
                'id'   => $appointment->id,
                'type' => $appointment->service_type,
            ];

            if ($owner) {
                // Admin + patient get notified who cancelled
                $payload = [
                    'title' => 'Booking Cancelled',
                    'body'  => "{$designation} {$doctorName} has cancelled an appointment with patient {$patientName}",
                    'data'  => array_merge($commonData, ['status' => 'cancelled']),
                ];
            } else {
                // Admin + providers get notified who cancelled
                $payload = [
                    'title' => 'Booking Cancelled',
                    'body'  => "{$patientName} has cancelled an appointment with {$designation} {$doctorName}",
                    'data'  => array_merge($commonData, ['status' => 'cancelled']),
                ];
            }



            // Notify admin
            if ($admin = Admin::first()) {
                $admin->notify(new DatabaseNotification($payload));
            }

            // Push notification to user - booking cancelled
            $this->notification()->send(
                $user,
                title: 'Booking Cancelled',
                body: "Dear {$patientName}, your appointment with {$designation} {$doctorName} has cancelled at {$appointment->appointment_start_time}.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'cancelled',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );

            // Push notification to doctor - booking cancelled
            $this->notification()->send(
                $doctor,
                title: 'Booking Cancelled',
                body: "Dear {$doctorName}, your appointment with {$patientName} has cancelled at {$appointment->appointment_start_time}.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'cancelled',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendOnTheWayNotification($appointment)
    {
        try {
            $user    = $appointment->user;       // Patient
            $doctor  = $appointment->bookable;   // Doctor

            // Patient Name (family member or patient)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Provider Name + Designation
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            $commonData = [
                'id'   => $appointment->id,
                'type' => $appointment->service_type,
            ];

            // Admin + providers get notified that doctor is on the way
            $payload = [
                'title' => "{$doctorName} {$designation} On The Way",
                'body'  => "{$doctorName} {$designation} is on the way to the appointment with {$patientName}",
                'data'  => array_merge($commonData, ['status' => 'ontheway']),
            ];

            // Notify admin
            if ($admin = Admin::first()) {
                $admin->notify(new DatabaseNotification($payload));
            }

            // Push notification to patient
            $this->notification()->send(
                $user,
                title: "Your {$doctorName} {$designation} is On the Way",
                body: "Dear {$patientName}, {$doctorName} {$designation} is on the way for your appointment scheduled at {$appointment->appointment_start_time}.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'ontheway',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );

            if (!empty($appointment->serviceProvider)) {
                $provider = $appointment->serviceProvider;

                $this->notification()->send(
                    $provider,
                    title: "You are On the Way",
                    body: "Dear {$doctorName}, you are marked as 'On the Way' for your appointment with {$patientName} at {$appointment->appointment_start_time}.",
                    sound: 'customSound',
                    id: $appointment->id,
                    data: [
                        'id'     => $appointment->id,
                        'type'   => $appointment->service_type,
                        'status' => 'ontheway',
                        'route'  => json_encode([
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ]),
                    ]
                );
            }
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendCompletedNotification($appointment)
    {
        try {
            $user   = $appointment->user;      // Patient
            $doctor = $appointment->bookable;  // Doctor

            // Patient name (prefer family_member)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            $this->notification()->send(
                $user,
                title: 'Session Completed',
                body: "Dear {$patientName}, your session with {$doctorName} {$designation} has successfully ended.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'ended',
                    'sound'  => 'customSound',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function sendStartNotification($appointment, array $notificationPayload, string $type)
    {
        try {
            $user   = $appointment->user;      // Patient
            $doctor = $appointment->bookable;  // Doctor

            // Patient name (prefer family_member name)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            // Merge payload with base notification
            $data = array_merge([
                'id'    => $appointment->id,
                'type'  => $type,
                'sound' => 'customSound',
                'route' => json_encode([
                    'name'   => 'appointment.details',
                    'params' => ['id' => $appointment->id],
                ]),
            ], $notificationPayload);

            // Push notification to patient
            $this->notification()->send(
                $user,
                title: 'Session Started',
                body: "Dear {$patientName}, your session with {$doctorName} {$designation} has started.",
                sound: 'customSound',
                id: $appointment->id,
                data: $data
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendUserNotification($appointment, $type)
    {
        try {

            $user   = $appointment->user;      // Patient
            $doctor = $appointment->bookable;  // Doctor

            // Patient name (prefer family_member if available)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
            }

            if ($type == 'doctor') {
                // Push notification to patient - booking scheduled
                $this->notification()->send(
                    $user,
                    title: 'New Booking',
                    body: "Patient {$patientName}, your appointment with Dr. {$doctorName} has been booked successfully!",
                    sound: 'customSound',
                    id: $appointment->id,
                    data: [
                        'id'     => $appointment->id,
                        'type'   => $appointment->service_type,
                        'status' => 'scheduled',
                        'sound'  => 'customSound',
                        'route'  => json_encode([
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ]),
                    ]
                );

                // Push notification to patient - payment successful
                $commonData = [
                    'id'   => $appointment->id,
                    'type' => $appointment->service_type,
                ];

                $body = "Patient {$patientName}, has scheduled an appointment with Dr.{$doctorName}.";

                // Prepare payload for admin and provider (doctor)
                $payload = [
                    'title' => 'New Booking',
                    'body'  => $body,
                    'data'  => array_merge($commonData, ['status' => 'scheduled'])
                ];

                // Notify admin
                if ($admin = Admin::first()) {
                    $admin->notify(new DatabaseNotification($payload));
                }

                $doctor = User::find($appointment->bookable_id);
                $doctor->notify(new DatabaseNotification($payload));
            } else {

                // Push notification to patient - payment successful
                $commonData = [
                    'id'   => $appointment->id,
                    'type' => $appointment->service_type,
                ];

                $body = "Patient {$patientName}, has scheduled an appointment with Healthcare Care Professional. {$doctorName}.";

                // Prepare payload for admin and provider (doctor)
                $payload = [
                    'title' => 'New Booking',
                    'body'  => $body,
                    'data'  => array_merge($commonData, ['status' => 'scheduled'])
                ];

                // Notify admin
                if ($admin = Admin::first()) {
                    $admin->notify(new DatabaseNotification($payload));
                }
            }
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendServiceProviderCancelNotification($appointment, $owner = null)
    {
        try {
            $user    = $appointment->user;       // Patient
            $doctor  = $appointment->bookable;   // Doctor

            // Names
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else if ($appointment->service_type === 'lab_bundle') {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            $commonData = [
                'id'   => $appointment->id,
                'type' => $appointment->service_type,
            ];

            if ($owner) {
                // Admin + patient get notified who cancelled
                $payload = [
                    'title' => 'Booking Cancelled',
                    'body'  => "Health Care Professional {$doctorName} has cancelled an appointment with Patient {$patientName}",
                    'data'  => array_merge($commonData, ['status' => 'cancelled']),
                ];
            } else {
                // Admin + providers get notified who cancelled
                $payload = [
                    'title' => 'Booking Cancelled',
                    'body'  => "Patient {$patientName} has cancelled an appointment with {$designation} {$doctorName}",
                    'data'  => array_merge($commonData, ['status' => 'cancelled']),
                ];
            }



            // Notify admin
            if ($admin = Admin::first()) {
                $admin->notify(new DatabaseNotification($payload));
            }

            // Push notification to user - booking cancelled
            $this->notification()->send(
                $user,
                title: 'Booking Cancelled',
                body: "Dear Patient {$patientName}, your appointment with Health Care Professional {$doctorName} has cancelled at {$appointment->appointment_start_time}.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'cancelled',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendPaymentNotification($appointment, $type)
    {
        try {
            $user   = $appointment->user;      // Patient
            $doctor = $appointment->bookable;  // Doctor

            // Patient name (prefer family_member if available)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            if ($type == 'doctor') {
                // Push notification to patient - booking scheduled

                $this->notification()->send(
                    $user,
                    title: 'Booking Payment',
                    body: "Patient {$patientName}, your payment for the appointment with Dr. {$doctorName} has been received successfully.",
                    sound: 'customSound',
                    id: $appointment->id,
                    data: [
                        'id'     => $appointment->id,
                        'type'   => $appointment->service_type,
                        'status' => 'paid',
                        'sound'  => 'customSound',
                        'route'  => json_encode([
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ]),
                    ]
                );
            } else {
                // Push notification to patient - booking scheduled

                $this->notification()->send(
                    $user,
                    title: 'Booking Payment',
                    body: "Patient {$patientName}, your payment for the appointment with Healthcare Care Professional. {$doctorName} has been received successfully.",
                    sound: 'customSound',
                    id: $appointment->id,
                    data: [
                        'id'     => $appointment->id,
                        'type'   => $appointment->service_type,
                        'status' => 'paid',
                        'sound'  => 'customSound',
                        'route'  => json_encode([
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ]),
                    ]
                );
            }
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function sendNotification($appointment)
    {
        try {
            $user   = $appointment->user;      // Patient
            $doctor = $appointment->bookable;  // Doctor

            // Patient name (prefer family_member if available)
            $patientName = $appointment->family_member?->name
                ?? trim("{$user->first_name} {$user->last_name}");

            // Doctor details
            if (!empty($appointment->serviceProvider)) {
                $doctorName  = trim(($appointment->serviceProvider->first_name ?? '') . ' ' . ($appointment->serviceProvider->last_name ?? ''));
                $designation = ucfirst($appointment->serviceProvider->role ?? '');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? '');
            }

            // Push notification to patient - booking scheduled
            $this->notification()->send(
                $user,
                title: 'New Booking',
                body: "Dear {$patientName}, your appointment with {$designation} {$doctorName} has been booked successfully!",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'scheduled',
                    'sound'  => 'customSound',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );

            // Push notification to patient - payment successful
            $this->notification()->send(
                $user,
                title: 'Booking Payment',
                body: "Dear {$patientName}, your payment for the appointment with {$designation} {$doctorName} has been received successfully.",
                sound: 'customSound',
                id: $appointment->id,
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'paid',
                    'sound'  => 'customSound',
                    'route'  => json_encode([
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ]),
                ]
            );

            $commonData = [
                'id'   => $appointment->id,
                'type' => $appointment->service_type,
            ];

            $providerText = '';
            if (!empty($doctorName) || !empty($designation)) {
                $providerText = " with {$designation} {$doctorName}";
            }

            $body = "Dear {$patientName}, has scheduled an appointment {$providerText}.";

            // Prepare payload for admin and provider (doctor)
            $payload = [
                'title' => 'New Booking',
                'body'  => $body,
                'data'  => array_merge($commonData, ['status' => 'scheduled'])
            ];

            // Notify admin
            if ($admin = Admin::first()) {
                $admin->notify(new DatabaseNotification($payload));
            }

            // Notify provider (Doctor or Homecare/Lab)
            $provider = null;
            if ($appointment->service_type === 'doctor') {
                $provider = User::find($appointment->bookable_id);
            } elseif (in_array($appointment->service_type, ['homecare', 'lab', 'lab_custom', 'lab_bundle'])) {
                $provider = User::find($appointment->provider);
            }

            if ($provider) {
                $provider->notify(new DatabaseNotification($payload));
            }
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }



    public function createAppointment(array $params)
    {
        \DB::beginTransaction();
        extract($params);
        $user = request()->user();
        $slots = Slot::find($params['slot_id']);
        $mentor = Psychologist::with('rate')->where('id', $slots->slotable_id)->first();
        $commission = Commissions::where('user_type', 'psychologist')->where('rate_type', 'commission')->latest()->first();
        $commission = $commission->rate / 100;
        $commission = $commission * ($mentor->rate->rate ?? 0);
        try {

            $data = array();
            $weekdayNames = [];
            $today = Carbon::today();
            $appointment_date = '0000-00-00';

            for ($i = 1; $i <= 7; $i++) {
                $date = $today->copy()->addDays($i);
                $today->copy()->addDays($i)->toDateString();
                $weekdayNames[] = $date->weekday();
                if ($slots->day == $date->weekday()) {
                    $appointment_date = $today->copy()->addDays($i)->toDateString();
                }
            }

            if ($mentor) {
                $appointment = $this->model->create([
                    'booking_id' => generateTicketID(2, length: 6),
                    'visit_charges' => 0,
                    'type' => 'online',
                    'charges' => $mentor->rate->rate - $commission,
                    'admin_commission' => $commission,
                    'payout_status' => 'unpaid',
                    'payout_date' => null,
                    'slot_id' => $params['slot_id'],
                    'userable_type' => get_class($user),
                    'userable_id' => $user->id,
                    'bookable_type' => get_class($mentor),
                    'bookable_id' => $mentor->id,
                    'status' => 'unpaid',
                    'appointment_status' => 'approved',
                    'appointment_start_time' => $slots->start_time,
                    'appointment_end_time' => $slots->end_time,
                    'appointment_date' => $appointment_date
                ]);

                $data['type'] = 'psychologist';
                $data['message'] = 'please pay the charges';
                $data['booking_id'] = $appointment->booking_id;
            }

            \DB::commit();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }

    public function sessionStatus($appointmentId, $status, $session_code = null)
    {
        try {
            $user = request()->user();
            $type = get_class($user);
            $currentDate = now()->format('Y-m-d');
            $currentTime = now()->format('H:i');

            $appointmentId = request('appointment_id');
            $appointment = $this->model->find($appointmentId);

            if (!$appointment) {
                return $message = response()->json(api_error('Appointment does not exist'));
            }

            $isAppointmentValid = (
                $appointment->appointment_status === 'approved' &&
                $appointment->appointment_date === $currentDate &&
                $currentTime >= $appointment->appointment_start_time &&
                $currentTime <= $appointment->appointment_end_time
            );

            if (!$isAppointmentValid) {
                if ($appointment->appointment_status !== 'approved') {
                    return $message = response()->json(api_error('Your appointment is not approved'));
                } else {
                    return $message = response()->json(api_error('Your session is not started'));
                }
            }

            $is_coach = 0;
            if ($appointment->bookable_id === $user->id && $appointment->bookable_type === $type) {
                $is_coach = 1;
            }


            if (request('status') == 1) {
                $existingSession = AppointmentSession::where('appointment_id', $appointmentId)->where('status', 1)->first();
                if ($existingSession) {
                    return $message = response()->json(api_error('Your session is already started'));
                } else {
                    if ($session_code) {
                        //for live go
                        $appointment->is_live = 1;
                        $appointment->session_code = $session_code;
                        $appointment->save();
                    }
                    AppointmentSession::create([
                        'appointment_id' => $appointmentId,
                        'userable_id' => $user->id,
                        'userable_type' => $type,
                        'status' => request('status'),
                        'is_coach' => $is_coach,
                    ]);
                    return $message = response()->json(api_error('Session started successfully'));
                }
            }
            if (request('status') == 0) {
                $startedSession = AppointmentSession::where('appointment_id', $appointmentId)->where('status', 0)->first();
                if ($startedSession) {
                    return $message = response()->json(api_error('Your session is already ended'));
                } else {
                    $appointment->is_live = 2;
                    $appointment->save();
                    AppointmentSession::where('appointment_id', $appointmentId)
                        ->update([
                            'status' => 0,
                        ]);

                    return $message = response()->json(api_error('Session ended successfully'));
                }
            }




            $message = (request('status') == 1) ? 'Session started' : 'Session ended';

            \DB::commit();
            return $message;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function getTotal(Filters|null $filter = null)
    {
        try {
            return $this->model->filter($filter)->sum('charges');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function getAdminTotal(Filters|null $filter = null)
    {
        try {
            return $this->model->filter($filter)->sum('admin_commission');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalAppointments(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalAppointments = $this->model->filter($filter)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();
            $currentMonthAppointments = $this->model->whereMonth('created_at', $currentMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();
            $previousMonthAppointments = $this->model->whereMonth('created_at', $previousMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthAppointments > $previousMonthAppointments;
            $difference = abs($currentMonthAppointments - $previousMonthAppointments); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthAppointments > 0
                ? (($difference / $previousMonthAppointments) * 100)
                : ($currentMonthAppointments > 0 ? 100 : 0); // If no appointments in previous month, set to 100% if new appointments exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalAppointments,
                'increase' => $increase, // True if current month appointments are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalAppointmentsUsers(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalAppointments = $this->model->filter($filter)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();
            $currentMonthAppointments = $this->model->whereMonth('created_at', $currentMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();
            $previousMonthAppointments = $this->model->whereMonth('created_at', $previousMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'completed')->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthAppointments > $previousMonthAppointments;
            $difference = abs($currentMonthAppointments - $previousMonthAppointments); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthAppointments > 0
                ? (($difference / $previousMonthAppointments) * 100)
                : ($currentMonthAppointments > 0 ? 100 : 0); // If no appointments in previous month, set to 100% if new appointments exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalAppointments,
                'increase' => $increase, // True if current month appointments are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function newAppointmentsUsers(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $previousMonth = Carbon::now()->subMonth()->month;

            $totalAppointments = $this->model->filter($filter)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'upcoming')->count();
            $currentMonthAppointments = $this->model->whereMonth('created_at', $currentMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'upcoming')->count();
            $previousMonthAppointments = $this->model->whereMonth('created_at', $previousMonth)->whereIn('status', ['scheduled', 'requested'])->where('appointment_status', 'upcoming')->count();

            // Determine if there is an increase or decrease
            $increase = $currentMonthAppointments > $previousMonthAppointments;
            $difference = abs($currentMonthAppointments - $previousMonthAppointments); // Absolute difference

            // Calculate percentage change
            $percentageChange = $previousMonthAppointments > 0
                ? (($difference / $previousMonthAppointments) * 100)
                : ($currentMonthAppointments > 0 ? 100 : 0); // If no appointments in previous month, set to 100% if new appointments exist

            // Format difference as increase or decrease
            $differenceText = $increase
                ? round($percentageChange, 2)
                : round($percentageChange, 2);

            return [
                'total' => $totalAppointments,
                'increase' => $increase, // True if current month appointments are more than last month
                'difference' => $differenceText, // Dynamic increase or decrease message
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTotalCount(Filters|null $filter = null)
    {
        try {
            $currentMonth = Carbon::now()->month;

            $totalPlayers = $this->model->filter($filter)->count();
            $currentMonthPlayers = $this->model->whereMonth('created_at', $currentMonth)->count();
            if ($totalPlayers > 0) {
                $percentageCurrentMonth = ($currentMonthPlayers / $totalPlayers) * 100;
            } else {
                // Handle the case where there are no players to avoid division by zero
                $percentageCurrentMonth = 0;
            }

            return  ['total' => $totalPlayers, 'trend' => $percentageCurrentMonth];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTotalEarnings(Filters|null $filter = null)
    {
        try {
            $amount  = $this->model->filter($filter)->sum('amount');
            $commission  = $this->model->filter($filter)->sum('commission');
            return $amount - $commission;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
