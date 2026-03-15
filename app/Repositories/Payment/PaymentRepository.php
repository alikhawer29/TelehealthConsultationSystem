<?php

namespace App\Repositories\Payment;

use Stripe\Stripe;
use App\Models\Package;
use Stripe\PaymentIntent;
use App\Models\Advertisement;
use Illuminate\Support\Carbon;
use App\Core\Abstracts\Filters;
use Illuminate\Support\Facades\DB;
use App\Core\Traits\StripePaymentTrait;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Payment\PaymentRepositoryContract;

class PaymentRepository extends BaseRepository implements PaymentRepositoryContract
{

    use StripePaymentTrait;

    public function getTotal(Filters|null $filter = null)
    {
        try {
            return $this->model->filter($filter)->sum('amount');
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

    public function getAdminPayouts(Filters|null $filter = null)
    {
        try {
            $commission  = $this->model->filter($filter)->sum('payout_amount');
            return $commission;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getAdminEarnings(Filters|null $filter = null)
    {
        try {
            $commission  = $this->model->filter($filter)->sum('commission');
            return $commission;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTotalCommission($type = null)
    {
        try {
            if ($type == 'pet') {
                $innerType = "ad";
            } else {
                $innerType = "product";
            }
            $result = \DB::select('SELECT
            (
                SELECT
                    (rate / 100)
                FROM
                    commission
                WHERE
                    orders.type = ? AND
                    orders.commission_id = commission.id
            ) as commission,
            (
                SELECT
                    ROUND(IFNULL(SUM(order_products.price * order_products.qty) * commission,0),2)
                FROM
                    order_products
                WHERE
                    order_products.order_id = order_id
                    AND
                order_products.orderable_type = "' . $innerType . '"
            ) as total

            FROM orders LIMIT 1', [$type]);

            return collect($result)->pluck('total')
                ->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTotalCommissionShop($type = null)
    {
        try {
            $type  = 'pet';
            if ($type == 'pet') {
                $innerType = "product";
            } else {
                $innerType = "product";
            }
            $result = \DB::select('SELECT
            (
                SELECT
                    (rate / 100)
                FROM
                    commission
                WHERE
                    orders.type = ? AND
                    orders.commission_id = commission.id
            ) as commission,
            (
                SELECT
                    ROUND(IFNULL(SUM(order_products.price * order_products.qty) * commission,0),2)
                FROM
                    order_products
                WHERE
                    order_products.order_id = order_id
                    AND
                order_products.orderable_type = "' . $innerType . '"
            ) as total

            FROM orders LIMIT 1', [$type]);

            return collect($result)->pluck('total')
                ->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function getTotalCommissions(Filters|null $filter = null)
    {
        try {

            $result = \DB::select(
                'SELECT
            (
                SELECT
                    rate / 100
                FROM
                    commission
                WHERE
                    orders.commission_id = commission.id
            ) as commission,
            (
                SELECT
                    rate
                FROM
                    commission
                WHERE
                    orders.delivery_rate_id = commission.id
            ) as delivery,
            (
                SELECT
                    SUM(order_products.price * order_products.qty)
                FROM
                    order_products
                WHERE
                    order_products.order_id = orders.id
                    And
                    order_products.orderable_type ="product"
            ) as total

            FROM orders
            WHERE
            type = "product"

             AND
             order_owner_id = ' . request()->user()->id
            );

            $total = collect($result)->pluck('total');
            $total = $total->sum();

            $commission = collect($result)->pluck('commission')
                ->first();
            $delivery = collect($result)->pluck('delivery')
                ->first();
            $revenue = $total * $commission;

            $totalRevenue = $total -  $revenue;
            return $totalRevenue;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getShelterTotalRevenue(Filters|null $filter = null)
    {
        try {

            $result = \DB::select(
                'SELECT
            (
                SELECT
                    rate / 100
                FROM
                    commission
                WHERE
                    orders.commission_id = commission.id
            ) as commission
            ,
            (
                SELECT
                -- ROUND(IFNULL(
                    SUM(order_products.price * order_products.qty)
                    --  * commission,0),2)
                FROM
                    order_products
                WHERE
                    order_products.order_id = orders.id
                    And
                    order_products.orderable_type ="ad"
            ) as total

            FROM orders
            WHERE
            type = "pet"
             AND
             order_owner_id = ' . request()->user()->id
            );



            $total = collect($result)->pluck('total');

            $total = $total->sum();
            $commission = collect($result)->pluck('commission')
                ->first();
            $revenue = $total * $commission;

            $totalRevenue = $total -  $revenue;
            return $totalRevenue;
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function payment(array $params)
    {
        // Begin transaction
        DB::beginTransaction();

        try {
            // Set Stripe API Key
            Stripe::setApiKey(config('services.stripe.secret'));

            // Find advertisement
            $advertisement = Advertisement::findOrFail($params['advertisement_id']);
            $advertisementStatus = $advertisement->status; // Store status separately

            // Check if advertisement is approved
            if ($advertisementStatus !== 'approved') {
                throw new \Exception('Advertisement must be approved for payment.');
            }

            // Create PaymentIntent with `automatic_payment_methods`
            $paymentIntent = PaymentIntent::create([
                'amount' => $params['amount'] * 100, // Convert to cents
                'currency' => 'aed',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never', // Prevents redirect-based methods
                ],
                'metadata' => [
                    'advertisement_id' => $params['advertisement_id'],
                    'package_id' => $params['package_id'],
                ],
            ]);

            // Commit transaction
            DB::commit();

            return [
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            throw $e;
        }
    }


    public function confirmPayment(array $params)
    {
        DB::beginTransaction();

        try {
            // Set Stripe API Key
            Stripe::setApiKey(config('services.stripe.secret'));

            // Ensure the payment_intent_id is a string
            if (!isset($params['payment_intent_id']) || !is_string($params['payment_intent_id'])) {
                throw new \Exception("Invalid Payment Intent ID.");
            }

            // Retrieve PaymentIntent
            $paymentIntent = PaymentIntent::retrieve($params['payment_intent_id']);

            // If the payment requires a method, attach it dynamically
            if ($paymentIntent->status === 'requires_payment_method') {
                if (!isset($params['payment_method_id']) || !is_string($params['payment_method_id'])) {
                    throw new \Exception("Invalid Payment Method ID.");
                }

                // Ensure payment_method is passed correctly
                $paymentIntent = PaymentIntent::update($params['payment_intent_id'], [
                    'payment_method' => $params['payment_method_id'],
                ]);
            }

            // Confirm payment if required
            if ($paymentIntent->status === 'requires_confirmation') {
                $paymentIntent->confirm();
            }

            // Proceed only if payment is successful
            if ($paymentIntent->status === 'succeeded') {
                $advertisement = Advertisement::findOrFail($params['advertisement_id']);
                $advertisement->payment_status = 'paid';

                // Retrieve package details
                $package = Package::findOrFail($advertisement->package_id);
                $duration = $package->duration; // e.g., "2 week" or "5 month"

                // Extract number and unit
                if (preg_match('/(\d+)\s*(week|month)/i', $duration, $matches)) {
                    $value = (int) $matches[1];
                    $unit = strtolower($matches[2]);

                    // Update expiry date based on duration type
                    $advertisement->expiry_date = ($unit === 'week')
                        ? Carbon::now()->addWeeks($value)
                        : Carbon::now()->addMonths($value);
                }

                // Activate advertisement
                $advertisement->advertisement_status = 'active';
                $advertisement->save();
            }

            DB::commit();
            return $advertisement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
