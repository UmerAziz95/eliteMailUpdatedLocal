<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
            'attachments.*' => 'nullable|file|max:10240' // 10MB max per file
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $attachments[] = $path;
            }
        }

        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'attachments' => $attachments,
            'status' => 'open'
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
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240'
        ]);

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
                $assignedStaff->email = "muhammad.farooq.raaj@gmail.com";
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
}
