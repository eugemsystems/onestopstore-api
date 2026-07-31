<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAgent;
use Illuminate\Http\Request;

class WhatsappAgentController extends Controller
{
    /**
     * Public endpoint — no authentication required.
     * Returns all enabled agents with real-time availability computed.
     *
     * GET /api/whatsapp-agents
     * Optional: ?branch=Harare
     */
    public function index(Request $request)
    {
        $query = WhatsappAgent::with('jobTitle')
            ->where('chat_enabled', true)
            ->inRandomOrder();

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $agents = $query->get()
            // Available agents first, random within each group
            ->sortByDesc('is_available_now')
            ->map(function ($agent) {
            return [
                'id'                  => $agent->id,
                'name'                => $agent->name,
                'job_title'           => $agent->jobTitle?->name,
                'branch'              => $agent->branch,
                'profile_picture_url' => $agent->profile_picture_url,
                'whatsapp_number'     => $agent->whatsapp_number,
                'available_from'      => $agent->available_from,
                'available_to'        => $agent->available_to,
                'available_days'      => $agent->available_days,
                'is_available_now'    => $agent->is_available_now,
            ];
        })->values();

        // Collect unique branches for filtering
        $branches = WhatsappAgent::where('chat_enabled', true)
            ->select('branch')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->filter(fn($b) => $b && $b !== 'None')
            ->values();

        return response()->json([
            'success'  => true,
            'agents'   => $agents,
            'branches' => $branches,
        ])->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control'               => 'no-store',
        ]);
    }
}
