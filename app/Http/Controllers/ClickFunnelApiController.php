<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClickFunnelApiController extends Controller
{
    
    public function getContactDetailByEmailFromActiveCampaignCrm(Request $request)
    {
        $email = $request->query('email');
    
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required'
            ], 400);
        }
    
        $baseUrl = 'https://gregbizops46515.api-us1.com/api/3';
    
        $headers = [
            'Api-Token' => env('ACTIVECAMPAIGN_API_KEY'),
            'Accept' => 'application/json',
        ];
    
        // 1. Get contact
        $contactResponse = Http::withoutVerifying()
            ->withHeaders($headers)
            ->get($baseUrl . '/contacts', [
                'email' => $email
            ]);
    
        // fallback search
        if ($contactResponse->failed()) {
            $contactResponse = Http::withoutVerifying()
                ->withHeaders($headers)
                ->get($baseUrl . '/contacts', [
                    'search' => $email
                ]);
        }
    
        if ($contactResponse->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'ActiveCampaign API failed',
                'status' => $contactResponse->status(),
                'response' => $contactResponse->body()
            ], $contactResponse->status());
        }
    
        $contacts = $contactResponse->json('contacts') ?? [];
    
        if (empty($contacts)) {
            return response()->json([
                'success' => true,
                'found' => false,
                'count' => 0,
                'contacts' => []
            ]);
        }
    
        // 2. Map field IDs (from your confirmed response)
        $fieldMap = [
            '70' => 'zoomLink',          // ✅ MAIN ZOOM LINK
            '71' => 'webinarDate',
            '73' => 'qaZoomLinkActive',
            '75' => 'qaZoomLinkPending',
        ];
    
        // 3. Attach field values
        $contactsWithData = collect($contacts)->map(function ($contact) use ($baseUrl, $headers, $fieldMap) {
    
            $contactId = $contact['id'] ?? null;
    
            // default values
            foreach ($fieldMap as $key) {
                $contact[$key] = null;
            }
    
            if (!$contactId) {
                return $contact;
            }
    
            $fieldValuesResponse = Http::withoutVerifying()
                ->withHeaders($headers)
                ->get($baseUrl . "/contacts/{$contactId}/fieldValues");
    
            if ($fieldValuesResponse->successful()) {
                $fieldValues = collect($fieldValuesResponse->json('fieldValues') ?? []);
    
                foreach ($fieldMap as $fieldId => $key) {
                    $value = $fieldValues->first(function ($item) use ($fieldId) {
                        return (string) ($item['field'] ?? '') === (string) $fieldId;
                    });
    
                    $contact[$key] = $value['value'] ?? null;
                }
            }
    
            return $contact;
        })->values();
    
        return response()->json([
            'success' => true,
            'found' => $contactsWithData->count() > 0,
            'count' => $contactsWithData->count(),
            'contacts' => $contactsWithData
        ]);
    }
}
