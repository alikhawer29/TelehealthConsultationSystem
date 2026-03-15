<?php

namespace App\Repositories\Order;

use App\Models\Ad;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Shop;
use App\Models\User;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shelter;
use App\Models\Appointment;
use App\Models\Commissions;
use App\Models\PackageCart;
use App\Models\OrderProduct;
use App\Models\Subscription;
use App\Models\ProductVariant;
use App\Core\Abstracts\Filters;
use App\Filters\Api\CartFilters;
use App\Core\Wrappers\Payment\Gateway;
use App\Filters\Api\CommissionFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Product\ProductRepository;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Repositories\Order\OrderRepositoryContract;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Repositories\Commissions\CommissionRepository;
use App\Repositories\Notification\NotificationRepository;

class OrderRepository extends BaseRepository implements OrderRepositoryContract
{

    private PaymentRepository $payment;
    private CartRepository $cart;
    private ProductRepository $productInventory;
    private CommissionRepository $rates;
    private NotificationRepository $notification;

    public function setModel(Model|MorphMany $model)
    {

        $this->model = $model;
        $this->payment = new PaymentRepository();
        $this->cart = new CartRepository();
        $this->rates = new CommissionRepository();
        $this->productInventory = new ProductRepository();

        $this->payment->setModel(Payment::make());
        $this->cart->setModel(Cart::make());
        $this->rates->setModel(Commissions::make());
        $this->productInventory->setModel(Product::make());
    }



