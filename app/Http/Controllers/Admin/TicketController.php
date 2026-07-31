<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Mail\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function __construct()
    {
        // Apply permission middleware to all methods
        $this->middleware('can:ticket.index')->only(['index']);
        $this->middleware('can:ticket.show')->only(['show']);
        $this->middleware('can:ticket.reply')->only(['reply']);
        $this->middleware('can:ticket.update-status')->only(['updateStatus']);
        $this->middleware('can:ticket.assign')->only(['assign']);
        $this->middleware('can:ticket.close')->only(['close']);
        $this->middleware('can:ticket.reopen')->only(['reopen']);
        $this->middleware('can:ticket.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'assignedTo', 'latestMessage'])->withCount('messages');

        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            if ($request->status === 'open') {
                $query->open();
            } elseif ($request->status === 'closed') {
                $query->closed();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority != 'all') {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->has('category') && $request->category != 'all') {
            $query->where('category', $request->category);
        }

        // Filter by assigned admin
        if ($request->has('assigned_to') && $request->assigned_to != 'all') {
            if ($request->assigned_to == 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get users who can manage tickets (exclude vendors and customers)
        $admins = User::whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['vendor', 'consumer']);
        })->orderBy('name')->get();

        // Get statistics
        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'waiting_admin' => Ticket::where('status', 'waiting_admin')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'unassigned' => Ticket::whereNull('assigned_to')->open()->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'admins', 'stats'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'assignedTo', 'messages.user']);

        // Get users who can manage tickets (exclude vendors and customers)
        $admins = User::whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['vendor', 'consumer']);
        })->orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'admins'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Update ticket status
        if ($ticket->status === 'waiting_admin') {
            $ticket->update(['status' => 'waiting_customer']);
        } elseif ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        // Send email notification to customer (only if not internal message)
        if (!$request->boolean('is_internal', false)) {
            try {
                $ticket->load('user');

                if ($ticket->user && $ticket->user->email) {
                    Mail::to($ticket->user->email)->send(new TicketReplyNotification($ticket, $message));

                }
            } catch (\Exception $emailError) {
                // Log email error but don't fail the request
                Log::error('Failed to send ticket reply email from admin', [
                    'ticket_id' => $ticket->id,
                    'error' => $emailError->getMessage(),
                ]);
            }
        }

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Reply added successfully');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,waiting_admin,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updateData = ['status' => $request->status];

        if ($request->has('assigned_to')) {
            $updateData['assigned_to'] = $request->assigned_to;
        }

        if ($request->status === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        if ($request->status === 'closed' && !$ticket->closed_at) {
            $updateData['closed_at'] = now();
        }

        $ticket->update($updateData);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $ticket->update([
            'assigned_to' => $request->assigned_to,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket assigned successfully');
    }

    public function close(Ticket $ticket)
    {
        $ticket->markAsClosed();

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket closed successfully');
    }

    public function reopen(Ticket $ticket)
    {
        $ticket->reopen();

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket reopened successfully');
    }
}
