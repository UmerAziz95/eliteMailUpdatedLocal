<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use Illuminate\Support\Facades\Response;
class OrderEmailController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'emails' => 'required|array',
            'emails.*.name' => 'required|string|max:255',
            'emails.*.email' => 'required|email|max:255',
            'emails.*.password' => 'required|string|min:6',
        ],
        [
            'order_id.required' => 'The order ID is required.',
            'order_id.exists' => 'The selected order ID is invalid.',
            'emails.required' => 'At least one email is required.',
            'emails.array' => 'Emails must be an array.',
            'emails.*.name.required' => 'Name is required for each email.',
            'emails.*.email.required' => 'Email is required for each email.',
            'emails.*.email.email' => 'Email must be a valid email address.',
            'emails.*.password.required' => 'Password is required for each email.',
            'emails.*.password.min' => 'Password must be at least 6 characters.',
            'emails.*.name.string' => 'Name must be a string.',
            'emails.*.name.max' => 'Name may not be greater than 255 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Delete existing emails for the order in one go
            OrderEmail::where('order_id', $request->order_id)->delete();

            // Create new emails
            $emails = collect($request->emails)->map(function ($emailData) use ($request) {
                return OrderEmail::create([
                    'order_id' => $request->order_id,
                    'user_id' => auth()->id(),
                    'name' => $emailData['name'],
                    'email' => $emailData['email'],
                    'password' => $emailData['password'],
                    'profile_picture' => $emailData['profile_picture'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Emails updated successfully',
                'data' => $emails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating emails: ' . $e->getMessage()
            ], 500);
        }
    }


    public function delete(Request $request, $id)
    {
        try {
            $email = OrderEmail::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $email->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmails($orderId)
    {
        try {
            $emails = OrderEmail::where('order_id', $orderId)
                ->where('user_id', auth()->id())
                ->get();

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






    public function exportCsv($orderId)
    {
        $order = Order::findOrFail($orderId);
    
        $emails = OrderEmail::where('order_id', $order->id)->get(['name', 'email', 'password']);
    
        if ($emails->isEmpty()) {
            return back()->with('error', 'No email records found for this order.');
        }
    
        $filename = "order_{$order->id}_emails.csv";
    
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
    
        $callback = function () use ($emails) {
            $file = fopen('php://output', 'w');
    
            // Add CSV header
            fputcsv($file, ['Name', 'Email', 'Password']);
    
            // Add data rows
            foreach ($emails as $email) {
                fputcsv($file, [$email->name, $email->email, $email->password]);
            }
    
            fclose($file);
        };
    
        return Response::stream($callback, 200, $headers);
    }
}
