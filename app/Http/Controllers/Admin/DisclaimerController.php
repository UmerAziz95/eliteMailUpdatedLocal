<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Disclaimer;

class DisclaimerController extends Controller
{
    /**
     * Display the disclaimers page
     */
    public function index()
    {
        $types = Disclaimer::getTypes();
        
        // Get existing disclaimers for each type
        $disclaimers = [];
        foreach ($types as $key => $label) {
            $disclaimer = Disclaimer::where('type', $key)->first();
            $disclaimers[$key] = $disclaimer;
        }
        
        return view('admin.disclaimers.index', compact('types', 'disclaimers'));
    }
    
    /**
     * Store or update disclaimer
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(Disclaimer::getTypes())),
            'content' => 'required|string|max:5000',
            'status' => 'nullable|in:on,off,true,false,1,0'
        ], [
            'type.in' => 'Invalid disclaimer type selected.',
            'content.required' => 'Disclaimer content is required.',
            'content.max' => 'Disclaimer content cannot exceed 5000 characters.'
        ]);
        
        try {
            // Convert status to boolean
            $status = false;
            if ($request->has('status')) {
                $statusValue = $request->status;
                $status = in_array($statusValue, ['on', 'true', '1', true], true);
            }
            
            $disclaimer = Disclaimer::updateOrCreate(
                ['type' => $request->type],
                [
                    'content' => $request->content,
                    'status' => $status
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Disclaimer saved successfully!',
                'data' => $disclaimer->fresh()
            ]);
        } catch (\Exception $e) {
            \Log::error('Disclaimer save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save disclaimer. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get disclaimer by type
     */
    public function show($type)
    {
        try {
            $disclaimer = Disclaimer::where('type', $type)->first();
            
            if (!$disclaimer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disclaimer not found.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $disclaimer
            ]);
        } catch (\Exception $e) {
            \Log::error('Disclaimer fetch error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disclaimer.'
            ], 500);
        }
    }
    
    /**
     * Update disclaimer status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $disclaimer = Disclaimer::findOrFail($id);
            
            $status = $request->has('status') && in_array($request->status, ['on', 'true', '1', true], true);
            
            $disclaimer->update(['status' => $status]);
            
            $typeLabel = Disclaimer::getTypes()[$disclaimer->type] ?? $disclaimer->type;
            
            return response()->json([
                'success' => true,
                'message' => "Disclaimer for '{$typeLabel}' has been " . ($status ? 'activated' : 'deactivated') . "!",
                'data' => $disclaimer->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Disclaimer not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Disclaimer status update error: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Delete disclaimer
     */
    public function destroy($id)
    {
        try {
            $disclaimer = Disclaimer::findOrFail($id);
            $typeLabel = Disclaimer::getTypes()[$disclaimer->type] ?? $disclaimer->type;
            
            $disclaimer->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Disclaimer for '{$typeLabel}' has been deleted successfully!"
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Disclaimer not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Disclaimer delete error: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete disclaimer. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get active disclaimer for public use
     */
    public function getActiveDisclaimer($type)
    {
        try {
            $disclaimer = Disclaimer::getActiveByType($type);
            
            if (!$disclaimer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active disclaimer found.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'content' => $disclaimer->content,
                    'type' => $disclaimer->type
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Active disclaimer fetch error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disclaimer.'
            ], 500);
        }
    }
}
