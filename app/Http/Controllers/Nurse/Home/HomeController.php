<?php

namespace App\Http\Controllers\Nurse\Home;

use App\Models\User;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Filters\Nurse\HomeFilters;
use App\Http\Controllers\Controller;
use App\Core\Generators\GraphGenerator;
use App\Repositories\User\UserRepository;
use App\Repositories\Branch\BranchRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Appointment\AppointmentRepository;

class HomeController extends Controller
{
    private PaymentRepository $payment;
    private UserRepository $user;
    private BranchRepository $branch;
    private AppointmentRepository $appointment;


    public function __construct(
        PaymentRepository $paymentRepo,
        Payment $payment,
        UserRepository $userRepo,
        User $user,
        BranchRepository $branchRepo,
        Branch $branch,
        AppointmentRepository $appointmenthRepo,
        Appointment $appointment
    ) {
        $this->payment = $paymentRepo;
        $this->user = $userRepo;
        $this->branch = $branchRepo;
        $this->appointment = $appointmenthRepo;

        $this->branch->setModel($branch);
        $this->user->setModel($user);
        $this->payment->setModel($payment);
        $this->appointment->setModel($appointment);
    }

    public function index(Request $request, HomeFilters $filter)
    {
        try {

            $filter->extendRequest([
                'service_type' => 1,
                'personal' => request()->user()->id
            ]);

            $totalAppointments = $this->appointment->totalAppointmentsUsers($filter);
            $newAppointments = $this->appointment->newAppointmentsUsers($filter);

            // Store all objects in an indexed array
            $data = [
                [
                    "total" => $totalAppointments['total'],
                    "increase" => $totalAppointments['increase'],
                    "difference" => $totalAppointments['difference'],
                    "text" => "Total Appointments",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-1.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ],
                [
                    "total" => $newAppointments['total'],
                    "increase" => $newAppointments['increase'],
                    "difference" => $newAppointments['difference'],
                    "text" => "New Appointments",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-1.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ],
            ];

            $data = api_successWithData('home data', $data);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function chart($type, Request $request, HomeFilters $filter)
    {
        try {

            $filter->extendRequest([
                $type => 1,
                'service_type' => 1,
                'personal' => request()->user()->id,
                'chart_status' => 1
            ]);

            $data = $this->{$type}->findAll($filter);
            $data = (new GraphGenerator($type))->data($data)->get();
            $data = api_successWithData('Nurse Appointment data', $data);
            return response()->json($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
