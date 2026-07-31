<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Mail\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Tickets", description="Customer support ticketing system")
 */
class TicketController extends Controller
{
    public function __construct()
    {
         // Note: Customers CAN access their own tickets via React frontend
        // Vendors are blocked because they have their own vendor dashboard
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Only block vendors - customers need access to support tickets
            if ($user && $user->hasRole('vendor')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Please use the vendor dashboard for support.',
                ], 403);
            }

            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *   path="/api/tickets",
     *   tags={"Tickets"},
     *   summary="List support tickets",
     *   description="Get list of support tickets for authenticated user. Admins can see all tickets.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Filter by status",
     *     required=false,
     *     @OA\Schema(type="string", enum={"open", "in_progress", "resolved", "closed"}, example="open")
     *   ),
     *   @OA\Parameter(
     *     name="priority",
     *     in="query",
     *     description="Filter by priority",
     *     required=false,
     *     @OA\Schema(type="string", enum={"low", "medium", "high", "urgent"}, example="high")
     *   ),
     *   @OA\Parameter(
     *     name="category",
     *     in="query",
     *     description="Filter by category",
     *     required=false,
     *     @OA\Schema(type="string", example="order_issue")
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search in ticket number, subject, description",
     *     required=false,
     *     @OA\Schema(type="string", example="order")
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     description="Results per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=15, example=15)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="List of tickets",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="tickets",
     *         type="object",
     *         @OA\Property(
     *           property="data",
     *           type="array",
     *           @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1001),
     *             @OA\Property(property="ticket_number", type="string", example="TICKET-2026-0109-1001"),
     *             @OA\Property(property="subject", type="string", example="Product not delivered"),
     *             @OA\Property(property="category", type="string", example="order_issue"),
     *             @OA\Property(property="priority", type="string", example="high"),
     *             @OA\Property(property="status", type="string", example="open")
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Ticket::with(['user', 'assignedTo', 'latestMessage.user']);

            // If not admin, only show user's own tickets
            if (!$user->hasRole('admin')) {
                $query->forUser($user->id);
            } else {
                // Admin can filter by user_id if provided
                if ($request->has('user_id')) {
                    $query->forUser($request->user_id);
                }

                // Admin can filter by assigned tickets
                if ($request->has('assigned_to_me') && $request->assigned_to_me) {
                    $query->assignedTo($user->id);
                }
            }

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'open') {
                    $query->open();
                } elseif ($request->status === 'closed') {
                    $query->closed();
                } else {
                    $query->where('status', $request->status);
                }
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
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

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $tickets = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'tickets' => $tickets,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tickets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tickets',
            ], 500);
        }
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'in:low,medium,high,urgent',
                'category' => 'in:general,technical,billing,account,order,other',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'category' => $request->category ?? 'general',
                'status' => 'open',
            ]);

            // Create initial message with the description
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description,
            ]);

            $ticket->load(['user', 'messages.user']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'ticket' => $ticket,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating ticket',
            ], 500);
        }
    }

    /**
     * Display the specified ticket
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::with(['user', 'assignedTo', 'messages.user'])->find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check authorization
            if (!$user->hasRole('admin') && $ticket->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Filter internal messages for non-admin users
            if (!$user->hasRole('admin')) {
                $ticket->setRelation('messages', $ticket->messages->where('is_internal', false)->values());
            }

            return response()->json([
                'success' => true,
                'ticket' => $ticket,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching ticket',
            ], 500);
        }
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check authorization
            if (!$user->hasRole('admin') && $ticket->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'subject' => 'string|max:255',
                'description' => 'string',
                'priority' => 'in:low,medium,high,urgent',
                'status' => 'in:open,in_progress,waiting_customer,waiting_admin,resolved,closed',
                'category' => 'in:general,technical,billing,account,order,other',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = $request->only(['subject', 'description', 'priority', 'category']);

            // Only admins can change status and assignment
            if ($user->hasRole('admin')) {
                if ($request->has('status')) {
                    $updateData['status'] = $request->status;

                    if ($request->status === 'resolved' && !$ticket->resolved_at) {
                        $updateData['resolved_at'] = now();
                    }

                    if ($request->status === 'closed' && !$ticket->closed_at) {
                        $updateData['closed_at'] = now();
                    }
                }

                if ($request->has('assigned_to')) {
                    $updateData['assigned_to'] = $request->assigned_to;
                }
            }

            $ticket->update($updateData);
            $ticket->load(['user', 'assignedTo', 'messages.user']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'ticket' => $ticket,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating ticket',
            ], 500);
        }
    }

    /**
     * Add a message to a ticket
     */
    public function addMessage(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check authorization
            if (!$user->hasRole('admin') && $ticket->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string',
                'is_internal' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Only admins can create internal messages
            $isInternal = $user->hasRole('admin') && $request->boolean('is_internal', false);

            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->message,
                'is_internal' => $isInternal,
            ]);

            // Update ticket status based on who replied
            if ($user->hasRole('admin')) {
                if ($ticket->status === 'waiting_admin') {
                    $ticket->update(['status' => 'waiting_customer']);
                } elseif ($ticket->status === 'open') {
                    $ticket->update(['status' => 'in_progress']);
                }
            } else {
                if (in_array($ticket->status, ['waiting_customer', 'resolved'])) {
                    $ticket->update(['status' => 'waiting_admin']);
                }
            }

            // Send email notification (only if not internal message)
            if (!$isInternal) {
                try {
                    $ticket->load(['user', 'assignedTo']);

                    // If admin replied, notify the ticket owner (customer)
                    if ($user->hasRole('admin')) {
                        if ($ticket->user && $ticket->user->email) {
                            Mail::to($ticket->user->email)->send(new TicketReplyNotification($ticket, $message));
                        }
                    } else {
                        // If customer replied, notify assigned admin or all admins with ticket permissions
                        if ($ticket->assignedTo && $ticket->assignedTo->email) {
                            // Notify the assigned admin
                            Mail::to($ticket->assignedTo->email)->send(new TicketReplyNotification($ticket, $message));
                        } else {
                            // Notify all admins with ticket.reply permission
                            $admins = \App\Models\User::permission('ticket.reply')->get();
                            foreach ($admins as $admin) {
                                if ($admin->email) {
                                    Mail::to($admin->email)->send(new TicketReplyNotification($ticket, $message));
                                }
                            }
                        }
                    }
                } catch (\Exception $emailError) {
                    // Log email error but don't fail the request
                    Log::error('Failed to send ticket reply email', [
                        'ticket_id' => $ticket->id,
                        'error' => $emailError->getMessage(),
                    ]);
                }
            }

            $message->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Message added successfully',
                'ticket_message' => $message,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding message',
            ], 500);
        }
    }

    /**
     * Close a ticket
     */
    public function close($id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check authorization - both user and admin can close
            if (!$user->hasRole('admin') && $ticket->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticket->markAsClosed();

            return response()->json([
                'success' => true,
                'message' => 'Ticket closed successfully',
                'ticket' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error closing ticket',
            ], 500);
        }
    }

    /**
     * Reopen a ticket
     */
    public function reopen($id)
    {
        try {
            $user = Auth::user();
            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check authorization
            if (!$user->hasRole('admin') && $ticket->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if (!$ticket->canBeReopened()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket cannot be reopened',
                ], 400);
            }

            $ticket->reopen();

            return response()->json([
                'success' => true,
                'message' => 'Ticket reopened successfully',
                'ticket' => $ticket,
            ]);
        } catch (\Exception $e) {
            Log::error('Error reopening ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error reopening ticket',
            ], 500);
        }
    }

    /**
     * Get ticket statistics (admin only)
     */
    public function statistics()
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $stats = [
                'total' => Ticket::count(),
                'open' => Ticket::where('status', 'open')->count(),
                'in_progress' => Ticket::where('status', 'in_progress')->count(),
                'waiting_customer' => Ticket::where('status', 'waiting_customer')->count(),
                'waiting_admin' => Ticket::where('status', 'waiting_admin')->count(),
                'resolved' => Ticket::where('status', 'resolved')->count(),
                'closed' => Ticket::where('status', 'closed')->count(),
                'by_priority' => [
                    'low' => Ticket::where('priority', 'low')->open()->count(),
                    'medium' => Ticket::where('priority', 'medium')->open()->count(),
                    'high' => Ticket::where('priority', 'high')->open()->count(),
                    'urgent' => Ticket::where('priority', 'urgent')->open()->count(),
                ],
                'unassigned' => Ticket::whereNull('assigned_to')->open()->count(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching ticket statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics',
            ], 500);
        }
    }

    /**
     * Delete a ticket (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            $ticket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting ticket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting ticket',
            ], 500);
        }
    }
}

