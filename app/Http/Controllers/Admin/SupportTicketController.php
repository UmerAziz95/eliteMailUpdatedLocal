<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::latest()->get();
        
        $totalTickets = $tickets->count();
        $pendingTickets = $tickets->where('status', 'open')->count();
        $inProgressTickets = $tickets->where('status', 'in_progress')->count();
        $completedTickets = $tickets->where('status', 'closed')->count();
        
        return view('admin.support.support', compact(
            'totalTickets', 
            'pendingTickets', 
            'inProgressTickets', 
            'completedTickets'
        ));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['replies.user', 'user'])->findOrFail($id);
        return view('admin.support.ticket_conversation', compact('ticket'));
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
            'is_internal' => 'boolean'
        ]);

        $ticket = SupportTicket::findOrFail($ticketId);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $attachments[] = $path;
            }
        }

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'attachments' => $attachments,
            'is_internal' => $request->input('is_internal', false)
        ]);

        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
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
            ->select('support_tickets.*');
            
        // Get global counters
        $counters = [
            'totalTickets' => SupportTicket::count(),
            'pendingTickets' => SupportTicket::where('status', 'open')->count(),
            'inProgressTickets' => SupportTicket::where('status', 'in_progress')->count(),
            'completedTickets' => SupportTicket::where('status', 'closed')->count(),
        ];

        // Apply ticket number filter
        if ($request->has('ticket_number') && $request->ticket_number != '') {
            $tickets->where('ticket_number', 'like', '%' . $request->ticket_number . '%');
        }

        // Apply subject filter
        if ($request->has('subject') && $request->subject != '') {
            $tickets->where('subject', 'like', '%' . $request->subject . '%');
        }

        // Apply customer filter
        if ($request->has('customer') && $request->customer != '') {
            $tickets->whereHas('user', function($query) use ($request) {
                $query->where('name', 'like', '%' . $request->customer . '%');
            });
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
                return '<div class="d-flex align-items-center gap-2">
                    <button class="bg-transparent p-0 border-0" onclick="viewTicket('.$ticket->id.')">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                    <div class="dropdown">
                        <button class="bg-transparent p-0 border-0" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item updateStatus" href="#" data-id="'.$ticket->id.'" data-status="in_progress">Mark as In Progress</a></li>
                            <li><a class="dropdown-item updateStatus" href="#" data-id="'.$ticket->id.'" data-status="closed">Mark as Closed</a></li>
                        </ul>
                    </div>
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

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,closed'
        ]);
         
        $ticket = SupportTicket::findOrFail($id);
        $oldStatus = $ticket->status;
        $newStatus = $validated['status'];
        $ticket->update(['status' => $validated['status']]);
        
        // Only send notification if status has actually changed
        if ($oldStatus !== $newStatus) {
            // Send email notification to the user
            Mail::to($ticket->user->email)
                ->queue(new \App\Mail\TicketStatusMail(
                    $ticket,
                    Auth::user(),
                    $oldStatus,
                    $newStatus
                ));

            // Create notification for the user
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_ticket_status',
                'title' => 'Ticket Status Updated',
                'message' => "The status of your support ticket #{$ticket->ticket_number} has been updated to " . ucfirst(str_replace('_', ' ', $newStatus)),
                'data' => [
                    'ticket_id' => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'updated_by' => Auth::id(),
                    'created_at' => now()->toDateTimeString(),
                    'ip_address' => request()->ip()
                ]
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully'
        ]);
    }
}