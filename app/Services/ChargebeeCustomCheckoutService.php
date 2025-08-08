<?php

namespace App\Services;

use ChargeBee\ChargeBee\Models\Customer;
use ChargeBee\ChargeBee\Models\PaymentSource;
use ChargeBee\ChargeBee\Models\Subscription;
use ChargeBee\ChargeBee\ChargeBee;
use App\Models\Plan;
use Log;

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

    public function attachPaymentSource($customerId, $token, $vaultToke)
    {
        $result = PaymentSource::createUsingTempToken([
            'customer_id' => $customerId,
            'tmp_token' => $vaultToke,
            'type'=> 'card',
            'gateway_account_id' => 'gw_Azqb55UtBKcr0Cks',
            'gateway'=>'stripe'
        ]);

       

        return $result->paymentSource();
    }

public function createSubscription($itemPriceId, $customerId)
{
  $plan = Plan::first();
       $result = Subscription::createWithItems($customerId, [
                "subscription_items" => [
                    [
                        "item_price_id" =>$plan->chargebee_plan_id,
                        "quantity" => 1
                    ]
                ]
            ]);

    return [
        'subscription' => $result->subscription(),
        'invoice' => $result->invoice(),
    ];
}




}




