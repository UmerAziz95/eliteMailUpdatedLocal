<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortEncryptedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

            // Generate a unique slug
            $slug = Str::random(16);
            
            // Ensure slug is unique
            while (ShortEncryptedLink::where('slug', $slug)->exists()) {
                $slug = Str::random(16);
            }

            // Create the encrypted link data
            $linkData = [
                'type' => 'static_plan',
                'master_plan_id' => $masterPlanId,
                'chargebee_plan_id' => $chargebeePlanId,
                'generated_at' => now()->toISOString(),
                'generated_by' => auth()->id()
            ];

            // Create the short encrypted link
            $shortLink = ShortEncryptedLink::create([
                'slug' => $slug,
                'encrypted_url' => encrypt(json_encode($linkData)),
            ]);

            $staticLink = url("/go/{$slug}");

            return response()->json([
                'success' => true,
                'link' => $staticLink,
                'slug' => $slug,
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
}
