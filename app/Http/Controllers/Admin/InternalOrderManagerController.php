<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\ReorderInfo;
use App\Models\HostingPlatform;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use App\Models\Log as ModelLog;
use App\Models\Status;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

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
        $orders = Order::all();
        $statuses = $this->statuses;

        $totalOrders = $orders->count();

        $pendingOrders = $orders->where('status_manage_by_admin', 'pending')->count();
        $rejectOrders = $orders->where('status_manage_by_admin', 'reject')->count();
        $inProgressOrders = $orders->where('status_manage_by_admin', 'in-progress')->count();
        $cancelledOrders = $orders->where('status_manage_by_admin', 'cancelled')->count();
        $completedOrders = $orders->where('status_manage_by_admin', 'completed')->count();
        $draftOrders = $orders->where('status_manage_by_admin', 'draft')->count();

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
            'percentageChange',
            'statuses'
        ));
    }


    public function newOrder(Request $request){
       

         $order =null;
         $plan =\App\Models\Plan::first();
        //  dd($plan);
        // dd($order);
        $hostingPlatforms = \App\Models\HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();
        
        return view('admin.internal_order_manager.edit-order', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));

       
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
                // 'first_name' => 'required|string|max:50',
                // 'last_name' => 'required|string|max:50',
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
                // 'persona_password' => 'required|string|min:3',
                'profile_picture_link' => 'nullable|url|max:255',
                // 'email_persona_password' => 'nullable|string|min:3',
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
                    
                    // if (empty($prefixVariantsDetails[$prefixKey]['profile_link'])) {
                    //     return response()->json([
                    //         'success' => false,
                    //         'errors' => ["prefix_variants_details.{$prefixKey}.profile_link" => ['Profile link is required for this prefix variant.']]
                    //     ], 422);
                    // }
                    
                    // // Validate URL format for profile link
                    // if (!filter_var($prefixVariantsDetails[$prefixKey]['profile_link'], FILTER_VALIDATE_URL)) {
                    //     return response()->json([
                    //         'success' => false,
                    //         'errors' => ["prefix_variants_details.{$prefixKey}.profile_link" => ['Profile link must be a valid URL.']]
                    //     ], 422);
                    // }
                }
            }
            $status = 'pending'; // Default status for new orders
            // persona_password set 123
            $request->persona_password = '123';
            // Calculate number of domains and total inboxes
            $domains = array_filter(preg_split('/[\r\n,]+/', $request->domains));
            $domainCount = count($domains);
            $calculatedTotalInboxes = $domainCount * $request->inboxes_per_domain;

            // Get requested plan
            $plan = Plan::findOrFail($request->plan_id);
            
            // Store session data if validation passes
            // $request->session()->put('order_info', $request->all());
            // set new plan_id on session order_info
            // $request->session()->put('order_info.plan_id', $request->plan_id);
            $message = 'Order information saved successfully.';
            
            // for edit order
            if($request->edit_id && $request->order_id){
                $temp_order = Order::with('reorderInfo')->findOrFail($request->order_id);
                $TOTAL_INBOXES = $temp_order->reorderInfo->first()->total_inboxes;
                
                // Validate against order's total_inboxes limit
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
                // "currentInboxes" => "3332" "max_inboxes" => "5000"
                $currentInboxes = $request->current_inboxes ?? 0;
                $maxInboxes = $request->max_inboxes ?? 0;
                $order = Order::with('reorderInfo')->findOrFail($request->order_id);
                // Set status based on whether total_inboxes equals calculated total from request domains
                // $status = ($TOTAL_INBOXES == $calculatedTotalInboxes) ? 'pending' : 'draft';
                // $status = ($currentInboxes == $maxInboxes) ? 'pending' : 'draft';
                $is_draft = $request->is_draft ?? 0;
                // If is_draft is set to 1, set status to draft, otherwise pending
                $status = $is_draft == 1 ? 'draft' : 'pending';
                // Update order status
                $order->update([
                    'status_manage_by_admin' => $status,
                ]);

                if($order->assigned_to && $status != 'draft') {
                    $order->update([
                        'status_manage_by_admin' => 'in-progress',
                    ]);
                }
                // Get the current session data
                $orderInfo = $request->session()->get('order_info', []);
                
                // Update session with reorder info data (preserve new form input over old data)
                if($order->reorderInfo && !$order->reorderInfo->isEmpty()) {
                    $reorderInfo = $order->reorderInfo->first();
                    // update data on table ReorderInfo
                    ReorderInfo::where('id', $reorderInfo->id)->update([
                        'user_id' => $request->user_id,
                        'plan_id' => $request->plan_id,
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
                        // 'total_inboxes' => ($maxInboxes == $currentInboxes) ? $calculatedTotalInboxes : $reorderInfo->total_inboxes,
                        'total_inboxes' => $is_draft == 1 ? $reorderInfo->total_inboxes : $calculatedTotalInboxes, // Use existing total_inboxes if draft, otherwise use calculated
                        // initial_total_inboxes
                        'initial_total_inboxes' => $reorderInfo->initial_total_inboxes == 0 ? $reorderInfo->total_inboxes : $reorderInfo->initial_total_inboxes, // Store initial total inboxes at reorder time
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        // 'first_name' => 'N/A',
                        // 'last_name' => 'N/A',
                        'prefix_variants' => $request->prefix_variants,
                        'prefix_variants_details' => $request->prefix_variants_details,
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => '123', // Set to 123 as per requirement
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                    ]);
                   $message = 'Order information updated successfully.';
                   // Create a new activity log using the custom log service
                    ActivityLogService::log(
                        'customer-order-update',
                        'Order updated: '. $order->id,
                        $order, 
                        [
                            'user_id' => $request->user_id,
                            'plan_id' => $request->plan_id,
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
                            'inboxes_per_domain' => $request->inboxes_per_domain,
                            // 'first_name' => $request->first_name,
                            // 'last_name' => $request->last_name,
                            'prefix_variants' => $request->prefix_variants,
                            'prefix_variants_details' => $request->prefix_variants_details,
                            'persona_password' => $request->persona_password,
                            'profile_picture_link' => $request->profile_picture_link,
                            'email_persona_password' => $request->email_persona_password,
                            'email_persona_picture_link' => $request->email_persona_picture_link,
                            'master_inbox_email' => $request->master_inbox_email,
                            'additional_info' => $request->additional_info,
                        ]
                    );
                }
                // 
                // Send email to admin and customer when order is edited
                try {
                    // Get user information
                    $user = User::findOrFail($request->user_id);
                    $reorderInfo = $order->reorderInfo->first();
                    
                    // Send notification to the customer
                    Mail::to($user->email)
                        ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], false));
                    // dd(config('mail.admin_address', 'admin@example.com'));
                    // Send notification to admin
                    Mail::to(config('mail.admin_address', 'admin@example.com'))
                        ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], true));
                    
                    // Check if the order has an assigned contractor
                    if ($order->assigned_to ) {
                        // Get the assigned contractor
                        $contractor = User::find($order->assigned_to);
                        // dd($contractor);
                        // Send notification to the assigned contractor if found
                        if ($contractor) {
                            Mail::to($contractor->email)
                                ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], true));
                        }
                    } else {
                        // No assigned contractor, log this information
                        Log::info('No contractor assigned to order #' . $order->id . ' for edit notification');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send order edit notification emails: ' . $e->getMessage());
                    // Continue execution - don't let email failure stop the process
                }
            }
            // status is pending then pannelCreationAndOrderSplitOnPannels
            if($status == 'pending'){
                // panel creation
                // $this->pannelCreationAndOrderSplitOnPannels($order);
            }
            // Create order tracking record at the end
            if($request->edit_id && $request->order_id) {
                // For existing orders, update or create tracking record
                $order->orderTracking()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'cron_run_time' => now(),
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'total_inboxes' => $calculatedTotalInboxes,
                        'status' => 'pending', // Set status to pending for new orders
                    ]
                );
                
                Log::info('Order tracking record updated for edited order', [
                    'order_id' => $order->id,
                    'status' => $status,
                    'total_inboxes' => $calculatedTotalInboxes,
                    'inboxes_per_domain' => $request->inboxes_per_domain
                ]);
            }
            
            // First check 
            return response()->json([
                'success' => true,
                'message' => $message,
                'plan_id' => $request->plan_id,
                'user_id' => $request->user_id,
                'order_id' => isset($order) ? $order->id : null,
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
}
