<?php

namespace App\Core\Traits;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

trait StripePaymentTrait
{

    public function processCardPayment($cardNumber, $expMonth, $expYear, $cvc, $amount, $autoRenewal = false, $user = null)
    {
        $key = config("gateway.stripe.credentials.private_key");
        Stripe::setApiKey($key);

        try {
            // Create a PaymentMethod
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number'    => $cardNumber,
                    'exp_month' => $expMonth,
                    'exp_year'  => $expYear,
                    'cvc'       => $cvc,
                ],
            ]);

            $customer = null;

            // If auto-renewal is enabled, create a Stripe customer
            if ($autoRenewal && $user) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'payment_method' => $paymentMethod->id,
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id,
                    ],
                ]);
            }

            // Create and confirm a PaymentIntent using the PaymentMethod
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // amount in cents
                'currency' => 'aed',
                'customer' => $customer->id ?? null,
                'payment_method' => $paymentMethod->id,
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);

            // Return success response with Stripe customer ID if created
            return [
                'status' => 'success',
                'payment_intent' => $paymentIntent,
                'customer_id' => $customer->id ?? null,
            ];
        } catch (\Exception $e) {
            // Handle error and return failure response
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
