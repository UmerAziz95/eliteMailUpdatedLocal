<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaticLinkController extends Controller
{
    public function generateStaticLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'master_plan_id' => 'required|integer',
            'chargebee_plan_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $masterPlanId = $request->master_plan_id;
            $chargebeePlanId = $request->chargebee_plan_id;
            
            // Create the encrypted link data
            $linkData = [
                'type' => 'static_plan',
                'master_plan_id' => $masterPlanId,
                'chargebee_plan_id' => $chargebeePlanId,
                // 'generated_at' => now()->toISOString(),
                'generated_by' => auth()->id()
            ];

            // Create a simple encrypted URL parameter
            $encryptedData = encrypt(json_encode($linkData));
            $staticLink = url("/static-link?data=" . urlencode($encryptedData));

            return response()->json([
                'success' => true,
                'link' => $staticLink,
                'master_plan_id' => $masterPlanId,
                'chargebee_plan_id' => $chargebeePlanId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate static link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate static link for pool plans
     */
    public function generatePoolStaticLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pool_plan_id' => 'required|integer',
            'chargebee_plan_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $poolPlanId = $request->pool_plan_id;
            $chargebeePlanId = $request->chargebee_plan_id;
            
            // Create the encrypted link data for pool plans
            $linkData = [
                'type' => 'pool_static_plan',
                'pool_plan_id' => $poolPlanId,
                'chargebee_plan_id' => $chargebeePlanId,
                // 'generated_at' => now()->toISOString(),
                'generated_by' => auth()->id(),
            ];

            // Create a simple encrypted URL parameter
            $encryptedData = encrypt(json_encode($linkData));
            $staticLink = url("/static-link?data=" . urlencode($encryptedData));

            return response()->json([
                'success' => true,
                'link' => $staticLink,
                'pool_plan_id' => $poolPlanId,
                'chargebee_plan_id' => $chargebeePlanId,
                'link_type' => 'pool_static_plan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate pool static link: ' . $e->getMessage()
            ], 500);
        }
    }
}
