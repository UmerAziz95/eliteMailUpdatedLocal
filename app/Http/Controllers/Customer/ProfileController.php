<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function updateAddress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'modalcountry' => 'required|string|max:255',
                'modalAddressAddress1' => 'required|string|max:255',
                'modalAddressAddress2' => 'nullable|string|max:255',
                'modalAddressLandmark' => 'nullable|string|max:255',
                'modalAddressCity' => 'required|string|max:255',
                'modalAddressState' => 'required|string|max:255',
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
            
            $user->update([
                'billing_country' => $request->modalcountry,
                'billing_address' => $request->modalAddressAddress1,
                'billing_address2' => $request->modalAddressAddress2,
                'billing_landmark' => $request->modalAddressLandmark,
                'billing_city' => $request->modalAddressCity,
                'billing_state' => $request->modalAddressState,
                'billing_zip' => $request->modalAddressZipCode,
                // billing_company
                'billing_company' => $request->modalAddressCompany,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Billing address updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating billing address: ' . $e->getMessage()
            ], 500);
        }
    }
}