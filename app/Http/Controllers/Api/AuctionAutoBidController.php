<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionAutoBid;
use App\Models\AuctionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionAutoBidController extends Controller
{
    /** GET /api/auctions/{id}/auto-bid — get user's current auto-bid */
    public function show(Request $request, int $id): JsonResponse
    {
        AuctionItem::where('status', '!=', 'draft')->findOrFail($id);

        $autoBid = AuctionAutoBid::where('auction_item_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'auto_bid'   => $autoBid ? [
                'id'         => $autoBid->id,
                'max_amount' => (float) $autoBid->max_amount,
                'is_active'  => $autoBid->is_active,
            ] : null,
        ]);
    }

    /** POST /api/auctions/{id}/auto-bid — set or update max bid */
    public function store(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'max_amount' => 'required|numeric|min:0.01',
        ]);

        $auction = AuctionItem::where('status', '!=', 'draft')->findOrFail($id);

        if (!$auction->isActive()) {
            return response()->json(['message' => 'This auction is not currently active.'], 422);
        }

        $user = $request->user();

        if ($auction->created_by === $user->id) {
            return response()->json(['message' => 'You cannot set an auto-bid on your own auction.'], 422);
        }

        $minBid = $auction->minimumNextBid();
        if ((float) $request->max_amount < $minBid) {
            return response()->json([
                'message' => 'Your auto-bid maximum must be at least $' . number_format($minBid, 2) . ' (the current minimum next bid).',
            ], 422);
        }

        // Upsert — one active auto-bid per user per auction
        $autoBid = AuctionAutoBid::updateOrCreate(
            ['auction_item_id' => $id, 'user_id' => $user->id],
            ['max_amount' => $request->max_amount, 'is_active' => true],
        );

        return response()->json([
            'message'  => 'Auto-bid set successfully.',
            'auto_bid' => [
                'id'         => $autoBid->id,
                'max_amount' => (float) $autoBid->max_amount,
                'is_active'  => true,
            ],
        ]);
    }

    /** DELETE /api/auctions/{id}/auto-bid — cancel auto-bid */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = AuctionAutoBid::where('auction_item_id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['is_active' => false]);

        return response()->json([
            'message' => $deleted ? 'Auto-bid cancelled.' : 'No active auto-bid found.',
        ]);
    }
}
