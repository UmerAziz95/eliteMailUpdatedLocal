<?php

namespace App\Services;

use ChargeBee\ChargeBee\Models\Customer;
use ChargeBee\ChargeBee\Models\PaymentSource;
use ChargeBee\ChargeBee\Models\Subscription;
use ChargeBee\ChargeBee\ChargeBee;

class ChargebeeCustomCheckoutService
{
    public function createCustomer($email, $firstName = '', $lastName = '')
    {
        $result = Customer::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName
        ]);

        return $result->customer();
    }

    public function attachPaymentSource($customerId, $token)
    {
        $result = PaymentSource::createUsingTempToken([
            'customer_id' => $customerId,
            'tmp_token' => $token,
            'type'=> 'card'
        ]);

       

        return $result->paymentSource;
    }

    public function createSubscription($planId, $customerId)
    {
        $result = Subscription::create([
            'plan_id' => $planId,
            'plan_quantity' =>10,
            'customer' => ['id' => $customerId],
            'billing_cycles' => 1, // optional
        ]);

        return [
            'subscription' => $result->subscription,
            'invoice' => $result->invoice,
        ];
    }
}
