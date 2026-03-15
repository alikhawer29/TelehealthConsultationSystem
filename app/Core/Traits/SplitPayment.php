<?php

namespace App\Core\Traits;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\Payment;

trait SplitPayment
{

    public function connect($data, $email = null)
    {
        try {
            $key = config("gateway.stripe.credentials.private_key");

            $stripe = new \Stripe\StripeClient($key);


            $account = $stripe->accounts->create([
                'type' => 'custom',
                'country' => 'US',
                'email' => $data['email'],
                'business_type' => 'company',
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' =>
                [
                    'mcc' => '7523',
                    'url' => 'https://caroltennis.com/',
                ],
                'company' => [
                    'address' =>
                    [
                        'city' => 'Santa Rosa',
                        'line1' => '3558 Round Barn Blvd Suite #200',
                        'postal_code' => '95403',
                        'state' => 'CA',

                    ],
                    'name' => $data['shop_name'],
                    'phone' => '866-210-3898',
                    'tax_id' => '222222222',

                ],
                ['tos_acceptance' => ['date' => time(), 'ip' => $_SERVER['REMOTE_ADDR']]],
            ]);

            $external = $stripe->accounts->createExternalAccount(
                $account->id,
                [
                    'external_account' => [
                        'account_number' => $data['account_number'],
                        'account_holder_name' =>  $data['account_holder_name'],
                        'currency' => 'aed',
                        'country' => 'US',
                        'object' => 'bank_account',
                        'routing_number' => '110000000'
                    ]
                ]
            );

            \Stripe\Stripe::setApiKey($key);

            $file = \Stripe\File::create([
                'purpose' => 'identity_document',
                'file' => fopen(public_path('bus-card.png'), 'r'),
            ], [
                'stripe_account' => $account->id,
            ]);

            $person = $stripe->accounts->createPerson(
                $account->id,
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'address' =>
                    [
                        'city' => 'Santa Rosa',
                        'line1' => '3558 Round Barn Blvd Suite #200',
                        'postal_code' => '95403',
                        'state' => 'CA',

                    ],
                    'phone' => '866-210-3898',
                    'ssn_last_4' => '2222',
                    'relationship' =>
                    [
                        'title' => 'Owner',
                        'executive' => 'true',
                        'owner' => 'true',
                        'representative' => 'true',
                        'percent_ownership' => '100'
                    ],
                    'email' => $data['email'],
                    'dob' => ['day' => '01', 'month' => '01', 'year' => '1902'],
                    'id_number' => '222222222'

                ]
            );

            $updatedPerson = $stripe->accounts->updatePerson(
                $account->id,
                $person->id,
                ['verification' => ['document' => ['front' => $file->id]]]
            );

            $getAccount = $stripe->accounts->retrieve(
                $account->id,
                []
            );

            return $getAccount->id;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function receivePayment()
    {
        $stripe = new \Stripe\StripeClient(
            config('gateway.stripe.credentials.private_key')
        );
        return  $stripe->transfers->retrieve(
            'tr_1MolsXEC8DW8yq3m5dUnKSs7',
            []
        );
    }

    public function retrieveAccount()
    {
        $stripe = new \Stripe\StripeClient(config('gateway.stripe.credentials.private_key'));

        return $stripe->accounts->retrieve('acct_1Mp3zzCrewjCP5Rj', []);
    }

    public function listAllTransfer()
    {
        $stripe = new \Stripe\StripeClient(
            config('gateway.stripe.credentials.private_key')
        );
        return $stripe->transfers->all(['limit' => 3]);
    }

    public function charge($amount, $token)
    {
        try {
            $stripe = new \Stripe\StripeClient(config("gateway.stripe.credentials.private_key"));
            //charge by card token
            $stripe = $stripe->charges->create([
                'amount' => $amount,
                'currency' => 'aed',
                'source' => $token,
            ]);
            return $stripe;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function createCustomer($data)
    {
        $user = request()->user(); // Assuming the user is authenticated

        // Check if the user already has a Stripe customer ID
        if (!$user->stripe_customer_id) {
            // Create a new customer in Stripe
            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name,
            ]);

            // Save the Stripe customer ID in your database
            $user->stripe_customer_id = $customer->id;
            $user->save();
        }

        // Attach the payment method to the customer
        $paymentMethod = $this->stripe->paymentMethods->retrieve($request->payment_method_id);
        $this->stripe->paymentMethods->attach($request->payment_method_id, ['customer' => $user->stripe_customer_id]);

        // Set the default payment method for the customer
        $this->stripe->customers->update($user->stripe_customer_id, [
            'invoice_settings' => [
                'default_payment_method' => $request->payment_method_id,
            ],
        ]);

        return response()->json(['success' => true, 'customer_id' => $user->stripe_customer_id]);
    }

    public function saveCards($data, $user)
    {

        try {
            $card_details = $data['card_details'];
            $stripe = new \Stripe\StripeClient(config("gateway.stripe.credentials.private_key"));

            $user = request()->user();

            // Check if the user already has a Stripe customer ID
            if (!$user->saved_card) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]);
            }

