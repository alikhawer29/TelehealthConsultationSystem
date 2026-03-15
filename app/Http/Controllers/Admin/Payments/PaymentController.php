<?php

namespace App\Http\Controllers\Admin\Payments;


use App\Models\Payment;
use App\Http\Controllers\Controller;
use App\Filters\Admin\PaymentFilters;
use Illuminate\Support\Facades\Request;
use App\Repositories\Payment\PaymentRepository;
use App\Http\Requests\Payment\AdminPaymentRequest;

class PaymentController extends Controller
{
    private PaymentRepository $payments;


    public function __construct(PaymentRepository $repo, Payment $payment)
    {
        $this->payments = $repo;
        $this->payments->setModel($payment);
    }

    public function index(PaymentFilters $filter)
    {
        try {
            $filter->extendRequest([
                'order' => 1,
                'status' => 'paid'
            ]);
            $payments = $this->payments->paginate(
                request('per_page', 10),
                filter: $filter,
                relations: ['payable.serviceProvider', 'payer']
            );
            $data = api_successWithData('payments listing', $payments);
            return response()->json($data);
        } catch (\Exception $e) {
            return $e;
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function earnings(PaymentFilters $filter)
    {
        try {
            $filter->extendRequest([
                'order' => 1,
                'status' => 'paid'
            ]);
            $payments = $this->payments->findAll(
                filter: $filter,
            );
            $total = $payments->sum('amount'); // Calculate total amount

            $data = api_successWithData('admin earnings', ['total' => $total]);
            return response()->json($data);
        } catch (\Exception $e) {
            return $e;
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }




    public function show($id, Request $request, PaymentFilters $filter)
    {
        try {
            $orders = $this->payments
                ->findById(
                    $id,
                    filter: $filter,
                    relations: ['payable', 'payer']
                );

            $data = api_successWithData('payments details', $orders);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }
}
