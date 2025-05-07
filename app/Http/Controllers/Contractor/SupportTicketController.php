<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends Controller
{
    public function index()
    {
        // Get both assigned and unassigned tickets
        $assignedTickets = SupportTicket::where('assigned_to', Auth::id())->get();
        $unassignedTickets = SupportTicket::whereNull('assigned_to')->get();
        
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
        // Allow viewing both assigned and unassigned tickets
        $ticket = SupportTicket::with(['replies.user', 'user'])
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
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
            'is_internal' => 'boolean'
        ]);

        $ticket = SupportTicket::where(function($query) {
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
        $tickets = SupportTicket::with(['user'])
            ->where(function($query) {
                $query->where('assigned_to', Auth::id())
                      ->orWhereNull('assigned_to');
            })
            ->select('support_tickets.*');

        if ($request->has('status') && $request->status) {
            $tickets->where('status', $request->status);
        }

        return DataTables::of($tickets)
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
