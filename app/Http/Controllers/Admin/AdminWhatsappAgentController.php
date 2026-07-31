<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\WhatsappAgent;
use App\Models\WhatsappJobTitle;
use Illuminate\Http\Request;

class AdminWhatsappAgentController extends BaseAdminController
{
    protected string $permissionPrefix = 'user';

    private const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    public function index(Request $request)
    {
        $agents = WhatsappAgent::with(['jobTitle', 'user:id,name,email'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $jobTitles = WhatsappJobTitle::orderBy('sort_order')->orderBy('name')->get();
        $users = User::select('id', 'name', 'email', 'branch')->orderBy('name')->get();

        return view('admin.whatsapp.agents.index', compact('agents', 'jobTitles', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:150',
            'whatsapp_number'=> 'required|string|max:30',
            'job_title_id'   => 'nullable|exists:whatsapp_job_titles,id',
            'branch'         => 'nullable|string|max:100',
            'chat_enabled'   => 'boolean',
            'available_from' => 'nullable|date_format:H:i,H:i:s',
            'available_to'   => 'nullable|date_format:H:i,H:i:s',
            'available_days' => 'nullable|array',
            'available_days.*'=> 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'user_id'        => 'nullable|exists:users,id',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $agent = WhatsappAgent::create([
            'user_id'         => $request->user_id,
            'name'            => $request->name,
            'whatsapp_number' => $request->whatsapp_number,
            'job_title_id'    => $request->job_title_id,
            'branch'          => $request->branch ?? 'None',
            'chat_enabled'    => $request->boolean('chat_enabled'),
            'available_from'  => $request->available_from ? substr($request->available_from, 0, 5) : null,
            'available_to'    => $request->available_to   ? substr($request->available_to,   0, 5) : null,
            'available_days'  => $request->available_days ?? self::DAYS,
            'sort_order'      => $request->sort_order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agent added successfully.',
            'data'    => $agent->load('jobTitle'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $agent = WhatsappAgent::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:150',
            'whatsapp_number'=> 'required|string|max:30',
            'job_title_id'   => 'nullable|exists:whatsapp_job_titles,id',
            'branch'         => 'nullable|string|max:100',
            'chat_enabled'   => 'boolean',
            'available_from' => 'nullable|date_format:H:i,H:i:s',
            'available_to'   => 'nullable|date_format:H:i,H:i:s',
            'available_days' => 'nullable|array',
            'available_days.*'=> 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'user_id'        => 'nullable|exists:users,id',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $agent->update([
            'user_id'         => $request->user_id,
            'name'            => $request->name,
            'whatsapp_number' => $request->whatsapp_number,
            'job_title_id'    => $request->job_title_id,
            'branch'          => $request->branch ?? 'None',
            'chat_enabled'    => $request->boolean('chat_enabled'),
            'available_from'  => $request->available_from ? substr($request->available_from, 0, 5) : null,
            'available_to'    => $request->available_to   ? substr($request->available_to,   0, 5) : null,
            'available_days'  => $request->available_days ?? self::DAYS,
            'sort_order'      => $request->sort_order ?? $agent->sort_order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agent updated successfully.',
            'data'    => $agent->fresh('jobTitle'),
        ]);
    }

    public function destroy($id)
    {
        $agent = WhatsappAgent::findOrFail($id);
        $agent->delete();
        return response()->json(['success' => true, 'message' => 'Agent removed.']);
    }

    /**
     * Upload profile picture for an agent.
     */
    public function uploadPhoto(Request $request, $id)
    {
        $agent = WhatsappAgent::findOrFail($id);
        $request->validate(['photo' => 'required|image|max:2048']);

        $url = $this->uploadToMediaService($request->file('photo'));
        $agent->update(['profile_picture_url' => $url]);

        return response()->json(['success' => true, 'profile_picture_url' => $url]);
    }

    /**
     * The logged-in user updates their OWN agent record (limited fields).
     */
    public function updateMyProfile(Request $request)
    {
        $user  = auth()->user();
        $agent = WhatsappAgent::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'chat_enabled'   => 'boolean',
            'available_from' => 'nullable|date_format:H:i',
            'available_to'   => 'nullable|date_format:H:i',
            'available_days' => 'nullable|array',
            'available_days.*'=> 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        ]);

        $agent->update([
            'chat_enabled'  => $request->boolean('chat_enabled'),
            'available_from'=> $request->available_from,
            'available_to'  => $request->available_to,
            'available_days'=> $request->available_days ?? $agent->available_days,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat availability updated.',
            'data'    => $agent->fresh(),
        ]);
    }

    /**
     * Upload own profile picture (for profile page).
     */
    public function uploadMyPhoto(Request $request)
    {
        $user  = auth()->user();
        $agent = WhatsappAgent::where('user_id', $user->id)->firstOrFail();
        $request->validate(['photo' => 'required|image|max:2048']);

        $url = $this->uploadToMediaService($request->file('photo'));
        $agent->update(['profile_picture_url' => $url]);

        return response()->json(['success' => true, 'profile_picture_url' => $url]);
    }

    /**
     * Upload a file to the laravel-media service and return the public URL.
     * Same approach used by AdminProductController::uploadImage().
     */
    private function uploadToMediaService($file): string
    {
        $mediaApiUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));

        $response = \Illuminate\Support\Facades\Http::attach(
            'image',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post($mediaApiUrl . '/attachments/from-api', [
            'model_id'        => (string) \Illuminate\Support\Str::uuid(),
            'model_type'      => 'WhatsappAgent',
            'collection_name' => 'whatsapp-agents',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to upload photo to media service: ' . $response->body());
        }

        $uploadedFile = $response->json('files')[0] ?? null;
        if (!$uploadedFile) {
            throw new \Exception('No file data returned from media service');
        }

        return $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? '';
    }

    /**
     * Return the agent record for a given user (AJAX — used in users/edit).
     */
    public function getByUser($userId)
    {
        $agent = WhatsappAgent::with('jobTitle')->where('user_id', $userId)->first();
        $jobTitles = WhatsappJobTitle::orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success'    => true,
            'agent'      => $agent,
            'job_titles' => $jobTitles,
        ]);
    }

    /**
     * AJAX: return users belonging to a given role (excluding customer roles).
     * GET /admin/whatsapp/users-by-role?role=staff
     */
    public function getUsersByRole(Request $request)
    {
        $role = $request->query('role', '');

        $users = User::select('id', 'name', 'email', 'branch')
            ->when($role, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $role)))
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'branch' => $u->branch ?? '',
            ]);

        return response()->json(['success' => true, 'users' => $users]);
    }

    /**
     * AJAX: return distinct non-customer roles that have at least one user.
     * GET /admin/whatsapp/roles
     */
    public function getRoles()
    {
        $excluded = ['customer', 'consumer'];

        $roles = \Spatie\Permission\Models\Role::whereNotIn('name', $excluded)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'roles' => $roles]);
    }
}
