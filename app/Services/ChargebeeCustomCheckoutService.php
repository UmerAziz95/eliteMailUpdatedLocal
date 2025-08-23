<?php

namespace App\Services;

use ChargeBee\ChargeBee\Models\Customer;
use ChargeBee\ChargeBee\Models\PaymentSource;
use ChargeBee\ChargeBee\Models\Subscription;
use ChargeBee\ChargeBee\ChargeBee;
use App\Models\Plan;
use App\Models\User;
use Log;

class ChargebeeCustomCheckoutService
{
    
    public function createCustomer($email, $firstName = '', $lastName = '', $addressLine1 = '', $city = '', $state = '', $zip = '', $country = '')
    {
        try {
            // Create a new customer in Chargebee
            if (empty($email)) {
                throw new \InvalidArgumentException('Email is required to create a customer.');
            }
            
            // Check if user exists in our database
            $user = User::where('email', $email)->first();
            
            if ($user && !empty($user->chargebee_customer_id)) {
                // User exists and has a Chargebee customer ID - update existing customer
                $result = Customer::update($user->chargebee_customer_id, [
                    'first_name' => $firstName ?: $user->name,
                    'last_name' => $lastName,
                    'billing_address' => [
                        'line1' => $addressLine1 ?: $user->billing_address,
                        'line2' => $user->billing_address2,
                        'city' => $city ?: $user->billing_city,
                        'state' => $state ?: $user->billing_state,
                        'zip' => $zip ?: $user->billing_zip,
                        'country' => $country ?: $user->billing_country
                    ]
                ]);
                
                $result = Customer::updateBillingInfo($user->chargebee_customer_id, [
                    "billing_address" => [
                        "first_name" => $firstName,
                        "line1" => $addressLine1,
                        "line2" => $user->billing_address2 ?? "",
                        "city" => $city,
                        "state" => $state ?? "",
                        "zip" => $zip,
                        "country" => $country                           
                    ]
                ]);
                
                return $result->customer();
            }
            
            if ($user) {
                // User exists but no Chargebee customer ID - use user data as defaults
                $firstName = $firstName ?: $user->name;
                $addressLine1 = $addressLine1 ?: $user->billing_address;
                $city = $city ?: $user->billing_city;
                $state = $state ?: $user->billing_state;
                $zip = $zip ?: $user->billing_zip;
                $country = $country ?: $user->billing_country;
            }
            
            // Create new customer in Chargebee
            $result = Customer::create([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'billing_address' => [
                    'line1' => $addressLine1 ?: 'N/A',
                    'line2' => $user->billing_address2 ?? '',
                    'city' => $city ?: 'N/A',
                    'state' => $state ?: 'N/A',
                    'zip' => $zip ?: '00000',
                    'country' => $country ?: 'US'
                ]
            ]);

            // Update user with Chargebee customer ID if user exists
            if ($user) {
                $user->update(['chargebee_customer_id' => $result->customer()->id]);
            }

            return $result->customer();
            
        } catch (\Exception $e) {
            Log::error('Failed to create/update Chargebee customer: ' . $e->getMessage());
            throw $e;
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




