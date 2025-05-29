<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ChargeBee\ChargeBee\Models\Customer;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'billing_address' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Update user in the database
            $user->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'billing_address' => $request->billing_address
            ]);
            
            // Only proceed with ChargeBee updates if user role is customer (role_id = 2)
            $isCustomer = $user->role_id === 3 || $user->role_id === '3';
            
            // Get or determine ChargeBee customer ID only for customers
            $chargebee_customer_id = null;
            
            if ($isCustomer) {
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
            }
            
            // Update customer data in ChargeBee or create a new customer - only for users with customer role
            if ($isCustomer) {
                if ($chargebee_customer_id) {
                    // Update existing customer in ChargeBee
                    try {
                        $result = Customer::update($chargebee_customer_id, [
                            "first_name" => $request->name,
                            "phone" => $request->phone,
                            "billing_address" => [
                                "first_name" => $request->name,
                                "line1" => $request->billing_address ?? 'Not specified'
                            ]
                        ]);
                        
                        // If successful, ensure user's chargebee_customer_id is set
                        if (!$user->chargebee_customer_id) {
                            $user->update(['chargebee_customer_id' => $chargebee_customer_id]);
                        }
                        
                        Log::info("ChargeBee customer updated successfully", [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $chargebee_customer_id
                        ]);
                    } catch (\Exception $e) {
                        // Log ChargeBee update error but don't fail the whole request
                        Log::error('ChargeBee customer update failed: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'chargebee_customer_id' => $chargebee_customer_id
                        ]);
                    }
                } else {
                    // If no ChargeBee customer exists, create one
                    try {
                        $result = Customer::create([
                            "first_name" => $request->name,
                            "email" => $user->email,
                            "phone" => $request->phone,
                            // "billing_address" => [
                            //     "first_name" => $request->name,
                            //     "line1" => $request->billing_address ?? 'Not specified'
                            // ]
                        ]);
                        
                        if ($result && $result->customer()) {
                            // Save the new ChargeBee customer ID to the user
                            $user->update(['chargebee_customer_id' => $result->customer()->id]);
                            Log::info("New ChargeBee customer created", [
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
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }
}