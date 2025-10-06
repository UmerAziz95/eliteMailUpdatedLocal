<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PoolPlan;
use App\Models\PoolOrder;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PoolInvoice;
use Illuminate\Http\Request;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoolPlanController extends Controller
{
    /**
     * Handle successful pool plan subscription
     */
    public function subscriptionSuccess(Request $request)
    {
        try {
            $hostedPageId = $request->query('id');
            
            if (!$hostedPageId) {
                return redirect()->route('login')->withErrors(['error' => 'Invalid subscription response']);
            }

            // Retrieve the hosted page from ChargeBee
            $result = HostedPage::retrieve($hostedPageId);
            $hostedPage = $result->hostedPage();

            if ($hostedPage->state !== 'succeeded') {
                return redirect()->route('customer.pool-subscription.cancel')
                    ->withErrors(['error' => 'Subscription was not completed successfully']);
            }

            // Get subscription and invoice data
            $subscription = $hostedPage->content()->subscription();
            $customer = $hostedPage->content()->customer();
            $invoice = $hostedPage->content()->invoice();
        
            // Get authenticated user
            $user = Auth::user();

            // First check if user exists in system by email, if not logged in
            if (!$user) {
                $existingUser = User::where('email', $customer->email)->first();
                if ($existingUser) {
                    // Log in the existing user
                    Auth::login($existingUser);
                    $user = $existingUser;
                } else {
                    Log::warning('Pool subscription - User not found', [
                        'chargebee_email' => $customer->email
                    ]);
                    return redirect()->route('login')->withErrors(['error' => 'User account not found. Please register first.']);
                }
            } else {
                // Security check: Verify customer email matches authenticated user
                if ($customer->email !== $user->email) {
                    Log::warning('Pool subscription security mismatch', [
                        'chargebee_email' => $customer->email,
                        'user_email' => $user->email,
                        'user_id' => $user->id
                    ]);
                    return redirect()->route('login')->withErrors(['error' => 'Security verification failed']);
                }
            }
            // dd($subscription, $customer, $invoice, $user);
            // Get pool plan from ChargeBee subscription plan ID
            $subscriptionItems = $subscription->subscriptionItems;
            $chargebeePlanId = $subscriptionItems[0]->itemPriceId ?? null;
            $quantity = $subscriptionItems[0]->quantity ?? 1;
            $poolPlan = PoolPlan::where('chargebee_plan_id', $chargebeePlanId)->first();
            
            if (!$poolPlan) {
                Log::error('Pool plan not found for ChargeBee plan ID: ' . $chargebeePlanId);
                return redirect()->route('login')->withErrors(['error' => 'Pool plan not found']);
            }

            // Update user's ChargeBee customer ID if not set
            if (!$user->chargebee_customer_id) {
                $user->update(['chargebee_customer_id' => $customer->id]);
            }

            // Create or update pool order
            $poolOrder = $this->createPoolOrder($subscription, $customer, $invoice, $poolPlan, $user);

            // Create invoice record
            $poolInvoice = $this->createInvoiceRecord($invoice, $poolOrder, $user);

            // Send notification emails
            $this->sendPoolOrderNotifications($poolOrder, $user);

            return view('customer.pool-plans.subscription-success', compact('poolOrder', 'poolPlan', 'user', 'poolInvoice'));

        } catch (\Exception $e) {
            Log::error('Pool Subscription Success Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'error' => 'Failed to process pool subscription: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle cancelled pool plan subscription
     */
    public function subscriptionCancel(Request $request)
    {
        // Clear static plan session data if exists
        session()->forget(['pool_static_plan_data', 'static_type']);

        return view('customer.pool-plans.subscription-cancel');
    }

    /**
     * Create pool order record
     */
    private function createPoolOrder($subscription, $customer, $invoice, $poolPlan, $user)
    {
        // Check if order already exists
        $existingOrder = PoolOrder::where('chargebee_subscription_id', $subscription->id)->first();
        
        if ($existingOrder) {
            return $existingOrder;
        }

        // Extract quantity from subscription items
        $quantity = 1; // Default quantity
        if (isset($subscription->subscriptionItems) && is_array($subscription->subscriptionItems)) {
            foreach ($subscription->subscriptionItems as $item) {
                if (isset($item->quantity)) {
                    $quantity = $item->quantity;
                    break; // Use the first item's quantity
                }
            }
        }

        // Create new pool order
        $poolOrder = PoolOrder::create([
            'user_id' => $user->id,
            'pool_plan_id' => $poolPlan->id,
            'quantity' => $quantity,
            'chargebee_subscription_id' => $subscription->id,
            'chargebee_customer_id' => $customer->id,
            'chargebee_invoice_id' => $invoice->id,
            'amount' => $invoice->total / 100, // Convert cents to dollars
            'currency' => $invoice->currencyCode,
            'status' => 'completed',
            'status_manage_by_admin' => 'warming', // Default status
            'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
            'meta' => json_encode([
                'subscription_data' => [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->planId,
                    'status' => $subscription->status,
                    'current_term_start' => $subscription->currentTermStart,
                    'current_term_end' => $subscription->currentTermEnd,
                    'trial_end' => $subscription->trialEnd ?? null,
                    'billing_period' => $subscription->billingPeriod ?? null,
                    'billing_period_unit' => $subscription->billingPeriodUnit ?? null,
                ],
                'customer_data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'first_name' => $customer->firstName,
                    'last_name' => $customer->lastName ?? '',
                ],
                'invoice_data' => [
                    'id' => $invoice->id,
                    'total' => $invoice->total,
                    'amount_paid' => $invoice->amountPaid,
                    'currency_code' => $invoice->currencyCode,
                    'status' => $invoice->status,
                    'paid_at' => $invoice->paidAt,
                ],
                'order_details' => [
                    'quantity' => $quantity,
                    'unit_price' => ($invoice->total / 100) / $quantity, // Calculate unit price
                ],
                'pool_plan_type' => 'static_link_subscription',
                'independent_from_master' => true
            ])
        ]);

        return $poolOrder;
    }

    /**
     * Create pool invoice record
     */
    private function createInvoiceRecord($invoice, $poolOrder, $user)
    {
        // Check if pool invoice already exists
        $existingInvoice = PoolInvoice::where('chargebee_invoice_id', $invoice->id)->first();
        
        if (!$existingInvoice) {
            $invoiceRecord = PoolInvoice::create([
                'user_id' => $user->id,
                'pool_order_id' => $poolOrder->id, // Pool order ID
                'chargebee_invoice_id' => $invoice->id,
                'chargebee_customer_id' => $invoice->customerId,
                'amount' => $invoice->total / 100,
                'currency' => $invoice->currencyCode,
                'status' => $invoice->status,
                'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                'meta' => [
                    'invoice_data' => [
                        'id' => $invoice->id,
                        'total' => $invoice->total,
                        'amount_paid' => $invoice->amountPaid,
                        'currency_code' => $invoice->currencyCode,
                        'status' => $invoice->status,
                        'paid_at' => $invoice->paidAt,
                    ]
                ]
            ]);

            return $invoiceRecord;
        }

        return $existingInvoice;
    }

    /**
     * Send pool order notification emails
     */
    private function sendPoolOrderNotifications($poolOrder, $user)
    {
        try {
            // Send order confirmation email to user
            // Mail::to($user->email)->send(new PoolOrderCreatedMail($poolOrder, $user));

            // Send admin notification
            // $adminEmail = config('mail.admin_email', 'admin@example.com');
            // Mail::to($adminEmail)->send(new AdminPoolOrderNotificationMail($poolOrder, $user));

            Log::info('Pool order notifications sent', [
                'pool_order_id' => $poolOrder->id,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send pool order notifications: ' . $e->getMessage());
        }
    }

    /**
     * List pool orders for a user
     */
    public function myPoolOrders()
    {
        $user = Auth::user();
        $poolOrders = PoolOrder::where('user_id', $user->id)
            ->with(['poolPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.pool-orders.index', compact('poolOrders'));
    }

    /**
     * Show specific pool order details
     */
    public function showPoolOrder($id)
    {
        $user = Auth::user();
        $poolOrder = PoolOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['poolPlan', 'poolInvoices'])
            ->firstOrFail();

        return view('customer.pool-orders.show', compact('poolOrder'));
    }
}
