<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ChargeBee\ChargeBee\Models\Customer;

class ProfileController extends Controller
{
    public function updateAddress(Request $request)
    {
        try {
            // address not update on chargebee side please check it deeply
            $validator = Validator::make($request->all(), [
                'modalcountry' => 'required|string|max:255',
                'modalAddressAddress1' => 'required|string|max:255',
                'modalAddressAddress2' => 'nullable|string|max:255',
                'modalAddressLandmark' => 'nullable|string|max:255',
                'modalAddressCity' => 'required|string|max:255',
                'modalAddressState' => 'nullable|string|max:255',
                'modalAddressZipCode' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Update user in our database
            $user->update([
                'billing_country' => $request->modalcountry,
                'billing_address' => $request->modalAddressAddress1,
                'billing_address2' => $request->modalAddressAddress2,
                'billing_landmark' => $request->modalAddressLandmark,
                'billing_city' => $request->modalAddressCity,
                'billing_state' => $request->modalAddressState,
                'billing_zip' => $request->modalAddressZipCode,
                'billing_company' => $request->modalAddressCompany,
            ]);

            // Get or determine ChargeBee customer ID
            $chargebee_customer_id = null;
            
            // First try to get it from user
            if ($user->chargebee_customer_id) {
                $chargebee_customer_id = $user->chargebee_customer_id;
            } else {
                // Try to get it from the latest order
                $latestOrder = Order::where('user_id', $user->id)
                    ->whereNotNull('chargebee_customer_id')
                    ->latest()
                    ->first();
                    
                if ($latestOrder) {
                    $chargebee_customer_id = $latestOrder->chargebee_customer_id;
                }
            }
            // Update customer data in ChargeBee or create a new customer
            if ($chargebee_customer_id) {
                // Update existing customer in ChargeBee
                try {
                    $result = Customer::update($chargebee_customer_id, [
                        "billing_address" => [
                            "first_name" => $user->name,
                            "company" => $request->modalAddressCompany,
                            "line1" => $request->modalAddressAddress1,
                            "line2" => $request->modalAddressAddress2,
                            "city" => $request->modalAddressCity,
                            "state" => $request->modalAddressState,
                            "zip" => $request->modalAddressZipCode,
                            "country" => $request->modalcountry
                        ]
                    ]);
                    // dd($result);
                    // If successful, ensure user's chargebee_customer_id is set
                    if (!$user->chargebee_customer_id) {
                        $user->update(['chargebee_customer_id' => $chargebee_customer_id]);
                    }
                    
                    Log::info("ChargeBee customer billing address updated", [
                        'user_id' => $user->id,
                        'chargebee_customer_id' => $chargebee_customer_id
                    ]);
                } catch (\Exception $e) {
                    // Log ChargeBee update error but don't fail the whole request
                    Log::error('ChargeBee customer billing address update failed: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'chargebee_customer_id' => $chargebee_customer_id
                    ]);
                }
            } else {
                // If no ChargeBee customer exists, create one
                try {
                    $result = Customer::create([
                        "first_name" => $user->name,
                        "email" => $user->email,
                        "phone" => $user->phone,
                        "billing_address" => [
                            "first_name" => $user->name,
                            "company" => $request->modalAddressCompany,
                            "line1" => $request->modalAddressAddress1,
                            "line2" => $request->modalAddressAddress2,
                            "city" => $request->modalAddressCity,
                            "state" => $request->modalAddressState,
                            "zip" => $request->modalAddressZipCode,
                            "country" => $request->modalcountry
                        ]
                    ]);
                    
                    if ($result && $result->customer()) {
                        // Save the new ChargeBee customer ID to the user
                        $user->update(['chargebee_customer_id' => $result->customer()->id]);
                        Log::info("New ChargeBee customer created with billing address", [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $result->customer()->id
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log ChargeBee creation error but don't fail the whole request
                    Log::error('ChargeBee customer creation failed: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Billing address updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating billing address: ' . $e->getMessage(), [
                'user_id' => auth()->id() ?? 'unknown'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating billing address: ' . $e->getMessage()
            ], 500);
        }
    }
}