    public function createPurchaseOrder(array $params)
    {
        \DB::beginTransaction();
        extract($params);
        $user = request()->user();
        $total = 0;

        try {

            $order = $this->model->create([
                'order_id' => generateTicketID(2, length: 6),
                'order_type' => 'package',
                'userable_type' => get_class($user),
                'userable_id' => $user->id,
                'status' => 'paid'
            ]);

            /** get package **/
            $package = Package::where('id', $params['package_id'])->first();

            OrderProduct::create([
                'order_id' =>  $order->id,
                'orderable_type' => get_class($package),
                'orderable_id' => $package->id,
                'qty' => $package->sessions,
                'price' => $package->cost,
            ]);

            $total += $package->cost;
            $this->paymentSession([
                'card_details' => $card_details,
                'order' => $order,
                'user' => $user,
                'total' => $total,
            ]);

            Subscription::create([
                'package_id' => $package->id,
                'type' => 'yearly',
                'expire_date' => now()->addMonth(12)->format('Y-m-d'),
                'subscribable_type' => get_class($user),
                'subscribable_id' => $user->id,
                'status' => 'active'
            ]);

            \DB::commit();
            return $order;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function createSessionOrder(array $params)
    {

        \DB::beginTransaction();
        extract($params);
        $user = request()->user();
        $total = 0;

        try {

            $order = $this->model->create([
                'order_id' => generateTicketID(2, length: 6),
                'order_type' => 'package',
                'userable_type' => get_class($user),
                'userable_id' => $user->id,
                'status' => 'paid'
            ]);

            $cart = PackageCart::where('userable_id', $user->id)->where('userable_type', get_class($user))->get();

            foreach ($cart as $package_id) {
                /** get package **/
                $package = Package::where('id', $package_id->package_id)->first();

                OrderProduct::create([
                    'order_id' =>  $order->id,
                    'orderable_type' => get_class($package),
                    'orderable_id' => $package->id,
                    'qty' => $package->sessions,
                    'price' => $package->cost,
                ]);
                /** add sessions **/
                $user->chargeSessions($package->sessions, 'in', $package);
                $total += $package->cost;

                Subscription::create([
                    'package_id' => $package->id,
                    'type' => 'session',
                    'expire_date' => now(),
                    'subscribable_type' => get_class($user),
                    'subscribable_id' => $user->id,
                    'remaining' => $package->sessions,
                    'status' => 'active'
                ]);
            }
            $this->paymentSession([
                'card_details' => $card_details,
                'order' => $order,
                'user' => $user,
                'total' => $total,
            ]);

            //flush cart
            PackageCart::where('userable_id', $user->id)->where('userable_type', get_class($user))->delete();

            \DB::commit();
            return $order;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }


    public function paymentSession(array $params)
    {

        try {
            extract($params);
            \DB::beginTransaction();
            $card_details = $params['card_details'];
            /** create payment **/
            $gateway = new Gateway('stripe');
            $card = ['card' => $card_details];
            $gateway->token($card)->pay($params['total'] * 100);
            $charge = $gateway->getCharge();

            /** store payment log **/
            $this->payment->create([
                'payer_id' => $params['user']->id,
                'payer_type' => get_class($params['user']),
                'payable_type' => get_class($params['order']),
                'payable_id' => $params['order']->id,
                'amount' => $params['total'],
                'commission' => 0,
                'delivery' => 0,
                'transaction_id' => $charge->id ?? 0,
                'split_payment_data' =>  $data ?? null,
            ]);
            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    function getDetailNames(&$detail)
    {
        if ($detail['country']) {
            $country = Country::find($detail['country']);
            if ($country) {
                $detail['country'] = $country->name;
            } else {
                $detail['country'] = null;
            }
        }

        if ($detail['state']) {
            $state = State::find($detail['state']);
            if ($state) {
                $detail['state'] = $state->name;
            } else {
                $detail['state'] = null;
            }
        }

        if ($detail['city']) {
            $city = City::find($detail['city']);
            if ($city) {
                $detail['city'] = $city->name;
            } else {
                $detail['city'] = null;
            }
        }
    }


    public function create(array $params)
    {

        extract($params);
        \DB::beginTransaction();
        try {

            if ($params['shipping_detail']) {
                $this->getDetailNames($params['shipping_detail']);
            }

            if ($params['billing_detail']) {
                $this->getDetailNames($params['billing_detail']);
            }

            $user = request()->user();
            $userType = get_class($user);

            //get products from cart
            $cartItems = Cart::where('userable_type', $userType)->where('userable_id', $user->id)->get();

            if ($cartItems->isNotEmpty()) {
                $firstCartItem = $cartItems->first();
                $vendor = $firstCartItem->vendorable()->with(['delivery', 'shop'])->first();
            }


            /** get commission and delivery rate **/
            list($commision) = $this->getRates('order');
            $delivery_id = $vendor->delivery->id ?? 0;
            $shop = $vendor->shop->id ?? 0;

            //create order
            $order = $this->model->create([
                ...$params,
                'order_id' => generateTicketID(2, length: 6),
                'order_type' => 'product',
                'userable_id' => $user->id,
                'userable_type' => $userType,
                'order_owner_id' => $cartItems[0]['vendorable_id'],
                'order_owner_type' => $cartItems[0]['vendorable_type'],
                'delivery_rate_id' => $delivery_id,
                'commission_id' => $commision->id,
                'shop_id' => $shop,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payout_status' => 'unpaid'
            ]);

            foreach ($cartItems as $items) {
                //less from inventory
                $variation = ProductVariant::where('id', $items['variation_id'])->first();
                $variation->qty = $variation->qty - $items['qty'];
                $variation->save();

                //create order details
                $order->orderDetails()->create([
                    'order_id' => $order->id,
                    'orderable_id' => $items['product_id'],
                    'orderable_type' => Product::class,
                    // 'price' => $items['charges'],
                    'price' => $variation->price,
                    'qty' => $items['qty'],
                    'packageable_type' => Product::class,
                    'packageable_id' => $items['product_id'],
                    'variation_id' => $items['variation_id'],
                ]);
            }
            \DB::commit();
            return $order->id;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function getOrder(int $id)
    {
        try {
            return Order::where('user_id', $id)->where('type', 'product')->where('status', 'payment_pending')->pluck('id');
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function createPetOrder(array $params, $pet)
    {
        extract($params);
        \DB::beginTransaction();
        try {
            $type = $pet->type == 'purchase' ? Shelter::class : User::class;
            $order = $this->model->create([
                ...$params,
                'order_owner_id' => $pet->owner_id,
                'order_owner_type' => $type,
                'order_id' => generateTicketID(2, length: 6),
                'status' => 'delivered'
            ]);

            $order->products()->create([
                'orderable_id' => $pet->id,
                'price' => $pet->payable_cost,
                'orderable_type' => 'ad',
                'qty' => 1,

            ]);

            $this->payment($order->id, [
                'card_details' => $card_details,
                'order_owner_type' => $type,
                'user_id' => $user_id,
                'order_owner_id' => $pet->owner_id,
            ]);

            \DB::commit();
            return $order;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    public function getAdminTotal(Filters|null $filter = null)
    {
        try {
            $order = $this->model->filter($filter)->pluck('id');
            $packagePayment = Payment::whereIn('payable_id', $order)->where('payable_type', 'App\Models\Order')->sum('amount');
            $bookingPayment = Appointment::where('appointment_status', 'approved')->sum('admin_commission');
            $commission = Payment::sum('commission');
            $total =  $packagePayment + $commission + $bookingPayment;
            return  ['total' => $total, 'trend' => 0];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTotal(Filters|null $filter = null)
    {
        try {
            // return $this->model->filter($filter)->count();
            $currentMonth = Carbon::now()->month;

            $totalPlayers = $this->model->filter($filter)->count();
            $currentMonthPlayers = $this->model->filter($filter)->whereMonth('created_at', $currentMonth)->count();
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


    public function getTotalCommission(Filters|null $filter = null)
    {
        try {
            $result = \DB::select('SELECT
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
                    ROUND(SUM(order_products.price * order_products.qty) * commission,2)
                FROM
                    order_products
                WHERE
                    order_products.order_id = order_id
            ) as total


            FROM orders
            WHERE
            order_owner_id = ' . request()->user()->id . '
            LIMIT 1 ');
            return collect($result)->pluck('total')
                ->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getAdminTotalCommission(Filters|null $filter = null)
    {
        try {
            $result = \DB::select('SELECT
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
                    ROUND(SUM(order_products.price * order_products.qty) * commission,2)
                FROM
                    order_products
                WHERE
                    order_products.order_id = order_id
            ) as total


            FROM orders

            LIMIT 1 ');
            return collect($result)->pluck('total')
                ->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function payment($id, array $params, Filters|null $filter = null)
    {
        try {
            extract($params);
            \DB::beginTransaction();

            $order = $this
                ->withCount([
                    'orderDetails as total' => fn($q) => $q->select(\DB::raw('SUM(price*qty)'))
                ]);

            /** for mobile app payment **/
            if ($filter) {
                $order = $order->findOne(filter: $filter);
            }

            /** for all web payments **/
            else {
                $order = $order->findById($id);
            }
            if (!$order) {
                throw new \Exception('Paying for Invalid Order. this order doesn\'t exist for payment');
            }

            /** get order owner **/
            // $vendor = 'App\\Models\\' . ucfirst($params['order_owner_type']);
            // $vendor = $vendor::find($order->order_owner_id);
            $vendor = $order->order_owner_type::find($order->order_owner_id);

            /** get commission and delivery rate **/
            list($commission, $delivery) = $this->getRates('order', $order->order_owner_id);

            /** set commissions and delivery charges **/
            // $order->commission_id  = $commission->id;

            /** pets order have not delivery charges **/
            // $order->delivery_rate_id = $order->type == 'pet' ? null : ($delivery != null ? $delivery->id : null);
            $order->status = 'pending';
            $order->payment_status = 'paid';
            $order->payout_status = 'unpaid';
            $order->save();

            /** calculate total **/
            $amount = 0;
            $amount = $order->total;

            /** calculate delivery charges **/
            // $delivery_charges = $order->type == 'pet' ? 0 : ($delivery != null ? $delivery->rate : 0);
            // $delivery_charges = Shop::where('ownerable_type', $order->order_owner_type)->where('ownerable_id', $order->order_owner_id)->first();
            // $delivery_charges = $delivery_charges->delivery_fees;
            $delivery_charges = $delivery->rate ?? 0;

            /** calculate commission **/
            $percentage = ($commission->rate / 100);
            $commission_charges = $delivery_charges + $amount;
            $commission_charges = $commission_charges * $percentage;

            /** create total **/
            $total = ($amount + $delivery_charges);

            /** create payment **/
            $gateway = new Gateway('stripe');
            $card = ['card' => $card_details];
            $gateway->token($card)->pay($total * 100);
            $charge = $gateway->getCharge();

            /** split payment to shelter or user **/
            // $data = $this->splitPayment(round($total - $commission_charges), $vendor->stripe_id ?? 0);


            /** store payment log **/
            $this->payment->create([
                'payer_id' => $order->userable_id,
                'payer_type' => $order->userable_type,
                'payable_type' => get_class($order),
                'payable_id' => $order->id,
                'amount' => $total,
                // 'commission' => $params['order_owner_type'] == "App\Models\User" ? '0' : $commission_charges,
                'commission' => $commission_charges,
                // 'delivery' => $params['order_owner_type'] == "App\Models\User" ? '0' :  $delivery_charges,
                'delivery' => $delivery_charges,
                'transaction_id' => $charge->id ?? 0,
                'split_payment_data' =>  $data ?? null,
                'payout_amount' =>  $total - $commission_charges,
                'payout_status' =>  'unpaid',
                'payout_date' => $order->payout_date,



            ]);

            // $cartFilter = new CartFilters(request());
            // $cartFilter->extendRequest([
            //     'personal' => $order->userable_id,
            // ]);
            /** clear cart **/
            if ($order->order_type == 'product') {
                Cart::where('userable_id', $order->userable_id)->where('userable_type', $order->userable_type)->delete();
            }

            /** send notification to user **/
            // $this->notification()->via(['firebase'])->send(
            //     $order->user,
            //     data: [
            //         'type' => $order->type,
            //         'id' => $order->id,
            //     ]
            // );

            /** send notification to user **/
            // $name = $order->type == 'pet' ? 'shelter' : 'vendor';
            // $this->notification()->send(
            //     $order->user,
            //     title: 'New Order Placed',
            //     body: "New order #{$order->id} has been Placed",
            //     data: [
            //         'route' => [
            //             'name' => $name . '.orders.show',
            //             'params' => [
            //                 'id' => $order->id,
            //             ],
            //         ]
            //     ]
            // );

            /** send notification to owner **/
            // $this->notification()->send(
            //     $order->owner,
            //     title: 'New Order Received',
            //     body: "New order #{$order->id} has been received",
            //     data: [
            //         'route' => [
            //             'name' => $name . '.orders.show',
            //             'params' => [
            //                 'id' => $order->id,
            //             ],
            //         ]
            //     ]
            // );
            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }

    private function getRates($userType, $user_id = null)
    {

        // $rateFilter = new CommissionFilters(request());

        // // get commission
        // $rateFilter->extendRequest([
        //     'rate_type' => 'commission',
        //     'user_type' => $userType == 'App\Models\Shelter' ? 'shelter' : 'vendor',
        //     'effective_date' => now(),
        //     'order' => 1,
        // ]);
        // $commission = $this->rates->findOne(filter: $rateFilter);

        // // get delivery
        // $rateFilter->extendRequest([
        //     'rate_type' => 'delivery',
        //     'vendor_id' => $user_id,
        // ]);

        $delivery = Commissions::where('rate_type', 'delivery')->where('vendor_id', $user_id)->first();
        $commission = Commissions::where('rate_type', 'commission')->where('user_type', $userType)->first();

        return [$commission, $delivery];
    }

    private function splitPayment($amount, $stripe_id)
    {
        try {
            $key = config("gateway.stripe.credentials.private_key");
            $stripe = new \Stripe\StripeClient($key);
            $transfer = $stripe->transfers->create([
                'amount' => $amount,
                'currency' => 'aed',
                'destination' => $stripe_id,
            ]);
            return $transfer;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
