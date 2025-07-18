<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Coupon;
use ChargeBee\ChargeBee\Models\Coupon as ChargeBeeCoupon;
use ChargeBee\ChargeBee\Models\CouponSet;
use ChargeBee\ChargeBee\Models\CouponCode;
use ChargeBee\ChargeBee\Exceptions\InvalidRequestException;
use ChargeBee\ChargeBee\Exceptions\APIError;
use DataTables;

class AdminCouponController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => 'required|string|unique:coupons,code',
            'type'          => ['required', Rule::in(['percentage', 'fixed'])],
            'value'         => 'required|numeric|min:0',
            'usage_limit'   => 'nullable|integer|min:1',
            'status'        => ['required', Rule::in(['active', 'inactive'])],
            'expires_at'    => 'nullable|date|after:today',
            // 'plan_id'       => 'required|exists:plans,id', // Keep this validated!
        ]);

        $prefix     = strtoupper($validated['code']);
        $templateId = strtolower($prefix) . '-tmpl';
        $setId      = strtolower($prefix) . '-set';
        $count      = $validated['usage_limit'] ?? 1;
        $created    = [];

        $couponResponse = null; // Holds the ChargeBee_Result for the Coupon Template

        // 1. Create or retrieve Coupon Template in ChargeBee
        try {
            $couponResponse = ChargeBeeCoupon::createForItems([
                'id'                    => $templateId,
                'name'                  => $prefix . ' Coupon',
                'discount_type'         => $validated['type'],
                'discount_percentage'   => $validated['type'] === 'percentage' ? $validated['value'] : null,
                'discount_amount'       => $validated['type'] === 'fixed' ? intval($validated['value'] * 100) : null,
                'duration_type'         => 'one_time',
                'status'                => $validated['status'],
                'valid_till'            => $validated['expires_at'] ? strtotime($validated['expires_at']) : null,
                'item_applicability'    => 'specific_items',
                'apply_on'              => 'invoice_amount',
                'applicable_item_ids'   => "gt_1750481485_68563a4d95839",// Use validated plan_id here
            ]);
        } catch (InvalidRequestException $e) {
            if (str_contains($e->getMessage(), 'already present') || str_contains($e->getMessage(), 'already exists')) {
                try {
                    $couponResponse = ChargeBeeCoupon::retrieve($templateId);
                } catch (\Exception $ex) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Coupon template already exists but failed to retrieve it: ' . $ex->getMessage(),
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ChargeBee Coupon template creation failed (InvalidRequestException): ' . $e->getMessage(),
                ], 500);
            }
        } catch (APIError $e) {
            if (str_contains($e->getMessage(), 'already present') || str_contains($e->getMessage(), 'already exists')) {
                 try {
                    $couponResponse = ChargeBeeCoupon::retrieve($templateId);
                } catch (\Exception $ex) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Coupon template already exists but failed to retrieve it (via APIError catch): ' . $ex->getMessage(),
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ChargeBee Coupon template creation failed (APIError): ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during coupon template operation: ' . $e->getMessage(),
            ], 500);
        }

        // Validate that couponResponse is a valid ChargeBee_Result object and contains a coupon model
        if (!$couponResponse || !($couponResponse->coupon())) {
            return response()->json([
                'success' => false,
                'message' => 'ChargeBee Coupon template creation or retrieval failed to return a valid coupon object.',
            ], 500);
        }




        //2
        $couponSetResult = null; // Holds the ChargeBee_Result for the Coupon Set
        // 2. Create or retrieve Coupon Set in ChargeBee
        try {
            $couponSetResult = CouponSet::create([
                'id'        => $setId,
                'name'      => $prefix . ' Set',
                'coupon_id' => $templateId,
            ]);
        } catch (InvalidRequestException $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                try {
                    $couponSetResult = CouponSet::retrieve($setId);
                   
                    

                    // Check the coupon_id of the retrieved set directly on the model
                    // This is where the problematic line was. Access directly on the result for validation
                    if ($couponSetResult) {
                        $retrievedSet = $couponSetResult->coupon_set; 
                        if (empty($retrievedSet->coupon_id) || $retrievedSet->coupon_id !== $templateId) {
                            try {
                                CouponSet::delete($setId);
                                $couponSetResult = CouponSet::create([
                                    'id'        => $setId,
                                    'name'      => $prefix . ' Set',
                                    'coupon_id' => $templateId,
                                ]);
                                \Log::info("Recreated Coupon Set {$setId} due to missing/incorrect coupon_id.");
                            } catch (\Exception $deleteEx) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Coupon Set exists but is incorrectly linked and failed to recreate: ' . $deleteEx->getMessage(),
                                ], 500);
                            }
                        }
                    }

                } catch (\Exception $ex) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Coupon Set already exists but failed to retrieve it: ' . $ex->getMessage(),
                    ], 500);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'ChargeBee Coupon Set creation failed: ' . $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during coupon set operation: ' . $e->getMessage(),
            ], 500);
        }

        // Validate that couponSetResult contains the coupon_set model
        // Change from method call to property access:
        // Extract the CouponSet model from the result object for final validation and use
        $couponSetModel = $couponSetResult->coupon_set;
        dd($couponSetModel->coupon_id);

        // Final check of the coupon_id on the retrieved/created set's model
        if ($couponSetModel->coupon_id !== $templateId) {
             return response()->json([
                'success' => false,
                'message' => "ChargeBee Coupon Set '{$setId}' is not correctly linked to Coupon Template '{$templateId}'. Its coupon_id is actually '" . $couponSetModel->coupon_id . "'. This indicates a mismatch or a ChargeBee data inconsistency. Please check ChargeBee directly.",
            ], 500);
        }

      

        // 3. Create Individual Coupon Codes in ChargeBee and store in local DB
        $existingCodes = Coupon::where('code', 'LIKE', "{$prefix}-%")->pluck('code')->toArray();
        $insertPayload = [];

        for ($i = 1; count($insertPayload) < $count; $i++) {
            $code = "{$prefix}-{$i}";

            if (in_array($code, $existingCodes)) {
                continue;
            }

            try {
                CouponCode::create([
                    'coupon_set_id' => $setId,
                    'code'          => $code,
                ]);

                $insertPayload[] = [
                    'code'          => $code,
                    'type'          => $validated['type'],
                    'value'         => $validated['value'],
                    'usage_limit'   => 1,
                    'status'        => $validated['status'],
                    'expires_at'    => $validated['expires_at'],
                    'plan_id'       => $validated['plan_id'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create individual coupon code "' . $code . '": ' . $e->getMessage(),
                ], 500);
            }
        }

        if (!empty($insertPayload)) {
            Coupon::insert($insertPayload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupons created successfully',
            'data' => $insertPayload,
        ]);
    }



     public function index()
    {
        return view('admin.coupons.index');
    }




    
    public function couponsData(Request $request)
    {
        $coupons = Coupon::select([
            'id', 'code', 'type', 'value', 'usage_limit', 'used', 'status', 'expires_at'
        ]);

        return DataTables::of($coupons)
            ->addColumn('action', function ($coupon) {
                return view('admin.coupons.partials.actions', compact('coupon'))->render();
            })
            ->editColumn('type', function ($coupon) {
                return ucfirst($coupon->type);
            })
            ->make(true);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted successfully']);
    }

    public function plansList()
    {
        $plans = Plan::select('id', 'name')->where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

   
}












 
