<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\Notification;
use App\Services\ActivityLogService;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', Auth::id())
            ->latest()
            ->get();
            
        $totalTickets = $tickets->count();
        $pendingTickets = $tickets->where('status', 'open')->count();
        $completedTickets = $tickets->where('status', 'closed')->count();
        
        return view('customer.support.support', compact('totalTickets', 'pendingTickets', 'completedTickets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'order_id' => 'required_if:category,order|nullable|exists:orders,id,user_id,'.Auth::id(),
            'attachments.*' => 'nullable|file|max:10240' // 10MB max per file
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $attachments[] = $path;
            }
        }

        // Verify order belongs to user if category is order
        $assignedTo = null;
        if ($validated['category'] === 'order' && $request->order_id) {
            $order = Order::where('id', $request->order_id)
                         ->where('user_id', Auth::id())
                         ->firstOrFail();
            $assignedTo = $order->assigned_to;
        }
        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'attachments' => $attachments,
            'status' => 'open',
            'order_id' => $request->order_id,
            'assigned_to' => $assignedTo
        ]);
        
        // Create a new activity log using the custom log service
        ActivityLogService::log(
            'customer-support-ticket-create', 
            'Created a new support ticket: ' . $ticket->id, 
            $ticket, 
            [
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'created_at' => now()->toDateTimeString(),
                'ip_address' => request()->ip()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'ticket' => $ticket
        ]);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['replies.user', 'user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('customer.support.ticket_conversation', compact('ticket'));
    }

    public function reply(Request $request, $ticketId)
    {
        $validated = $request->validate([
            'message' => [
            'required',
            function ($attribute, $value, $fail) {
                // Strip HTML tags and check if content is empty
                if (empty(trim(strip_tags($value)))) {
                $fail('The message field cannot be empty.');
                }
            }
            ],
            'attachments.*' => 'nullable|file|max:10240'
        ]);
        // dd($validated);

        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($ticketId);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-replies', 'public');
                $attachments[] = $path;
            }
        }

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'attachments' => $attachments
        ]);

        // If ticket was closed, reopen it when customer replies
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }
        // Create a new activity log using the custom log service
        ActivityLogService::log(
            'customer-support-ticket-reply', 
            'Replied to support ticket: ' . $ticket->id, 
            $reply, 
            [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'message' => $reply->message,
                'attachments' => $reply->attachments,
                'created_at' => now()->toDateTimeString(),
                'ip_address' => request()->ip()
            ]
        );

        if ($ticket->assigned_to) {
            // Create notification for the staff member
            Notification::create([
                'user_id' => $ticket->assigned_to,
                'type' => 'support_ticket_reply',
                'title' => 'New Reply on Ticket',
                'message' => "You have a new reply on support ticket #{$ticket->id}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                    'message' => $reply->message,
                    'attachments' => $reply->attachments,
                    'created_at' => now()->toDateTimeString(),
                    'ip_address' => request()->ip()
                ]
            ]);

            // Send email to assigned staff member
            $assignedStaff = \App\Models\User::find($ticket->assigned_to);
            if ($assignedStaff) {
                Mail::to($assignedStaff->email)
                    ->queue(new \App\Mail\TicketReplyMail(
                        $ticket,
                        $reply,
                        Auth::user(),
                        $assignedStaff
                    ));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'reply' => $reply->load('user')
        ]);
    }

    public function getTickets(Request $request)
    {
        $tickets = SupportTicket::with(['user'])
            ->where('user_id', Auth::id())
            ->select('support_tickets.*');

        // Apply ticket number filter
        if ($request->has('ticket_number') && $request->ticket_number != '') {
            $tickets->where('ticket_number', 'like', '%' . $request->ticket_number . '%');
        }

        // Apply subject filter
        if ($request->has('subject') && $request->subject != '') {
            $tickets->where('subject', 'like', '%' . $request->subject . '%');
        }

        // Apply category filter
        if ($request->has('category') && $request->category != '') {
            $tickets->where('category', $request->category);
        }

        // Apply priority filter
        if ($request->has('priority') && $request->priority != '') {
            $tickets->where('priority', $request->priority);
        }

        // Apply status filter
        if ($request->has('status') && $request->status != '') {
            $tickets->where('status', $request->status);
        }

        // Apply date range filters
        if ($request->has('start_date') && $request->start_date != '') {
            $tickets->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $tickets->whereDate('created_at', '<=', $request->end_date);
        }

        return DataTables::of($tickets)
            ->addColumn('action', function ($ticket) {
                return '<div class="d-flex align-items-center gap-2">
                    <button class="bg-transparent p-0 border-0" onclick="viewTicket('.$ticket->id.')">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>';
            })
            ->editColumn('created_at', function ($ticket) {
                return $ticket->created_at->format('d M, Y');
            })
            ->editColumn('status', function ($ticket) {
                $statusClass = match($ticket->status) {
                    'open' => 'warning',
                    'in_progress' => 'primary',
                    'closed' => 'success',
                    default => 'secondary'
                };
                return '<span class="py-1 px-2 text-'.$statusClass.' border border-'.$statusClass.' rounded-2 bg-transparent">'.ucfirst($ticket->status).'</span>';
            })
            ->editColumn('priority', function ($ticket) {
                $priorityClass = match($ticket->priority) {
                    'low' => 'success',
                    'medium' => 'warning',
                    'high' => 'danger',
                    default => 'secondary'
                };
                return '<span class="py-1 px-2 text-'.$priorityClass.' border border-'.$priorityClass.' rounded-2 bg-transparent">'.ucfirst($ticket->priority).'</span>';
            })
            ->rawColumns(['action', 'status', 'priority'])
            ->make(true);
    }

    public function getUserOrders(Request $request)
    {
        $query = Order::where('user_id', Auth::id())
                     ->with(['plan', 'reorderInfo']);

        // Apply search filter if search term is provided
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('plan', function($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
                       ->get()
                       ->map(function($order) {
                           return [
                               'id' => $order->id,
                               'text' => sprintf(
                                   "Order #%d - %s (%s)", 
                                   $order->id,
                                   $order->plan ? $order->plan->name : 'N/A',
                                   $order->created_at->format('d M Y')
                               )
                           ];
                       });
        
        return response()->json([
            'results' => array_values($orders->toArray())
        ]);
    }
}
