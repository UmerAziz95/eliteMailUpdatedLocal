<?php

namespace App\Http\Controllers\Admin;

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
            'message' => 'required|string',
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
        dd($request->all());
        $tickets = SupportTicket::with(['user'])
            ->select('support_tickets.*');

        return DataTables::of($tickets)
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

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,closed'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully'
        ]);
    }
}