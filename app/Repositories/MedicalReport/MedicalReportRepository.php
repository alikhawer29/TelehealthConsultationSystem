<?php

namespace App\Repositories\MedicalReport;

use Carbon\Carbon;
use Stripe\Stripe;
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
use App\Models\Psychologist;
use App\Models\ServiceBundle;
use App\Core\Abstracts\Filters;
use App\Models\AppointmentSession;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Slot\SlotRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Core\Notifications\DatabaseNotification;
use App\Core\Abstracts\Repository\BaseRepository;

class MedicalReportRepository extends BaseRepository implements MedicalReportRepositoryContract
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



    // Physician and nurse both
    public function assignPhysician(array $params): ?Appointment
    {
        \DB::beginTransaction();
        try {
            $user = request()->user();
            $appointmentId = $params['appointment_id'];
            $physicianId   = $params['physician_id'];

            // Fetch appointment with lock
            $appointment = $this->model
                ->lockForUpdate()
                ->findOrFail($appointmentId);

            // Check for conflict
            $conflictExists = $this->model
                ->where('id', $appointmentId)
                ->whereNotNull('provider')
                ->exists();

            if ($conflictExists) {
                throw new \Exception('Appointment already assigned.');
            }

            // Update appointment
            $appointment->update([
                'provider' => $physicianId,
                'status'   => 'scheduled',
            ]);

            // Notify patient
            $this->sendNotification($appointment);

            // Prepare patient name
            $patientName = $appointment->family_member->name
                ?? trim(($appointment->user->first_name ?? '') . ' ' . ($appointment->user->last_name ?? ''));

            // Notify physician
            if ($physician = User::find($physicianId)) {
                $physician->notify(new \App\Notifications\GenericNotification([
                    'title' => 'New Appointment Assigned',
                    'body'  => "You have been assigned to a new appointment with {$patientName}.",
                    'sound' => 'customSound',
                    'id'    => $appointment->id,
                    'data'  => [
                        'route' => [
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ],
                    ],
                ]));
            }

            \DB::commit();
            return $appointment->fresh();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }



    //user
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
            $appointment = $this->model->find($id);

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
                $appointment = $this->model->where('id', $paymentIntent->metadata->appointment_id)->first();

                if ($appointment) {

                    if ($appointment->service_type === 'doctor') {
                        $appointment->update([
                            'appointment_status' => 'upcoming',
                            'payment_status' => 'paid',
                            'status' => 'scheduled'
                        ]);
                    } else {
                        $appointment->update([
                            'appointment_status' => 'upcoming',
                            'payment_status' => 'paid',
                            'status' => 'pending'
                        ]);
                    }


                    // Store payment log
                    $this->payment->create([
                        'payer_id' => $user->id,
                        'payer_type' => get_class($user),
                        'payable_type' => get_class($appointment),
                        'payable_id' => $appointment->id,
                        'amount' => $paymentIntent->amount / 100, // Convert cents to dollars
                        'status' => 'paid',
                        'transaction_id' => $paymentIntent->id ?? null,
                        'customer_payment_data' => json_encode($paymentIntent),
                        'payment_method' => $paymentIntent->payment_method_types[0] ?? 'unknown', // Store payment method type

                    ]);

                    $this->sendNotification($appointment);
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

    public function sendCancelNotification($appointment)
    {
        try {
            $user = $appointment->user;

            // Prefer service_provider if exists, otherwise fallback to bookable
            if (!empty($appointment->service_provider)) {
                $doctorName  = trim(($appointment->service_provider->first_name ?? '') . ' ' . ($appointment->service_provider->last_name ?? ''));
                $designation = ucfirst($appointment->service_provider->role ?? 'Doctor');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? 'Doctor');
            }

            // Push notification to user - booking cancelled
            $this->notification()->send(
                $user,
                title: 'Booking Cancelled',
                body: "Your booking with {$doctorName} {$designation} has been cancelled.",
                data: [
                    'id'     => $appointment->id,
                    'type'   => $appointment->service_type,
                    'status' => 'cancelled',
                    'sound'  => 'customSound',
                    'route'  => [
                        'name'   => 'appointment.details',
                        'params' => ['id' => $appointment->id],
                    ],
                ]
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function sendNotification($appointment)
    {
        try {
            $user = $appointment->user;

            // Prefer service_provider if exists, otherwise fallback to bookable
            if (!empty($appointment->service_provider)) {
                $doctorName  = trim(($appointment->service_provider->first_name ?? '') . ' ' . ($appointment->service_provider->last_name ?? ''));
                $designation = ucfirst($appointment->service_provider->role ?? 'Doctor');
            } else {
                $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                $designation = ucfirst($appointment->bookable->role ?? 'Doctor');
            }

            // Common notification data
            $commonData = [
                'id'    => $appointment->id,
                'type'  => $appointment->service_type,
                'sound' => 'customSound',
                'route' => [
                    'name'   => 'appointment.details',
                    'params' => ['id' => $appointment->id],
                ],
            ];

            // --- Notify Patient ---
            $this->notification()->send(
                $user,
                title: 'New Booking',
                body: "Your booking with  {$designation} {$doctorName} has been scheduled",
                data: array_merge($commonData, ['status' => 'scheduled'])
            );

            $this->notification()->send(
                $user,
                title: 'Booking Payment',
                body: "Your payment for booking with  {$designation} {$doctorName} has been received",
                data: array_merge($commonData, ['status' => 'paid'])
            );

            $providerText = '';
            if (!empty($doctorName) || !empty($designation)) {
                $providerText = " with {$designation} {$doctorName}";
            }

            $body = "Dear {$user->first_name}, has scheduled an appointment {$providerText}.";

            // --- Prepare payload for admin & provider ---
            $payload = [
                'title' => 'New Booking',
                'body'  => $body,
                'data'  => array_merge($commonData, ['status' => 'scheduled']),
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



    // if ($appointment->service_type == 'homecare') {
    //     $healthcare_id = $appointment->provider;
    // }
    // if ($appointment->service_type == 'lab' || $appointment->service_type == 'lab_custom' || $appointment->service_type == 'lab_bundle') {
    //     $healthcare_id = $appointment->bookable_id;
    // }



    public function coachCharges($package, $check)
    {
        $visit_charges = Commissions::where('rate_type', 'visit-charges')->first();
        $admin_commission = Commissions::where('user_type', 'coach')->where('rate_type', 'commission')->latest()->first();
        $visit_charges = $check == true ? $visit_charges->rate : 0;
        $total = $package->cost / $package->sessions +  $visit_charges;
        $admin_commission = $admin_commission->rate / 100;
        $admin_payout = round($total * $admin_commission, 1);
        $coach_payout = round($total - $admin_payout, 1);
        return [
            'admin_payout' => $admin_payout,
            'coach_payout' => $coach_payout,
            'visit_charges' => $visit_charges
        ];
    }

    public function psychologistCharges($mentor, $commission)
    {
        $commission = Commissions::where('user_type', 'psychologist')->where('rate_type', $commission)->latest()->first();
        $commission = $commission->rate / 100;
        return $commission = $commission * ($mentor->rate->rate ?? 0);
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
