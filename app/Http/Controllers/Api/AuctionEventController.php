<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionEvent;
use App\Models\AuctionItem;
use Illuminate\Http\Request;

class AuctionEventController extends Controller
{
    private const ALLOWED_EVENTS = [
        'page_view',
        'image_click',
        'bid_focus',
        'bid_submit',
        'bid_success',
        'bid_error',
        'product_link_click',
        'countdown_expired',
        'tab_switch',
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'auction_item_id' => 'required|integer|exists:auction_items,id',
            'event'           => 'required|string|in:' . implode(',', self::ALLOWED_EVENTS),
            'meta'            => 'nullable|array',
            'session_id'      => 'nullable|string|max:64',
        ]);

        AuctionEvent::create([
            'auction_item_id' => $data['auction_item_id'],
            'event'           => $data['event'],
            'meta'            => $data['meta'] ?? null,
            'session_id'      => $data['session_id'] ?? null,
            'ip'              => $request->ip(),
            'user_agent'      => substr($request->userAgent() ?? '', 0, 500),
            'created_at'      => now(),
        ]);

        return response()->json(['ok' => true], 201);
    }
}