            // Create a Payment Method using the card details
            $paymentMethod = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $card_details['number'],
                    'exp_month' => $card_details['exp_month'],
                    'exp_year' => $card_details['exp_year'],
                    'cvc' => $card_details['cvc'],
                ],
            ]);


            // Attach the Payment Method to the customer
            $stripe->paymentMethods->attach($paymentMethod->id, ['customer' => $customer->id]);

            // Update the customer to set the default payment method
            $stripe->customers->update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod->id,
                ],
            ]);

            // Update or create bank data
            $bankData = [
                'account_holder_name' => $user->first_name . ' ' . $user->last_name,
                'account_number' => $card_details['number'], // This should be hashed/encrypted in a real app
                'stripe_id' =>  $customer->id,
                'stripe_payment_method_id' => $paymentMethod->id, // New field for payment method ID
            ];
            Bank::updateOrCreate(
                [
                    'accountable_type' => get_class($user),
                    'accountable_id' => $user->id,
                ],
                $bankData
            );

            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function chargeCustomer($amount, $customerId, $paymentMethodId)
    {

        try {

            $stripe = new \Stripe\StripeClient(config("gateway.stripe.credentials.private_key"));

            // Create a PaymentIntent to charge the customer
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => round($amount), // Amount in cents
                'currency' => 'aed',
                'customer' => $customerId, // Include the customer ID
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'transfer_group' => 'ORDER_' . uniqid(), // Unique identifier for the order

            ]);
            return $paymentIntent;
            return response()->json(['success' => true, 'payment_intent' => $paymentIntent]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function vendorAccount($email)
    {
        try {

            $vendor = Branch::where('email', $email)->first();

            $stripe = new \Stripe\StripeClient(config("gateway.stripe.credentials.private_key"));

            $connectedAccount = $stripe->accounts->create([
                'type' => 'express',
                'country' => 'US',
                'email' => $vendor->email,
            ]);

            // Update or create bank data
            $bankData = [
                'account_holder_name' => $vendor->name,
                'account_number' => $vendor->email, // This should be hashed/encrypted in a real app
                'stripe_id' =>  $connectedAccount->id,
                'stripe_payment_method_id' => 0, // New field for payment method ID
            ];
            Bank::updateOrCreate(
                [
                    'accountable_type' => get_class($vendor),
                    'accountable_id' => $vendor->id,
                ],
                $bankData
            );
            return response()->json(['success' => true, 'vendor stripe account created']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function transferPayment($amount, $stripe_id)
    {
        try {
            $key = config("gateway.stripe.credentials.private_key");
            $stripe = new \Stripe\StripeClient($key);
            $transfer = $stripe->transfers->create([
                'amount' => $amount * 100,
                'currency' => 'aed',
                'destination' => $stripe_id,
            ]);
            return $transfer;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function refundPayment($order)
    {
        try {
            $payments = Payment::where('payable_id', $order->id)->first();
            $chargeId = $payments->customer_payment_data['latest_charge'];
            $amount = $payments->amount;
            // Set your Stripe secret key from the configuration
            $key = config("gateway.stripe.credentials.private_key");
            // Initialize the Stripe client
            $stripe = new \Stripe\StripeClient($key);
            // Create the refund using the Stripe client
            $refund = $stripe->refunds->create([
                'charge' => $chargeId, // Use the charge ID
                'amount' => $amount, // Amount to refund in cents
                'reason' => 'requested_by_customer', // Reason for refund (optional)
            ]);

            $payments->status = 'refund';
            $payments->customer_refund_data = $refund;
            $payments->save();
            return $refund;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Catch and return Stripe API specific errors
            return $e->getMessage();
        } catch (\Exception $e) {
            // Catch and return general errors
            return $e->getMessage();
        }
    }
}
