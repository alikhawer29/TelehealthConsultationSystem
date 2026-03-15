<?php

namespace App\Http\Controllers\User\Payment;

use Carbon\Carbon;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Filters\Player\UserPaymentFilters;
use App\Repositories\Payment\PaymentRepository;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\CreateBuyerPaymentRequest;

class PaymentController extends Controller
{
    private PaymentRepository $payments;

    public function __construct(PaymentRepository $repo, Payment $payment)
    {
        $this->payments = $repo;
        $this->payments->setModel($payment);
    }


    public function index(Request $request, UserPaymentFilters $filter)
    {
        try {

            $filter->extendRequest([
                'sortBy' => 1,
                'groupBy' => 1,
                'personal' => $request->user()
            ]);
            $earnings = $this->payments->getTotalEarnings(filter: $filter);
            $payments = $this->payments
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['payable' => function ($q) {
                        $q
                            ->withCount([
                                'products as total' => fn($q) => $q->select(\DB::raw('SUM(price * quantity)'))
                            ]);
                    }],
                );
            $data = api_successWithData('payments listing', compact('payments', 'earnings'));
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function create(CreatePaymentRequest $request)
    {
        try {
            $data = $this->payments->payment($request->validated());
            $data = api_successWithData('intent created successfully', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function confirmPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'advertisement_id' => 'required|exists:advertisements,id',
                'payment_status' => 'required|in:paid,failed',
                'payment_intent_id' => 'required',
                'payment_method_id' => 'sometimes|required', // Ensure payment method is required only when necessary
            ]);

            $advertisement = $this->payments->confirmPayment($validated);

            return response()->json(api_successWithData('Payment status updated successfully', $advertisement), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
