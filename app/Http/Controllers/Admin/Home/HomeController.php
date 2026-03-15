<?php

namespace App\Http\Controllers\Admin\Home;

use App\Models\User;
use App\Models\Branch;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Filters\Admin\HomeFilters;
use App\Http\Controllers\Controller;
use App\Core\Generators\GraphGenerator;
use App\Models\Appointment;
use App\Repositories\Appointment\AppointmentRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Branch\BranchRepository;
use App\Repositories\Payment\PaymentRepository;

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
            $users = $this->user->totalUsers($filter);
            $consultants = $this->user->totalConsultants($filter);
            $nurses = $this->user->totalNurses($filter);
            $physicians = $this->user->totalPhysicians($filter);
            $appointments = $this->appointment->totalAppointments($filter);

            // Store all objects in an indexed array
            $data = [
                [
                    "total" => $users['total'],
                    "increase" => $users['increase'],
                    "difference" => $users['difference'],
                    "text" => "Total Users",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-1.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ],
                [
                    "total" => $consultants['total'],
                    "increase" => $consultants['increase'],
                    "difference" => $consultants['difference'],
                    "text" => "Total Consultants",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-2.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-2.png"
                ],
                [
                    "total" => $nurses['total'],
                    "increase" => $nurses['increase'],
                    "difference" => $nurses['difference'],
                    "text" => "Total Nurses",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-1.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ],
                [
                    "total" => $physicians['total'],
                    "increase" => $physicians['increase'],
                    "difference" => $physicians['difference'],
                    "text" => "Total Physicians",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-2.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ],
                [
                    "total" => $appointments['total'],
                    "increase" => $appointments['increase'],
                    "difference" => $appointments['difference'],
                    "text" => "Total Appointments",
                    "graph" => "/telehealth/src/Assets/images/graph-sm-1.png",
                    "image" => "/telehealth/src/Assets/images/card-stat-img-1.png"
                ]
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
                'role' => request('role'),
                'status' => 1,
                'appointment_status' => 1
            ]);

            $data = $this->{$type}->findAll($filter);
            $data = (new GraphGenerator($type))->data($data)->get();
            $data = api_successWithData(request('role') . ' data', $data);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function calender()
    {
        try {
            $calender = $this->appointment->getAdminCalender();
            return response()->json(api_successWithData('calender data.', $calender));
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
