<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use App\Models\OrderEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Services\ActivityLogService;
use App\Models\Notification;

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
            $user_id = Order::where('id', $request->order_id)->value('user_id');
            // dd($user_id);
            if (!$user_id) {
                return response()->json(['error' => 'User not found for the given order ID'], 404);
            }
            
            // Create new emails
            $emails = collect($request->emails)->map(function ($emailData) use ($request, $user_id) {
                return OrderEmail::create([
                    'order_id' => $request->order_id,
                    'user_id' => $user_id,
                    'name' => $emailData['name'],
                    'email' => $emailData['email'],
                    'password' => $emailData['password'],
                    'profile_picture' => $emailData['profile_picture'] ?? null,
                ]);
            });
            $_temp = Order::where('id', $request->order_id)->first();

            // Create notification for customer
            Notification::create([
                'user_id' => $user_id, // customer
                'type' => 'email_created',
                'title' => 'New Email Accounts Created',
                'message' => 'New email accounts have been created for your order #' . $request->order_id,
                'data' => [
                    'order_id' => $request->order_id,
                    'email_count' => count($request->emails)
                ]
            ]);

            // Create notification for contractor
            Notification::create([
                'user_id' => auth()->id(), // contractor
                'type' => 'email_created',
                'title' => 'Email Accounts Created',
                'message' => 'You have created new email accounts for order #' . $request->order_id,
                'data' => [
                    'order_id' => $request->order_id,
                    'email_count' => count($request->emails)
                ]
            ]);

            // Create a new activity log using the custom log service
            ActivityLogService::log(
                'contractor-order-email-create',
                'Order email created : ' . $request->order_id,
                $_temp,
                [
                    'order_id' => $request->order_id,
                    'emails' => $emails,
                    'created_by' => auth()->id()
                ]
            );

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
}
