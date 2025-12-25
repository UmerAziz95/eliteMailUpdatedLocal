<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmtpProviderController extends Controller
{
    /**
     * Get list of SMTP providers for dropdown (Select2 compatible format)
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $query = SmtpProvider::active()->orderBy('name');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $providers = $query->get(['id', 'name', 'url']);

        // Format for Select2
        $results = $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'text' => $provider->name,
                'url' => $provider->url,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Store a new SMTP provider
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $provider = SmtpProvider::create([
            'name' => $request->name,
            'url' => $request->url,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider created successfully',
            'provider' => [
                'id' => $provider->id,
                'text' => $provider->name,
                'url' => $provider->url,
            ]
        ]);
    }

    /**
     * Get all providers for management page
     */
    public function all()
    {
        $providers = SmtpProvider::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'providers' => $providers
        ]);
    }

    /**
     * Update SMTP provider
     */
    public function update(Request $request, SmtpProvider $smtpProvider)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $smtpProvider->update($request->only(['name', 'url', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider updated successfully',
            'provider' => $smtpProvider
        ]);
    }

    /**
     * Delete SMTP provider
     */
    public function destroy(SmtpProvider $smtpProvider)
    {
        // Check if provider is being used
        $poolCount = $smtpProvider->pools()->count();

        if ($poolCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete provider. It is being used by {$poolCount} pool(s).",
            ], 422);
        }

        $smtpProvider->delete();

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider deleted successfully'
        ]);
    }
}
