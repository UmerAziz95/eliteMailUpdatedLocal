<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Validator;

class AdminOrderEmailController extends Controller
{
    public function getEmails($orderId)
    {
        try {
            $emails = OrderEmail::where('order_id', $orderId)->get();

            return response()->json([
                'success' => true,
                'data' => $emails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching emails: ' . $e->getMessage()
            ], 500);
        }
    }
}
