<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\ReorderInfo;
use App\Models\InternalOrder;
use App\Models\HostingPlatform;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use App\Models\Log as ModelLog;
use Illuminate\Support\Facades\DB;
use App\Models\Status;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class InternalOrderManagerController extends Controller
{

     private $statuses;
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];
    // split statues
    private $splitStatuses = [
        'completed' => 'success',
        // 'unallocated' => 'warning',
        // 'allocated' => 'info',
        'rejected' => 'danger',
        'in-progress' => 'primary',
        // 'pending' => 'secondary'
    ];

    public function __construct()
    {
        $this->statuses = Status::pluck('badge', 'name')->toArray();
    }

        public function index()
    {
        $plans = Plan::all();
        $userId = auth()->id();
        $orders = InternalOrder::all(); // Changed from Order to InternalOrder
        $statuses = $this->statuses;

        $totalOrders = $orders->count();

        $pendingOrders = $orders->where('status_manage_by_admin', 'pending')->count();
        $rejectOrders = $orders->where('status_manage_by_admin', 'reject')->count();
        $inProgressOrders = $orders->where('status_manage_by_admin', 'in-progress')->count();
        $cancelledOrders = $orders->where('status_manage_by_admin', 'cancelled')->count();
        $completedOrders = $orders->where('status_manage_by_admin', 'completed')->count();
        $draftOrders = $orders->where('status_manage_by_admin', 'draft')->count();
        $expiredOrders = $orders->where('status_manage_by_admin', 'expired')->count();

        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = $orders->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        return view('admin.internal_order_manager.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'rejectOrders',
            'inProgressOrders',
            'cancelledOrders',
            'completedOrders',
            'draftOrders',
            'expiredOrders',
            'percentageChange',
            'statuses'
        ));
    }

    public function newOrder(Request $request, $id = null){
        // Check if we're editing an existing internal order
        $internalOrder = null;
        if ($id) {
            $internalOrder = InternalOrder::with(['plan'])->findOrFail($id);
        }
        // dd($internalOrder);
        // $plan = \App\Models\Plan::first();
        $plan = $internalOrder->plan ?? \App\Models\Plan::first();
        $hostingPlatforms = \App\Models\HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();
        
        // Pass internal order instead of regular order
        return view('admin.internal_order_manager.edit-order', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'internalOrder'));
    }
    

    public function store(Request $request)
    {
       
        try {
            // Validate the request data
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'plan_id' => 'required|exists:plans,id',
                'forwarding_url' => 'required|string|max:255',
                'hosting_platform' => 'required|string|max:50',
                'other_platform' => 'nullable|required_if:hosting_platform,other|string|max:50',
                'platform_login' => 'nullable|string|max:255',
                'platform_password' => 'nullable|string|min:3',
                'backup_codes' => 'required_if:hosting_platform,namecheap|string',
                'domains' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $domains = array_filter(preg_split('/[\r\n,]+/', $value));
                        if (count($domains) !== count(array_unique($domains))) {
                            $fail('Duplicate domains are not allowed.');
                        }
                        foreach ($domains as $domain) {
                            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/', trim($domain))) {
                                $fail('Invalid domain format: ' . trim($domain));
                            }
                        }
                    }
                ],
                'sending_platform' => 'required|string|max:50',
                'sequencer_login' => 'required|email|max:255',
                'sequencer_password' => 'required|string|min:3',
                'total_inboxes' => 'required|integer|min:1',
                'inboxes_per_domain' => 'required|integer|min:1|max:3',
                'prefix_variants' => 'required|array|min:1',
                'prefix_variants.prefix_variant_1' => 'required|string|max:50',
                'prefix_variants.prefix_variant_2' => 'nullable|string|max:50',
                'prefix_variants.prefix_variant_3' => 'nullable|string|max:50',
                'prefix_variants_details' => 'required|array|min:1',
                'prefix_variants_details.prefix_variant_1.first_name' => 'required|string|max:50',
                'prefix_variants_details.prefix_variant_1.last_name' => 'required|string|max:50',
                'prefix_variants_details.prefix_variant_1.profile_link' => 'nullable|url|max:255',
                'prefix_variants_details.prefix_variant_2.first_name' => 'nullable|string|max:50',
                'prefix_variants_details.prefix_variant_2.last_name' => 'nullable|string|max:50',
                'prefix_variants_details.prefix_variant_2.profile_link' => 'nullable|url|max:255',
                'prefix_variants_details.prefix_variant_3.first_name' => 'nullable|string|max:50',
                'prefix_variants_details.prefix_variant_3.last_name' => 'nullable|string|max:50',
                'prefix_variants_details.prefix_variant_3.profile_link' => 'nullable|url|max:255',
                'profile_picture_link' => 'nullable|url|max:255',
                'email_persona_picture_link' => 'nullable|url|max:255',
                'master_inbox_email' => 'nullable|email|max:255',
                'additional_info' => 'nullable|string',
                'coupon_code' => 'nullable|string|max:50'
            ], [
                'other_platform.required_if' => 'Please specify the hosting platform when selecting "Other" option',
                'backup_codes.required_if' => 'Backup codes are required when using Namecheap',
                'inboxes_per_domain.max' => 'You can have maximum 3 inboxes per domain',
                'sequencer_login.email' => 'Sequencer login must be a valid email address',
                'master_inbox_email.email' => 'Master inbox email must be a valid email address',
                'profile_picture_link.url' => 'Profile picture link must be a valid URL',
                'email_persona_picture_link.url' => 'Email persona picture link must be a valid URL'
            ]);
            
            // Additional validation for prefix variants based on inboxes_per_domain
            $inboxesPerDomain = (int) $request->inboxes_per_domain;
            $prefixVariants = $request->prefix_variants ?? [];
            $prefixVariantsDetails = $request->prefix_variants_details ?? [];
            
            // Validate required prefix variants based on inboxes_per_domain
            for ($i = 1; $i <= $inboxesPerDomain; $i++) {
                $prefixKey = "prefix_variant_{$i}";
                if ($i === 1 && empty($prefixVariants[$prefixKey])) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['prefix_variants.prefix_variant_1' => ['The first prefix variant is required.']]
                    ], 422);
                }
                
                // Validate format if value exists
                if (!empty($prefixVariants[$prefixKey])) {
                    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $prefixVariants[$prefixKey])) {
                        return response()->json([
                            'success' => false,
                            'errors' => ["prefix_variants.{$prefixKey}" => ['Only letters, numbers, dots, hyphens and underscores are allowed.']]
                        ], 422);
                    }
                }
                
                // Validate prefix variants details for required variants
                if (!empty($prefixVariants[$prefixKey])) {
                    if (empty($prefixVariantsDetails[$prefixKey]['first_name'])) {
                        return response()->json([
                            'success' => false,
                            'errors' => ["prefix_variants_details.{$prefixKey}.first_name" => ['First name is required for this prefix variant.']]
                        ], 422);
                    }
                    
                    if (empty($prefixVariantsDetails[$prefixKey]['last_name'])) {
                        return response()->json([
                            'success' => false,
                            'errors' => ["prefix_variants_details.{$prefixKey}.last_name" => ['Last name is required for this prefix variant.']]
                        ], 422);
                    }
                }
            }
            
            $status = 'pending'; // Default status for new orders
            // persona_password set 123
            $request->persona_password = '123';
            
            // Calculate number of domains and total inboxes
            $domains = array_filter(preg_split('/[\r\n,]+/', $request->domains));
            $domainCount = count($domains);
            $calculatedTotalInboxes = $domainCount * $request->inboxes_per_domain;

            // Determine plan based on total inboxes using dynamic plan lookup
            try {
                $determinedPlanId = $this->determinePlanByInboxes($calculatedTotalInboxes);
            } catch (\Exception $e) {
                Log::error('Failed to determine plan by inboxes: ' . $e->getMessage(), [
                    'total_inboxes' => $calculatedTotalInboxes,
                    'domain_count' => $domainCount,
                    'inboxes_per_domain' => $request->inboxes_per_domain
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to determine appropriate plan: ' . $e->getMessage()
                ], 422);
            }

            // Override the plan_id from request with the determined plan
            $request->merge(['plan_id' => $determinedPlanId]);

            // Get requested plan
            $plan = Plan::findOrFail($determinedPlanId);
            
            Log::info('Plan determined successfully', [
                'total_inboxes' => $calculatedTotalInboxes,
                'determined_plan_id' => $determinedPlanId,
                'plan_name' => $plan->name,
                'plan_range' => $plan->min_inbox . '-' . ($plan->max_inbox == 0 ? 'unlimited' : $plan->max_inbox)
            ]);
            
            $message = 'Order information saved successfully.';
            
            // Check if internal_order_id exists - if yes, edit; if no, create
            if($request->internal_order_id){
                // Find existing internal order
                $existingInternalOrder = InternalOrder::find($request->internal_order_id);
                
                if (!$existingInternalOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Internal order not found.'
                    ], 404);
                }
                
                $TOTAL_INBOXES = $existingInternalOrder->total_inboxes;
                // If total_inboxes is 0, use the calculated total
                $TOTAL_INBOXES = 0;
                
                // Validate against internal order's total_inboxes limit
                if ($TOTAL_INBOXES > 0 && $calculatedTotalInboxes > $TOTAL_INBOXES) {
                    return response()->json([
                        'success' => false,
                        'message' => "Order Limit Exceeded! You have {$calculatedTotalInboxes} inboxes but this order supports only {$TOTAL_INBOXES} inboxes.",
                        'errors' => [
                            'domains' => [
                                "Order Limit Exceeded! You have {$calculatedTotalInboxes} inboxes but this order supports only {$TOTAL_INBOXES} inboxes."
                            ]
                        ]
                    ], 422);
                }
                
                $is_draft = $request->is_draft ?? 0;
                $status = $is_draft == 1 ? 'draft' : 'pending';

                // Update existing internal order
                $existingInternalOrder->update([
                    'user_id' => $request->user_id,
                    'plan_id' => $determinedPlanId,
                    'amount' => 0,
                    'status' => 'pending',
                    'status_manage_by_admin' => $status,
                    'currency' => 'USD',
                    'forwarding_url' => $request->forwarding_url,
                    'hosting_platform' => $request->hosting_platform,
                    'other_platform' => $request->other_platform,
                    'bison_url' => $request->bison_url,
                    'bison_workspace' => $request->bison_workspace,
                    'backup_codes' => $request->backup_codes,
                    'platform_login' => $request->platform_login,
                    'platform_password' => $request->platform_password,
                    'domains' => implode(',', array_filter($domains)),
                    'sending_platform' => $request->sending_platform,
                    'sequencer_login' => $request->sequencer_login,
                    'sequencer_password' => $request->sequencer_password,
                    'total_inboxes' => $is_draft == 1 ? $TOTAL_INBOXES : $calculatedTotalInboxes,
                    'initial_total_inboxes' => $existingInternalOrder->initial_total_inboxes == 0 ? $TOTAL_INBOXES : $existingInternalOrder->initial_total_inboxes,
                    'inboxes_per_domain' => $request->inboxes_per_domain,
                    'first_name' => isset($request->prefix_variants_details['prefix_variant_1']['first_name']) ? $request->prefix_variants_details['prefix_variant_1']['first_name'] : null,
                    'last_name' => isset($request->prefix_variants_details['prefix_variant_1']['last_name']) ? $request->prefix_variants_details['prefix_variant_1']['last_name'] : null,
                    'prefix_variant_1' => isset($request->prefix_variants['prefix_variant_1']) ? $request->prefix_variants['prefix_variant_1'] : null,
                    'prefix_variant_2' => isset($request->prefix_variants['prefix_variant_2']) ? $request->prefix_variants['prefix_variant_2'] : null,
                    'prefix_variants' => $request->prefix_variants,
                    'prefix_variants_details' => $request->prefix_variants_details,
                    'persona_password' => $request->persona_password,
                    'profile_picture_link' => $request->profile_picture_link,
                    'email_persona_password' => '123',
                    'email_persona_picture_link' => $request->email_persona_picture_link,
                    'master_inbox_email' => $request->master_inbox_email,
                    'additional_info' => $request->additional_info,
                    'coupon_code' => $request->coupon_code,
                    'tutorial_section' => $request->tutorial_section,
                ]);

                $internalOrder = $existingInternalOrder;
                $message = 'Internal order updated successfully.';

                // Update or create related Order record
                $order = Order::where('internal_order_id', $internalOrder->id)->first();
                if ($order) {
                    // Update existing order
                    $order->update([
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'amount' => $plan->price ?? 0,
                        'status' => 'pending',
                        'status_manage_by_admin' => $status,
                        'currency' => 'USD',
                        'payment_method' => 'internal',
                        'payment_status' => 'paid',
                    ]);
                } else {
                    // Create new order if it doesn't exist
                    $order = Order::create([
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'amount' => $plan->price ?? 0,
                        'status' => 'pending',
                        'status_manage_by_admin' => $status,
                        'currency' => 'USD',
                        'payment_method' => 'internal',
                        'payment_status' => 'paid',
                        'internal_order_id' => $internalOrder->id,
                    ]);
                }

                // Update or create related ReorderInfo record
                $reorderInfo = ReorderInfo::where('order_id', $order->id)->first();
                if ($reorderInfo) {
                    // Update existing reorder info
                    $reorderInfo->update([
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'forwarding_url' => $request->forwarding_url,
                        'hosting_platform' => $request->hosting_platform,
                        'other_platform' => $request->other_platform,
                        'bison_url' => $request->bison_url,
                        'bison_workspace' => $request->bison_workspace,
                        'backup_codes' => $request->backup_codes,
                        'platform_login' => $request->platform_login,
                        'platform_password' => $request->platform_password,
                        'domains' => implode("\n", array_filter($domains)),
                        'sending_platform' => $request->sending_platform,
                        'sequencer_login' => $request->sequencer_login,
                        'sequencer_password' => $request->sequencer_password,
                        'total_inboxes' => $is_draft == 1 ? $TOTAL_INBOXES : $calculatedTotalInboxes,
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'first_name' => isset($request->prefix_variants_details['prefix_variant_1']['first_name']) ? $request->prefix_variants_details['prefix_variant_1']['first_name'] : null,
                        'last_name' => isset($request->prefix_variants_details['prefix_variant_1']['last_name']) ? $request->prefix_variants_details['prefix_variant_1']['last_name'] : null,
                        'prefix_variant_1' => isset($request->prefix_variants['prefix_variant_1']) ? $request->prefix_variants['prefix_variant_1'] : null,
                        'prefix_variant_2' => isset($request->prefix_variants['prefix_variant_2']) ? $request->prefix_variants['prefix_variant_2'] : null,
                        'prefix_variants' => json_encode($request->prefix_variants),
                        'prefix_variants_details' => json_encode($request->prefix_variants_details),
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => '123',
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                        'tutorial_section' => $request->tutorial_section,
                    ]);
                } else {
                    // Create new reorder info if it doesn't exist
                    $reorderInfo = ReorderInfo::create([
                        'order_id' => $order->id,
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'forwarding_url' => $request->forwarding_url,
                        'hosting_platform' => $request->hosting_platform,
                        'other_platform' => $request->other_platform,
                        'bison_url' => $request->bison_url,
                        'bison_workspace' => $request->bison_workspace,
                        'backup_codes' => $request->backup_codes,
                        'platform_login' => $request->platform_login,
                        'platform_password' => $request->platform_password,
                        'domains' => implode("\n", array_filter($domains)),
                        'sending_platform' => $request->sending_platform,
                        'sequencer_login' => $request->sequencer_login,
                        'sequencer_password' => $request->sequencer_password,
                        'total_inboxes' => $is_draft == 1 ? $TOTAL_INBOXES : $calculatedTotalInboxes,
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'first_name' => isset($request->prefix_variants_details['prefix_variant_1']['first_name']) ? $request->prefix_variants_details['prefix_variant_1']['first_name'] : null,
                        'last_name' => isset($request->prefix_variants_details['prefix_variant_1']['last_name']) ? $request->prefix_variants_details['prefix_variant_1']['last_name'] : null,
                        'prefix_variant_1' => isset($request->prefix_variants['prefix_variant_1']) ? $request->prefix_variants['prefix_variant_1'] : null,
                        'prefix_variant_2' => isset($request->prefix_variants['prefix_variant_2']) ? $request->prefix_variants['prefix_variant_2'] : null,
                        'prefix_variants' => json_encode($request->prefix_variants),
                        'prefix_variants_details' => json_encode($request->prefix_variants_details),
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => '123',
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                        'tutorial_section' => $request->tutorial_section,
                    ]);
                }

                $message = 'Internal order updated successfully with related data.';

                // Create a new activity log using the custom log service
                ActivityLogService::log(
                    'internal-order-update',
                    'Internal order updated: '. $internalOrder->id . ' with related Order #' . $order->id,
                    $internalOrder, 
                    [
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'total_inboxes' => $calculatedTotalInboxes,
                        'status' => $status,
                        'related_order_id' => $order->id,
                        'reorder_info_id' => $reorderInfo->id
                    ]
                );

                // Send email notifications for internal order updates (optional)
                try {
                    // Get user information
                    $user = User::findOrFail($request->user_id);
                    
                    Log::info('Internal order updated successfully', [
                        'internal_order_id' => $internalOrder->id,
                        'user_id' => $user->id,
                        'user_email' => $user->email
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to process internal order update notifications: ' . $e->getMessage());
                }
            } else {
                // Handle new internal order creation
                $is_draft = $request->is_draft ?? 0;
                $status = $is_draft == 1 ? 'draft' : 'pending';
                
                // Use database transaction to ensure data consistency
                DB::beginTransaction();
                try {
                    // Create InternalOrder record directly
                    $internalOrder = InternalOrder::create([
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'amount' => 0,
                        'status' => 'pending',
                        'status_manage_by_admin' => $status,
                        'currency' => 'USD',
                        'forwarding_url' => $request->forwarding_url,
                        'hosting_platform' => $request->hosting_platform,
                        'other_platform' => $request->other_platform,
                        'bison_url' => $request->bison_url,
                        'bison_workspace' => $request->bison_workspace,
                        'backup_codes' => $request->backup_codes,
                        'platform_login' => $request->platform_login,
                        'platform_password' => $request->platform_password,
                        'domains' => implode(',', array_filter($domains)),
                        'sending_platform' => $request->sending_platform,
                        'sequencer_login' => $request->sequencer_login,
                        'sequencer_password' => $request->sequencer_password,
                        'total_inboxes' => $calculatedTotalInboxes,
                        'initial_total_inboxes' => $calculatedTotalInboxes,
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'first_name' => isset($request->prefix_variants_details['prefix_variant_1']['first_name']) ? $request->prefix_variants_details['prefix_variant_1']['first_name'] : null,
                        'last_name' => isset($request->prefix_variants_details['prefix_variant_1']['last_name']) ? $request->prefix_variants_details['prefix_variant_1']['last_name'] : null,
                        'prefix_variant_1' => isset($request->prefix_variants['prefix_variant_1']) ? $request->prefix_variants['prefix_variant_1'] : null,
                        'prefix_variant_2' => isset($request->prefix_variants['prefix_variant_2']) ? $request->prefix_variants['prefix_variant_2'] : null,
                        'prefix_variants' => $request->prefix_variants,
                        'prefix_variants_details' => $request->prefix_variants_details,
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => '123',
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                        'tutorial_section' => $request->tutorial_section,
                    ]);

                    // Also create corresponding Order record
                    $order = Order::create([
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'amount' => $plan->price * $calculatedTotalInboxes,
                        'status' => 'pending',
                        'status_manage_by_admin' => $status,
                        'currency' => 'USD',
                        // 'payment_method' => 'internal',
                        // 'payment_status' => 'paid', // Internal orders are considered pre-paid
                        'internal_order_id' => $internalOrder->id, // Link to internal order
                        'is_internal' => 1
                    ]);

                    // Create ReorderInfo record with detailed configuration
                    $reorderInfo = ReorderInfo::create([
                        'order_id' => $order->id,
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'forwarding_url' => $request->forwarding_url,
                        'hosting_platform' => $request->hosting_platform,
                        'other_platform' => $request->other_platform,
                        'bison_url' => $request->bison_url,
                        'bison_workspace' => $request->bison_workspace,
                        'backup_codes' => $request->backup_codes,
                        'platform_login' => $request->platform_login,
                        'platform_password' => $request->platform_password,
                        'domains' => implode("\n", array_filter($domains)), // Use newlines for reorder_infos
                        'sending_platform' => $request->sending_platform,
                        'sequencer_login' => $request->sequencer_login,
                        'sequencer_password' => $request->sequencer_password,
                        'total_inboxes' => $calculatedTotalInboxes,
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'first_name' => isset($request->prefix_variants_details['prefix_variant_1']['first_name']) ? $request->prefix_variants_details['prefix_variant_1']['first_name'] : null,
                        'last_name' => isset($request->prefix_variants_details['prefix_variant_1']['last_name']) ? $request->prefix_variants_details['prefix_variant_1']['last_name'] : null,
                        'prefix_variant_1' => isset($request->prefix_variants['prefix_variant_1']) ? $request->prefix_variants['prefix_variant_1'] : null,
                        'prefix_variant_2' => isset($request->prefix_variants['prefix_variant_2']) ? $request->prefix_variants['prefix_variant_2'] : null,
                        'prefix_variants' => json_encode($request->prefix_variants),
                        'prefix_variants_details' => json_encode($request->prefix_variants_details),
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => '123',
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                        'tutorial_section' => $request->tutorial_section,
                    ]);
                    $order->orderTracking()->updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'cron_run_time' => now(),
                            'inboxes_per_domain' => $request->inboxes_per_domain,
                            'total_inboxes' => $calculatedTotalInboxes,
                            'status' => 'pending', // Set status to pending for new orders
                        ]
                    );
                    // Commit the transaction
                    DB::commit();
                    
                    $message = 'New internal order created successfully with related data.';
                } catch (\Exception $e) {
                    // Rollback the transaction on error
                    DB::rollback();
                    throw $e;
                }
                
                // Log the creation
                ActivityLogService::log(
                    'internal-order-create',
                    'New internal order created: '. $internalOrder->id . ' with related Order #' . $order->id,
                    $internalOrder, 
                    [
                        'user_id' => $request->user_id,
                        'plan_id' => $determinedPlanId,
                        'total_inboxes' => $calculatedTotalInboxes,
                        'status' => $status,
                        'related_order_id' => $order->id,
                        'reorder_info_id' => $reorderInfo->id
                    ]
                );
            }
            // Run the artisan command
            Artisan::call('panels:check-capacity');
            
            // Get the command output
            $output = Artisan::output();
            
            Log::info('Panel capacity check completed successfully', [
                'output' => $output
            ]);
            // Return response
            return response()->json([
                'success' => true,
                'message' => $message,
                'plan_id' => $determinedPlanId,
                'user_id' => $request->user_id,
                'internal_order_id' => $internalOrder->id,
                'order_id' => isset($order) ? $order->id : null,
                'reorder_info_id' => isset($reorderInfo) ? $reorderInfo->id : null,
                'status' => $status
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 422);
        }
    }
    /**
     * Determine plan ID based on total inboxes using dynamic plan lookup
     * 
     * This method finds the appropriate plan based on the total number of inboxes
     * by checking each plan's min_inbox and max_inbox range. Plans are ordered by
     * min_inbox to check smaller ranges first.
     * 
     * Logic:
     * - Get all active plans ordered by min_inbox (ascending)
     * - Find first plan where totalInboxes >= min_inbox AND (max_inbox = 0 OR totalInboxes <= max_inbox)
     * - max_inbox = 0 means unlimited inboxes
     * - If no exact match, return the unlimited plan or highest range plan
     * 
     * @param int $totalInboxes Total number of inboxes to determine plan for
     * @return int Plan ID
     * @throws \Exception If no active plans found
     */
    private function determinePlanByInboxes($totalInboxes)
    {
        // Get all active plans ordered by min_inbox (ascending) to check smallest ranges first
        $plans = Plan::where('is_active', true)
            ->orderBy('min_inbox', 'asc')
            ->get();

        // If no plans found, throw an exception
        if ($plans->isEmpty()) {
            throw new \Exception('No active plans found in the system. Please create at least one active plan.');
        }

        // Find the appropriate plan based on inbox range
        foreach ($plans as $plan) {
            // Check if total inboxes falls within the plan's range
            // max_inbox = 0 means unlimited
            if ($totalInboxes >= $plan->min_inbox && 
                ($plan->max_inbox == 0 || $totalInboxes <= $plan->max_inbox)) {
                return $plan->id;
            }
        }

        // If no plan matches, find the plan with the highest range
        // This covers cases where totalInboxes exceeds all defined ranges
        $highestPlan = $plans->where('max_inbox', 0)->first() ?? // Unlimited plan first
                      $plans->sortByDesc('max_inbox')->first();   // Or highest max_inbox

        if (!$highestPlan) {
            // Fallback to the first available plan if no suitable plan found
            $highestPlan = $plans->first();
        }

        return $highestPlan->id;
    }
        /**
     * Get orders for import modal (DataTables format)
     */
    public function getOrdersForImport(Request $request)
    {
        try {
            $query = Order::with(['plan', 'reorderInfo'])
                ->where('user_id', auth()->id())
                ->where('status_manage_by_admin', '!=', 'draft');

            // Exclude current order if editing
            if ($request->has('exclude_current') && $request->exclude_current) {
                $query->where('id', '!=', $request->exclude_current);
            }

            return DataTables::of($query)
                ->addColumn('domains_preview', function ($order) {
                    if (!$order->reorderInfo || !$order->reorderInfo->first()) {
                        return 'N/A';
                    }
                    
                    $domains = $order->reorderInfo->first()->domains ?? '';
                    return $domains;
                })
                ->addColumn('total_inboxes', function ($order) {
                    if (!$order->reorderInfo || !$order->reorderInfo->first()) {
                        return 0;
                    }
                    
                    $reorderInfo = $order->reorderInfo->first();
                    $domains = $reorderInfo->domains ?? '';
                    $inboxesPerDomain = $reorderInfo->inboxes_per_domain ?? 1;
                    
                    // Parse domains and count them
                    $domainsArray = [];
                    $lines = preg_split('/\r\n|\r|\n/', $domains);
                    foreach ($lines as $line) {
                        if (trim($line)) {
                            $lineItems = explode(',', $line);
                            foreach ($lineItems as $item) {
                                if (trim($item)) {
                                    $domainsArray[] = trim($item);
                                }
                            }
                        }
                    }
                    
                    $totalDomains = count($domainsArray);
                    $calculatedTotalInboxes = $totalDomains * $inboxesPerDomain;
                    
                    return $calculatedTotalInboxes > 0 ? $calculatedTotalInboxes : ($reorderInfo->total_inboxes ?? 0);
                })
                ->addColumn('status_badge', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusClass = $this->statuses[$status] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('created_at_formatted', function ($order) {
                    return $order->created_at->format('M d, Y');
                })
                ->addColumn('action', function ($order) use ($request) {
                    if ($request->has('for_import') && $request->for_import) {
                        // Import action button
                        return '<button class="btn btn-sm btn-primary import-order-btn" data-order-id="' . $order->id . '" title="Import this order data">
                                    <i class="fa-solid fa-file-import"></i> Import
                                </button>';
                    } else {
                        // Default action buttons
                        return '<div class="dropdown">
                                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="' . route('customer.orders.view', $order->id) . '">
                                            <i class="fa-solid fa-eye"></i> View</a></li>
                                        <li><a class="dropdown-item" href="' . route('customer.orders.reorder', $order->id) . '">
                                            <i class="fa-solid fa-repeat"></i> Reorder</a></li>
                                    </ul>
                                </div>';
                    }
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);

        } catch (Exception $e) {
            Log::error('Error getting orders for import: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load orders'], 500);
        }
    }
    /**
     * Get specific order data for import
     */
    public function importOrderData($id)
    {
        try {
            $order = Order::with(['plan', 'reorderInfo'])
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or access denied.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'plan' => $order->plan,
                    'reorder_info' => $order->reorderInfo->first()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error importing order data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to import order data.'
            ]);
        }
    }

    /**
     * Get internal orders data for DataTables
     */
      
    public function getInternalOrders(Request $request)
    {
        try {
            $query = InternalOrder::with(['user', 'plan'])
                ->select('internal_orders.*');

            // Apply filters
            if ($request->filled('plan_id')) {
                $query->where('plan_id', $request->plan_id);
            }

            if ($request->filled('orderId')) {
                $query->where('id', 'like', '%' . $request->orderId . '%');
            }

            if ($request->filled('status')) {
                $query->where('status_manage_by_admin', $request->status);
            }

            if ($request->filled('email')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->email . '%');
                });
            }

            if ($request->filled('domain')) {
                $query->where('forwarding_url', 'like', '%' . $request->domain . '%');
            }

            if ($request->filled('totalInboxes')) {
                $query->where('total_inboxes', $request->totalInboxes);
            }

            if ($request->filled('startDate')) {
                $query->whereDate('created_at', '>=', $request->startDate);
            }

            if ($request->filled('endDate')) {
                $query->whereDate('created_at', '<=', $request->endDate);
            }

            return DataTables::of($query)
                ->addColumn('user_name', function ($order) {
                    return $order->user ? $order->user->name : 'N/A';
                })
                ->addColumn('user_email', function ($order) {
                    return $order->user ? $order->user->email : 'N/A';
                })
                ->addColumn('plan_name', function ($order) {
                    return $order->plan ? $order->plan->name : 'N/A';
                })
                ->addColumn('assigned_to', function ($order) {
                    if ($order->assigned_to) {
                        $assignedUser = User::find($order->assigned_to);
                        return $assignedUser ? $assignedUser->name : 'Unknown';
                    }
                    return 'Unassigned';
                })
                ->addColumn('split_counts', function ($order) {
                    // For internal orders, split counts might not be applicable
                    // Return a default value or calculate based on your business logic
                    return '0';
                })
                ->addColumn('total_inboxes', function ($order) {
                    return $order->total_inboxes ?? 0;
                })
                ->addColumn('status_badge', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'pending');
                    $statusClass = $this->statuses[$status] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('created_at_formatted', function ($order) {
                    return $order->created_at ? $order->created_at->format('M d, Y H:i') : 'N/A';
                })
                ->addColumn('action', function ($order) {
                    $actions = '<div class="dropdown">
                                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                       
                                        <li><a class="dropdown-item assign-user-btn" href="#" data-order-id="' . $order->id . '" data-bs-toggle="offcanvas" data-bs-target="#assignUserOffcanvas">
                                            <i class="fa-solid fa-user-plus"></i> Assign to User</a></li>
                                        ';
                                        //  <li><a class="dropdown-item" href="' . route('admin.internal_order_management.new_order', $order->id) . '">
                                        //     <i class="fa-solid fa-edit"></i> Edit</a></li>
                                        // <li><hr class="dropdown-divider"></li>
                                        // <li><a class="dropdown-item text-danger delete-order" href="#" data-order-id="' . $order->id . '" data-order-user="' . ($order->user ? $order->user->name : 'Unknown') . '">
                                        //     <i class="fa-solid fa-trash"></i> Delete</a></li>
                    // Add status update options
                    // if ($order->status_manage_by_admin !== 'completed') {
                    //     $actions .= '<li><a class="dropdown-item update-status" href="#" data-order-id="' . $order->id . '" data-status="completed">
                    //                     <i class="fa-solid fa-check"></i> Mark Complete</a></li>';
                    // }
                    
                    // if ($order->status_manage_by_admin !== 'in-progress') {
                    //     $actions .= '<li><a class="dropdown-item update-status" href="#" data-order-id="' . $order->id . '" data-status="in-progress">
                    //                     <i class="fa-solid fa-clock"></i> Mark In Progress</a></li>';
                    // }
                    
                    // if ($order->status_manage_by_admin !== 'reject') {
                    //     $actions .= '<li><a class="dropdown-item update-status" href="#" data-order-id="' . $order->id . '" data-status="reject">
                    //                     <i class="fa-solid fa-times"></i> Reject</a></li>';
                    // }
                    
                    $actions .= '</ul></div>';
                    
                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);

        } catch (Exception $e) {
            Log::error('Error getting internal orders data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load orders'], 500);
        }
    }

    /**
     * Update internal order status
     */
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:internal_orders,id',
                'status' => 'required|string|in:pending,in-progress,completed,reject,cancelled,draft'
            ]);

            $internalOrder = InternalOrder::findOrFail($validated['order_id']);
            $oldStatus = $internalOrder->status_manage_by_admin;
            
            $internalOrder->status_manage_by_admin = $validated['status'];
            $internalOrder->save();

            // Log the status change
            ActivityLogService::log(
                'internal-order-status-update',
                "Internal order status changed from {$oldStatus} to {$validated['status']}",
                $internalOrder, 
                [
                    'old_status' => $oldStatus,
                    'new_status' => $validated['status'],
                    'updated_by' => auth()->id()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'new_status' => $validated['status']
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating internal order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status.'
            ], 500);
        }
    }

    /**
     * Delete internal order
     */
    public function delete(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:internal_orders,id'
            ]);

            $internalOrder = InternalOrder::findOrFail($validated['order_id']);
            
            // Store order info for logging before deletion
            $orderInfo = [
                'id' => $internalOrder->id,
                'user_id' => $internalOrder->user_id,
                'user_name' => $internalOrder->user ? $internalOrder->user->name : 'Unknown',
                'user_email' => $internalOrder->user ? $internalOrder->user->email : 'Unknown',
                'status' => $internalOrder->status_manage_by_admin,
                'total_inboxes' => $internalOrder->total_inboxes,
                'domains' => $internalOrder->domains,
                'created_at' => $internalOrder->created_at
            ];

            // Find and delete related Order record
            $relatedOrder = Order::where('internal_order_id', $internalOrder->id)->first();
            $deletedOrderInfo = [];
            if ($relatedOrder) {
                $deletedOrderInfo['order_id'] = $relatedOrder->id;
                
                // Delete related ReorderInfo records first (due to foreign key constraint)
                $deletedReorderInfos = ReorderInfo::where('order_id', $relatedOrder->id)->get();
                foreach ($deletedReorderInfos as $reorderInfo) {
                    $deletedOrderInfo['reorder_info_ids'][] = $reorderInfo->id;
                    $reorderInfo->delete();
                }
                
                // Then delete the order
                $relatedOrder->delete();
            }

            // Delete the internal order
            $internalOrder->delete();

            Log::info('Internal order and related data deleted successfully', [
                'internal_order_id' => $orderInfo['id'],
                'user_name' => $orderInfo['user_name'],
                'deleted_order_id' => $deletedOrderInfo['order_id'] ?? null,
                'deleted_reorder_info_ids' => $deletedOrderInfo['reorder_info_ids'] ?? [],
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Internal order and all related data deleted successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error deleting internal order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete internal order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users for Select2 dropdown
     */
    public function getUsers(Request $request)
    {
        try {
            $search = $request->get('search', '');
            
            $query = User::select('id', 'name', 'email', 'phone', 'is_internal')
                ->where('is_internal', true); // Only show internal users
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            $users = $query->limit(20)->get();
            
            $results = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ' (' . $user->email . ')',
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'N/A',
                    'is_internal' => $user->is_internal
                ];
            });
            
            return response()->json([
                'results' => $results,
                'pagination' => ['more' => false]
            ]);
            
        } catch (Exception $e) {
            Log::error('Error getting users for assignment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load users'], 500);
        }
    }

    /**
     * Assign order to user
     */
    public function assignToUser(Request $request)
    {
        try {
            // Check if we're creating a new user
            if ($request->has('create_new_user') && $request->create_new_user) {
                return $this->createAndAssignUser($request);
            }

            // Existing user assignment logic
            $validated = $request->validate([
                'order_id' => 'required|exists:internal_orders,id',
                'user_id' => 'required|exists:users,id'
            ]);
            
            $internalOrder = InternalOrder::findOrFail($validated['order_id']);
            $user = User::findOrFail($validated['user_id']);
            
            // Get the related order from orders table
            $relatedOrder = Order::where('internal_order_id', $internalOrder->id)->first();
            // dd($relatedOrder, $internalOrder, $validated);
            $oldAssignedTo = $internalOrder->assigned_to;
            
            // Update internal order assignment
            $internalOrder->assigned_to = $validated['user_id'];
            $internalOrder->save();

            // Update related order assignment if it exists
            if ($relatedOrder) {
                $oldAssignedTo = $relatedOrder->user_id;
                $relatedOrder->user_id = $validated['user_id'];
                $relatedOrder->is_internal_order_assignment = true;
                $relatedOrder->save();
                
                // Update reorder info if it exists
                $reorderInfo = $relatedOrder->reorderInfo()->first();
                if ($reorderInfo) {
                    $reorderInfo->user_id = $validated['user_id'];
                    $reorderInfo->save();
                }
            }

            // Log the assignment activity
            ActivityLogService::log(
                'internal-order-assignment',
                'Internal order #' . $internalOrder->id . ' assigned to ' . $user->name . 
                ($relatedOrder ? ' (Order #' . $relatedOrder->id . ' also updated)' : ''),
                $internalOrder,
                [
                    'old_assigned_to' => $oldAssignedTo,
                    'new_assigned_to' => $user->id,
                    'assigned_by' => auth()->id(),
                    'related_order_id' => $relatedOrder ? $relatedOrder->id : null,
                    'related_order_updated' => $relatedOrder ? true : false
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Order successfully assigned to ' . $user->name . 
                            ($relatedOrder ? ' (Related order #' . $relatedOrder->id . ' also updated)' : '')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error assigning order to user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new user and assign order to them
     */
    private function createAndAssignUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:internal_orders,id',
                'new_user_name' => 'required|string|max:255',
                'new_user_email' => 'required|email|unique:users,email',
                'new_user_phone' => 'nullable|string|max:20',
                'new_user_password' => 'required|string|min:6|confirmed',
                'new_user_password_confirmation' => 'required|string|min:6',
                'new_user_internal' => 'nullable|boolean'
            ]);

            $internalOrder = InternalOrder::findOrFail($validated['order_id']);

            // Get the related order from orders table
            $relatedOrder = Order::where('internal_order_id', $internalOrder->id)->first();

            // Create the new user
            $user = User::create([
                'name' => $validated['new_user_name'],
                'email' => $validated['new_user_email'],
                'phone' => $validated['new_user_phone'] ?? null,
                'password' => bcrypt($validated['new_user_password']),
                'is_internal' => $validated['new_user_internal'] ?? true, // Default to internal user
                'email_verified_at' => now(), // Auto-verify internal users
            ]);

            // Assign the internal order to the new user
            $oldAssignedTo = $internalOrder->assigned_to;
            $internalOrder->assigned_to = $user->id;
            $internalOrder->save();

            // Assign the related order to the new user if it exists
            if ($relatedOrder) {
                $oldAssignedTo = $relatedOrder->user_id;
                $relatedOrder->user_id = $user->id;
                $relatedOrder->is_internal_order_assignment = true;
                // $relatedOrder->reorderInfo()->first()->user_id = $user->id;
                // Update reorder info if it exists
                $reorderInfo = $relatedOrder->reorderInfo()->first();
                if ($reorderInfo) {
                    $reorderInfo->user_id = $user->id;
                    $reorderInfo->save();
                }
                $relatedOrder->save();
            }

            // Log the user creation and assignment activity
            ActivityLogService::log(
                'internal-user-created-and-assigned',
                'New internal user ' . $user->name . ' created and assigned to order #' . $internalOrder->id .
                ($relatedOrder ? ' (Order #' . $relatedOrder->id . ' also updated)' : ''),
                $internalOrder,
                [
                    'created_user_id' => $user->id,
                    'created_user_email' => $user->email,
                    'created_user_phone' => $user->phone,
                    'old_assigned_to' => $oldAssignedTo,
                    'new_assigned_to' => $user->id,
                    'assigned_by' => auth()->id(),
                    'related_order_id' => $relatedOrder ? $relatedOrder->id : null,
                    'related_order_updated' => $relatedOrder ? true : false
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'New user ' . $user->name . ' created and order assigned successfully' .
                            ($relatedOrder ? ' (Related order #' . $relatedOrder->id . ' also updated)' : ''),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating and assigning user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user and assign order: ' . $e->getMessage()
            ], 500);
        }
    }
}
