<?php

namespace App\Console\Commands;

use App\Models\Bank;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PayoutOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:payout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->orderPayouts();
    }

    public function orderPayouts()
    {
        try {
            $stripe = new \Stripe\StripeClient(config("gateway.stripe.credentials.private_key"));
            $payments = Payment::with('payable')
                ->where('payout_status', 'unpaid')
                ->where('status', 'paid')
                ->whereDate('payout_date', now()->format('Y-m-d'))
                ->get();
            foreach ($payments as $payment) {
                if ($payment->payable) {
                    $owner = Bank::where('accountable_type', 'App\Models\Restaurant')
                        ->where('accountable_id', $payment->payable->restaurant_id)
                        ->first();
                    if ($owner && $owner->stripe_id) {
                        $transfer = $stripe->transfers->create([
                            'amount' => intval(round($payment->payout_amount)),
                            'currency' => 'aed',
                            'destination' => $owner->stripe_id,
                        ]);
                        $payment->update([
                            'split_payment_data' => $transfer,
                            'payout_status' => 'paid',
                        ]);
                        $message =  'Restaurant ' . $owner->account_holder_name . ' : ' . $owner->order_owner_id . ', have been paid out by admin, order id is: ' . $payment->payable->order_id . ' and amount is : ' . $payment->payout_amount . ' on date: ' . now();
                        Log::info($message);
                    }
                }
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
