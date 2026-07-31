<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemTicket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SystemTicketController extends Controller
{
    public function __construct()
    {
        // Apply permission middleware to all methods
        $this->middleware('can:system-ticket.index')->only(['index']);
        $this->middleware('can:system-ticket.create')->only(['create', 'store']);
        $this->middleware('can:system-ticket.show')->only(['show']);
        $this->middleware('can:system-ticket.edit')->only(['update']);
        $this->middleware('can:system-ticket.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = SystemTicket::with(['createdBy', 'assignedTo']);

        // Role-based filtering: Only admins can see all tickets
        // Other users only see tickets assigned to them
        $user = Auth::user();
        if (!$user->hasRole(\App\Enums\RoleEnum::ADMIN)) {
            $query->where('assigned_to', $user->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned user (only for admins)
        if ($request->filled('assigned_to') && $user->hasRole(\App\Enums\RoleEnum::ADMIN)) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%")
                  ->orWhere('ticket_number', 'ilike', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get users for filter dropdown (only for admins)
        $users = $user->hasRole(\App\Enums\RoleEnum::ADMIN)
            ? User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'developer', 'tester']);
              })->get()
            : collect();

        return view('admin.system-tickets.index', compact('tickets', 'users'));
    }

    public function create()
    {
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'developer']);
        })->get();

        return view('admin.system-tickets.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'category' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:users,id',
            'attachments.*' => 'nullable|image|max:5120', // 5MB max per image
        ]);

        try {
            $attachmentIds = [];

            // Handle attachments - upload to laravel-media server
            if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    $attachment = $this->uploadImage($file, 'system-tickets');

                    // Store the numeric ID from the attachment
                    if ($attachment && $attachment->id) {
                        $attachmentIds[] = $attachment->id;
                    }
                }

            }

            $ticket = SystemTicket::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'category' => $validated['category'] ?? null,
                'status' => 'open',
                'created_by' => Auth::id(),
                'assigned_to' => $validated['assigned_to'] ?? null,
                'attachments' => $attachmentIds,
            ]);

            // Log activity
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'comment' => 'Ticket created',
            ]);

            if ($validated['assigned_to'] ?? null) {
                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'assigned',
                    'comment' => 'Ticket assigned to ' . User::find($validated['assigned_to'])->name,
                    'new_value' => $validated['assigned_to'],
                ]);
            }

            return redirect()->route('admin.system-tickets.show', $ticket->id)
                ->with('success', 'Ticket created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating system ticket', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to create ticket. Please try again.');
        }
    }

    public function show($id)
    {
        $ticket = SystemTicket::with(['createdBy', 'assignedTo', 'closedBy', 'activities.user'])
            ->findOrFail($id);

        // Role-based access: Non-admins can only view tickets assigned to them
        $user = Auth::user();
        if (!$user->hasRole(\App\Enums\RoleEnum::ADMIN)) {
            if ($ticket->assigned_to !== $user->id) {
                abort(403, 'You do not have permission to view this ticket.');
            }
        }

        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'developer', 'tester']);
        })->get();

        return view('admin.system-tickets.show', compact('ticket', 'users'));
    }

    public function update(Request $request, $id)
    {
        $ticket = SystemTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:open,in_progress,testing,closed,reopened',
            'priority' => 'nullable|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'comment' => 'nullable|string',
            'attachments.*' => 'nullable|image|max:5120',
        ]);

        try {
            $changes = [];

            // Track status change
            if ($request->filled('status') && $ticket->status !== $validated['status']) {
                $oldStatus = $ticket->status;
                $ticket->status = $validated['status'];
                $changes['status'] = ['old' => $oldStatus, 'new' => $validated['status']];

                // If closing ticket
                if ($validated['status'] === 'closed') {
                    $ticket->closed_by = Auth::id();
                    $ticket->closed_at = now();
                }

                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'status_changed',
                    'comment' => "Status changed from {$oldStatus} to {$validated['status']}",
                    'old_value' => $oldStatus,
                    'new_value' => $validated['status'],
                ]);
            }

            // Track priority change
            if ($request->filled('priority') && $ticket->priority !== $validated['priority']) {
                $oldPriority = $ticket->priority;
                $ticket->priority = $validated['priority'];
                $changes['priority'] = ['old' => $oldPriority, 'new' => $validated['priority']];

                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'comment' => "Priority changed from {$oldPriority} to {$validated['priority']}",
                    'old_value' => $oldPriority,
                    'new_value' => $validated['priority'],
                ]);
            }

            // Track assignment change
            if ($request->has('assigned_to') && $ticket->assigned_to != $validated['assigned_to']) {
                $oldAssignee = $ticket->assigned_to ? User::find($ticket->assigned_to)->name : 'Unassigned';
                $newAssignee = $validated['assigned_to'] ? User::find($validated['assigned_to'])->name : 'Unassigned';
                $ticket->assigned_to = $validated['assigned_to'];
                $changes['assigned_to'] = ['old' => $ticket->assigned_to, 'new' => $validated['assigned_to']];

                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'assigned',
                    'comment' => "Assigned from {$oldAssignee} to {$newAssignee}",
                    'old_value' => $ticket->assigned_to,
                    'new_value' => $validated['assigned_to'],
                ]);
            }

            $ticket->save();

            // Add comment if provided
            if ($request->filled('comment')) {
                $attachmentIds = [];

                // Handle comment attachments - upload to laravel-media server
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $attachment = $this->uploadImage($file, 'system-tickets');
                        $attachmentIds[] = $attachment->id;
                    }
                }

                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'action' => 'commented',
                    'comment' => $validated['comment'],
                    'attachments' => $attachmentIds,
                ]);
            }

            return redirect()->back()->with('success', 'Ticket updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating system ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to update ticket. Please try again.');
        }
    }

    public function reopen($id)
    {
        $ticket = SystemTicket::findOrFail($id);

        try {
            $ticket->update([
                'status' => 'reopened',
                'closed_by' => null,
                'closed_at' => null,
            ]);

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'reopened',
                'comment' => 'Ticket reopened by ' . Auth::user()->name,
            ]);

            return redirect()->back()->with('success', 'Ticket reopened successfully.');
        } catch (\Exception $e) {
            Log::error('Error reopening system ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to reopen ticket. Please try again.');
        }
    }

    public function destroy($id)
    {
        $ticket = SystemTicket::findOrFail($id);

        try {
            // Note: We don't delete attachments from media server as they might be referenced elsewhere
            // The attachments table already handles soft deletes if needed

            $ticket->delete();

            return redirect()->route('admin.system-tickets.index')
                ->with('success', 'Ticket deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting system ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to delete ticket. Please try again.');
        }
    }

    /**
     * Upload image to laravel-media server and create attachment record
     */
    private function uploadImage($file, $collectionName = 'system-tickets')
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();

        try {
            // Upload to external laravel-media API
            $mediaApiUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));


            $response = \Illuminate\Support\Facades\Http::timeout(60)
                ->attach(
                    'image', // The field name expected by laravel-media API
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )->post($mediaApiUrl . '/attachments/from-api', [
                    'model_id' => $uuid,
                    'model_type' => 'SystemTicket',
                    'collection_name' => $collectionName,
                ]);


            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = $response->json();

                Log::error('Failed to upload to media service', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'media_api_url' => $mediaApiUrl,
                ]);

                $errorMessage = $errorJson['message'] ?? $errorBody ?? 'Unknown error';
                throw new \Exception('Failed to upload to media service (Status: ' . $response->status() . '): ' . $errorMessage);
            }

            $uploadedFile = $response->json('files')[0] ?? null;

            if (!$uploadedFile) {
                Log::error('No file data returned from media service', [
                    'response' => $response->json()
                ]);
                throw new \Exception('No file data returned from media service');
            }

            // Check if attachment already exists (to avoid duplicate UUID error)
            $attachment = \App\Models\Attachment::where('uuid', $uploadedFile['uuid'])->first();

            if ($attachment) {
                // Update existing attachment record
                Log::warning('Attachment UUID already exists in database - updating instead of creating', [
                    'uuid' => $uploadedFile['uuid'],
                    'existing_id' => $attachment->id,
                ]);

                $attachment->update([
                    'name' => $uploadedFile['name'] ?? $uploadedFile['file_name'],
                    'file_name' => $uploadedFile['file_name'],
                    'mime_type' => $uploadedFile['mime_type'],
                    'disk' => $uploadedFile['disk'] ?? 'public',
                    'collection_name' => $uploadedFile['collection_name'] ?? $collectionName,
                    'size' => $uploadedFile['size'] ?? 0,
                    'original_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'image_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'model_id' => $uuid,
                    'model_type' => 'SystemTicket',
                ]);
            } else {
                // Create new attachment record locally in laravel-api database
                // The actual file is on laravel-media server, we just reference it
                // DO NOT set 'id' - let database auto-generate it (id is bigint, not UUID)
                $attachment = \App\Models\Attachment::create([
                    'uuid' => $uploadedFile['uuid'], // Use the UUID from laravel-media
                    'name' => $uploadedFile['name'] ?? $uploadedFile['file_name'],
                    'file_name' => $uploadedFile['file_name'],
                    'mime_type' => $uploadedFile['mime_type'],
                    'disk' => $uploadedFile['disk'] ?? 'public',
                    'collection_name' => $uploadedFile['collection_name'] ?? $collectionName,
                    'size' => $uploadedFile['size'] ?? 0,
                    'original_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'image_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'model_id' => $uuid,
                    'model_type' => 'SystemTicket',
                ]);

                // Since the Attachment model has incrementing=false, the ID won't be auto-populated
                // We need to query it back by UUID to get the database-generated ID
                $attachment = \App\Models\Attachment::where('uuid', $uploadedFile['uuid'])->first();

            }

            return $attachment;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('Cannot connect to media server. Please ensure the media service is running and accessible.');
        } catch (\Exception $e) {

            throw $e;
        }
    }
}
