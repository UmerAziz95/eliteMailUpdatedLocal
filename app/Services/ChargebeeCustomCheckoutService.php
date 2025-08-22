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
    public function createCustomer($email, $firstName = '', $lastName = '', $addressLine1 = '', $city = '', $state = '', $zip = '', $country = '')
    {
        // Create a new customer in Chargebee
        if (empty($email)) {
            throw new \InvalidArgumentException('Email is required to create a customer.');
        }

        // Optional fields can be added as needed
    {
        $result = Customer::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'billing_address' => [
                'line1' => "test line1",
                'city' => "test city",
                'state' => "test state",
                'zip' => "12345",
                'country' => "US"
            ],
            'billing_address2' => [
                 'line1' => "test line1",
                'city' => "test city",
                'state' => "test state",
                'zip' => "12345",
                'country' => "US"
            ],
            'address' => [
                'line1' => $addressLine1,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'country' => $country
            ]
        ]);

        return $result->customer();
    }
}

        public function attachPaymentSource($customerId, $token, $vaultToke)
        {
            $result = PaymentSource::createUsingTempToken([
                'customer_id' => $customerId,
                'tmp_token' => $vaultToke,
                'type'=> 'card',
                'gateway_account_id' => env('PAYMENT_GATEWAY_ID'),
                'gateway'=>'stripe'
            ]);

        

            return $result->paymentSource();
        } 

        public function createSubscription($itemPriceId, $customerId, $quantity)
        {
      
            $result = Subscription::createWithItems($customerId, [
                        "subscription_items" => [
                            [
                                "item_price_id" =>$itemPriceId,
                                "quantity" => $quantity
                            ]
                        ]
                    ]);

            return [
                'subscription' => $result->subscription(),
                'invoice' => $result->invoice(),
            ];
        }




}




