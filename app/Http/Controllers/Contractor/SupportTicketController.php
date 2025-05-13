<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Mail;

class SupportTicketController extends Controller
{
    public function index()
    {
        // Get both assigned and unassigned order tickets
        $assignedTickets = SupportTicket::where('category', 'order')
            ->where('assigned_to', Auth::id())
            ->get();
        $unassignedTickets = SupportTicket::where('category', 'order')
            ->whereNull('assigned_to')
            ->get();
        
        $totalTickets = $assignedTickets->count();
        $pendingTickets = $assignedTickets->where('status', 'open')->count();
        $inProgressTickets = $assignedTickets->where('status', 'in_progress')->count();
        $completedTickets = $assignedTickets->where('status', 'closed')->count();
        
        return view('contractor.support.support', compact(
            'totalTickets', 
            'pendingTickets', 
            'inProgressTickets', 
            'completedTickets',
            'unassignedTickets'
        ));
    }

    public function show($id)
    {
        // Allow viewing both assigned and unassigned order tickets
        $ticket = SupportTicket::with(['replies.user', 'user'])
            ->where('category', 'order')
            ->where(function($query) {
                $query->where('assigned_to', Auth::id())
                      ->orWhereNull('assigned_to');
            })
            ->findOrFail($id);

        return view('contractor.support.ticket_conversation', compact('ticket'));
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
            'attachments.*' => 'nullable|file|max:10240',
            'is_internal' => 'nullable|boolean',
            'update_status' => 'nullable|in:open,in_progress,closed'
        ]);

        $ticket = SupportTicket::where('category', 'order')
            ->where(function($query) {
                $query->where('assigned_to', Auth::id())
                      ->orWhereNull('assigned_to');
            })->findOrFail($ticketId);

        // Auto-assign ticket if unassigned
        if (!$ticket->assigned_to) {
            $ticket->update(['assigned_to' => Auth::id()]);
        }

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
            'attachments' => $attachments,
            'is_internal' => $validated['is_internal'] ?? false
        ]);

        // Update ticket status if needed
        if ($request->has('update_status') && $request->update_status) {
            $ticket->update(['status' => $request->update_status]);
        }

        // If this is not an internal note, notify the customer
        // if (!($validated['is_internal'] ?? false)) {
            // Send email to customer
            Mail::to($ticket->user->email)
                ->queue(new \App\Mail\TicketReplyMail(
                    $ticket,
                    $reply,
                    Auth::user(),  
                    $ticket->user
                ));

            // Create notification for the customer
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_ticket_reply',
                'title' => 'New Reply on Ticket',
                'message' => "You have a new reply on your support ticket #{$ticket->id}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                    'message' => $reply->message,
                    'attachments' => $reply->attachments,
                    'created_at' => now()->toDateTimeString(),
                    'ip_address' => request()->ip()
                ]
            ]);
        // }

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'reply' => $reply->load('user')
        ]);
    }

    public function updateStatus(Request $request, $ticketId)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,closed'
        ]);

        $ticket = SupportTicket::where(function($query) {
            $query->where('assigned_to', Auth::id())
                  ->orWhereNull('assigned_to');
        })->findOrFail($ticketId);

        // Auto-assign ticket if unassigned
        if (!$ticket->assigned_to) {
            $ticket->update([
                'assigned_to' => Auth::id(),
                'status' => $validated['status']
            ]);
        } else {
            $ticket->update(['status' => $validated['status']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully'
        ]);
    }

    public function getTickets(Request $request)
    {
        // Get global counters for contractor's tickets
        $assignedTickets = SupportTicket::where('category', 'order')
            ->where('assigned_to', Auth::id());
            
        $counters = [
            'totalTickets' => $assignedTickets->count(),
            'pendingTickets' => (clone $assignedTickets)->where('status', 'open')->count(),
            'inProgressTickets' => (clone $assignedTickets)->where('status', 'in_progress')->count(),
            'completedTickets' => (clone $assignedTickets)->where('status', 'closed')->count(),
        ];

        $tickets = SupportTicket::with(['user', 'order'])
            ->where(function($query) {
                $query->where('assigned_to', Auth::id())
                      ->orWhereNull('assigned_to');
            })
            ->when(Auth::user()->role_id == 4, function($query) {
                // For contractors, only show order-related tickets
                $query->where('category', 'order');
            })
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
            ->with('counters', $counters)
            ->addColumn('action', function ($ticket) {
                $assignedBadge = $ticket->assigned_to ? '' : '<span class="badge bg-info ms-2">Unassigned</span>';
                return '<div class="d-flex align-items-center gap-2">
                    <button class="bg-transparent p-0 border-0" onclick="viewTicket('.$ticket->id.')">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                    <div class="dropdown">
                        <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item updateStatus" href="#" data-id="'.$ticket->id.'" data-status="in_progress">Mark In Progress</a></li>
                            <li><a class="dropdown-item updateStatus" href="#" data-id="'.$ticket->id.'" data-status="closed">Mark as Closed</a></li>
                        </ul>
                    </div>
                    '.$assignedBadge.'
                </div>';
            })
            ->addColumn('order_number', function ($ticket) {
                return $ticket->order ? '#'.$ticket->order->id : 'N/A';
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
                return '<span class="py-1 px-2 text-'.$statusClass.' border border-'.$statusClass.' rounded-2 bg-transparent">'.str_replace('_', ' ', ucfirst($ticket->status)).'</span>';
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
}
