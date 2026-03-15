<?php

namespace App\Repositories\Packages;

use Carbon\Carbon;
use App\Models\Payment;
use App\Models\PackageCart;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Payment\PaymentRepository;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Core\Traits\StripePaymentTrait;
use App\Repositories\Subscription\SubscriptionRepository;

class PackagesRepository extends BaseRepository implements PackagesRepositoryContract
{
    protected $model;
    private PaymentRepository $payment;
    private SubscriptionRepository $subscription;

    use StripePaymentTrait;

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->payment = new PaymentRepository();
        $this->payment->setModel(Payment::make());

        $this->subscription = new SubscriptionRepository();
        $this->subscription->setModel(Subscription::make());
    }

    public function updatePlan($id, array $params)
    {

        try {
            return $this->model->updateOrCreate(
                ['id' => $id],
                [
                    'cost' => $params['cost'],
                    'sessions' => isset($params['sessions']) ? $params['sessions'] : 1,
                    'description' => $params['description'],
                ],
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function status($id)
    {
        try {
            $data = $this->model->where('id', $id)
                ->update([
                    'status' => \DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END")
                ]);

            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function updatePackage($id, array $params)
    {

        try {
            return $this->model->update(
                ['id' => $id],
                [
                    'title' => $params['title'],
                    'amount' => $params['amount'],
                    'period' => $params['period'],
                    'details' => $params['details'],
                    // 'type' => 'normal',
                    // 'for' => 'supplier',
                    // 'status' => 'active',
                    // 'from' => now(),
                    // 'to' => now()->addMonths($params['period']),
                ],
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function create(array $params)
    {
        try {
            // Check if the type is 'custom' and a user_id is provided
            if ($params['type'] === 'custom' && $params['user_id']) {
                // Check if a record with the same user_id already exists
                $existingRecord = $this->model->where('user_id', $params['user_id'])->first();

                if ($existingRecord) {
                    // If record exists, update it with the new status and type
                    $existingRecord->update([
                        'title' => $params['title'],
                        'no_of_users' => $params['no_of_users'],
                        'branches' => $params['branches'],
                        'price_monthly' => $params['price_monthly'],
                        'price_yearly' => $params['price_yearly'],
                        'status' => 'active', // Use the status from $params
                        'type' => 'custom', // Set the type to 'custom'
                        // Optionally, you can update other fields as needed
                    ]);
                    return $existingRecord; // Return the updated record
                }
            }

            // If type is 'general' or no matching record found for 'custom', create a new record
            return $this->model->create([
                'title' => $params['title'],
                'no_of_users' => $params['no_of_users'],
                'branches' => $params['branches'],
                'price_monthly' => $params['price_monthly'],
                'price_yearly' => $params['price_yearly'],
                'type' => $params['type'],
                'user_id' => $params['type'] === 'custom' ? $params['user_id'] : null, // If type is 'custom', set user_id
                'status' => $params['type'] === 'request' ? 'pending' : 'active', // Set status based on type
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function slots($id, $params)
    {
        try {
            $weekDay =  Carbon::parse($params)->dayOfWeek;
            $get = $this->findById(
                $id,
                relations: ['slots' => function ($q) use ($params, $weekDay) {
                    $q->where('day', $weekDay);
                }]
            );
            return $get->slots;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateStatus($id, $params)
    {

        try {
            $get = $this->findById(
                $id
            );
            $this->model->updateOrCreate(
                ['id' => $id],
                [$params['column'] =>  $params['status']],
            );
            if ($params['column'] == 'approved' && $params['status'] == '1') {
                $this->model->updateOrCreate(
                    ['id' => $id],
                    ['status' =>  1],
                );
            }
            return $get;
        } catch (\Throwable $th) {
            throw $th;
        }
    }





    public function payment($id, array $params)
    {
        DB::beginTransaction();

        try {
            $user = request()->user();
            $package = $this->model->findOrFail($id);
            $cardDetails = $params['card_details'];
            $price = $cardDetails['type'] == 'yearly' ? $package->price_yearly : $package->price_monthly;

            // Process payment
            $charge = $this->processCardPayment(
                $cardDetails['number'],
                $cardDetails['exp_month'],
                $cardDetails['exp_year'],
                $cardDetails['cvc'],
                $price,
                $cardDetails['auto_renewal'] ?? false,
                $user
            );

            if ($charge['status'] === 'error') {
                throw new \Exception($charge['message']);
            }

            // Invalidate existing active subscriptions for the user
            $this->invalidateExistingSubscriptions($user->id);

            // Save payment log
            $this->payment->create([
                'payer_id' => $user->id,
                'payer_type' => get_class($user),
                'payable_type' => get_class($package),
                'payable_id' => $package->id,
                'transaction_id' => $charge['payment_intent']['id'] ?? 0,
                'amount' => $price,
                'commission' => 0,
                'payout_amount' => 0,
                'payout_date' => null,
                'payout_status' => 'unpaid',
                'split_payment_data' => null,
                'customer_payment_data' => $charge ?? null,
                'status' => 'paid',
            ]);

            // Save subscription
            $this->createSubscription($package, $user, $cardDetails);

            // Save Stripe customer ID if auto-renewal is enabled
            if (isset($charge['customer_id']) && $cardDetails['auto_renewal']) {
                $user->update(['stripe_customer_id' => $charge['customer_id']]);
            }

            DB::commit();

            return $package;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function invalidateExistingSubscriptions($userId)
    {
        Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);
    }



    public function custom($data)
    {
        DB::beginTransaction();

        try {
            $userId = request()->user()->id;

            $package = $this->model->create([
                'no_of_users' => $data['no_of_users'],
                'branches' => $data['branches'],
                'comments' => $data['comments'],
                'status' => 'pending',
                'type' => 'request',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'user_id' => $userId
            ]);
            DB::commit();

            return $package;
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            throw $e;
        }
    }



    public function createSubscription($package, $user, $cardDetails)
    {
        $expiryDate = Carbon::now()->addMonth($cardDetails['type'] === 'monthly' ? 1 : 12);

        $business_id = $user->role === 'employee' ? $user->parent_id : $user->id;

        // Inactivate old subscriptions for the user
        Subscription::where('business_id', $business_id)
            ->update(['status' => 'inactive']);

        // Create new active subscription
        $this->subscription->create([
            'package_id' => $package->id,
            'business_id' => $business_id,
            'expire_date' => $expiryDate->format('Y-m-d'),
            'user_id' => $user->id,
            'status' => 'active',
            'type' => $cardDetails['type'],
            'auto_renewal' => $cardDetails['auto_renewal'],
        ]);
    }


    public function checkout(array $params)
    {
        try {
            extract($params);
            $user = request()->user();
            \DB::beginTransaction();

            //flush cart
            PackageCart::where('userable_id', $user->id)->where('userable_type', get_class($user))->delete();

            foreach ($params['package_id'] as $package_id) {

                /** get package **/
                $package = $this->model->find($package_id);

                $cart = PackageCart::create([
                    'package_id' => $package->id,
                    'userable_id' => $user->id,
                    'userable_type' => get_class($user),
                    'total' => $package->cost,
                ]);
            }

            $data['cart'] = PackageCart::with('package:id,name')
                ->where('userable_id', $user->id)->where('userable_type', get_class($user))->get();
            $data['cart_total'] = $data['cart']->sum('total');
            \DB::commit();
            return $data;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function paymentLogs(array $params)
    {
        try {
            $get = $this->findById(
                $id,
                relations: ['slots' => function ($q) use ($params, $weekDay) {
                    $q->where('day', $weekDay);
                }]
            );
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function subscriptionLogs($filter)
    {
        try {
            $user = request()->user();
            $type = get_class($user);
            $data = Subscription::with('package')
                ->withCount([
                    'sessions as add_sessions' => function ($q) {
                        $q->where('type', 'in')->select(\DB::raw('IFNULL(SUM(sessions), 0)'));
                    },
                    'sessions as less_sessions' => function ($q) {
                        $q->where('type', 'out')->select(\DB::raw('IFNULL(SUM(sessions), 0)'));
                    }
                ])
                ->where('subscribable_id', $user->id)->where('subscribable_type', get_class($user))
                ->when(request()->filled('status') && $type == 'App\Models\Player', function ($q) {
                    if (request('status') == 1) {
                        $q->where('remaining', '>', 0);
                    } else {
                        $q->where('remaining', 0);
                    }
                })
                ->when(request()->filled('coach_status') && $type == 'App\Models\Coach', function ($q) {
                    $q->where('status', request('coach_status'));
                })
                ->when(request()->filled('coach_status') && $type == 'App\Models\Psychologist', function ($q) {
                    $q->where('status', request('coach_status'));
                })
                ->when(request()->filled('from') && request()->filled('to'), function ($q) {
                    $q->whereDate('created_at', '>=', request('from'));
                    $q->whereDate('created_at', '<=', request('to'));
                })
                ->when(request()->filled('expireDate_from') && request()->filled('expireDate_to'), function ($q) {
                    $q->whereDate('expire_date', '>=', request('expireDate_from'));
                    $q->whereDate('expire_date', '<=', request('expireDate_to'));
                })
                ->when($filter->search, function ($q) use ($filter) {
                    $q->whereHas('package', function ($q) use ($filter) {
                        $q->where('name', 'like', '%' . $filter->search . '%');
                    });
                })
                ->paginate(request('per_page', 10));
            return $data;
        } catch (\Throwable $th) {
            \DB::rollback();
            throw $th;
        }
    }
}
