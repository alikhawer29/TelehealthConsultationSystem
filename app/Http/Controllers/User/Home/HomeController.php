<?php

namespace App\Http\Controllers\User\Home;

use App\Models\User;
use App\Models\Branch;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Filters\Admin\HomeFilters;
use App\Http\Controllers\Controller;
use App\Core\Generators\GraphGenerator;
use App\Repositories\User\UserRepository;
use App\Repositories\Branch\BranchRepository;
use App\Repositories\Payment\PaymentRepository;

class HomeController extends Controller
{
    private PaymentRepository $payment;
    private UserRepository $user;
    private BranchRepository $branch;

    public function __construct(
        PaymentRepository $paymentRepo,
        Payment $payment,
        UserRepository $userRepo,
        User $user,
        BranchRepository $branchRepo,
        Branch $branch
    ) {
        $this->payment = $paymentRepo;
        $this->user = $userRepo;
        $this->branch = $branchRepo;
        $this->branch->setModel($branch);
        $this->user->setModel($user);
        $this->payment->setModel($payment);
    }

    public function index(Request $request, HomeFilters $filter)
    {

        try {
            $buyerId = $request->user()->id;
            $thirtyDaysAgo = now()->subDays(30);
            $paymentExists = Payment::where('payer_id', $buyerId)
                ->where('created_at', '>=', $thirtyDaysAgo) // Checks if the payment is made in the last 30 days
                ->exists();
            $subscriptions = false;

            $data['subscriptions'] = $subscriptions;
            $data = api_successWithData('home data', $data);
            return response()->json($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function chart($type, Request $request, HomeFilters $filter)
    {
        try {

            //buyer - supplier - driver - user

            $filter->extendRequest([
                $type => 1,
                'role' => request('role')
            ]);

            $data = $this->{$type}->findAll($filter);
            $data = (new GraphGenerator($type))->data($data)->get();
            $data = api_successWithData(request('role') . ' data', $data);
            return response()->json($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
