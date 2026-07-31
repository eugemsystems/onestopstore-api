<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Analytics\UserSession;
use App\Models\Analytics\CartAbandonment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:detect-abandoned-carts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect truly abandoned carts (24+ hours old with no completed order)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Detecting abandoned carts...');

        $abandonmentThreshold = now()->subHours(24); // 24 hours ago
        $detected = 0;
        $skipped = 0;

        // Find sessions with cart activity but no recent activity and no completed order
        $sessionsWithCartActivity = UserSession::where('last_activity_at', '<', $abandonmentThreshold)
            ->whereNotNull('last_activity_at')
            ->where('created_at', '>=', now()->subDays(30)) // Only check last 30 days
            ->get();

        foreach ($sessionsWithCartActivity as $session) {
            // Check if this session already has a cart abandonment record
            $existingAbandonment = CartAbandonment::where('session_id', $session->session_id)
                ->where('recovered', false)
                ->first();

            if ($existingAbandonment) {
                $skipped++;
                continue; // Already tracked
            }

            // Check if this session has a completed order
            $hasCompletedOrder = Order::where('created_at', '>=', $session->created_at)
                ->where('created_at', '<=', $session->last_activity_at->addHours(2))
                ->where(function($q) use ($session) {
                    // Try to match by user_id if session has user
                    if ($session->user_id) {
                        $q->where('consumer_id', $session->user_id);
                    }
                })
                ->exists();

            if ($hasCompletedOrder) {
                $skipped++;
                continue; // Order was completed, not abandoned
            }

            // Check if there are any add_to_cart events for this session
            $hasCartEvents = DB::table('user_events')
                ->where('session_id', $session->session_id)
                ->where('event_name', 'add_to_cart')
                ->exists();

            if (!$hasCartEvents) {
                $skipped++;
                continue; // No cart activity, so no abandonment
            }

            // Try to reconstruct cart from events
            $cartItems = $this->reconstructCartFromEvents($session->session_id);

            if (empty($cartItems)) {
                $skipped++;
                continue; // Can't determine cart contents
            }

            // Create abandonment record
            CartAbandonment::create([
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
                'email' => null, // We don't have email from events
                'cart_items' => $cartItems,
                'cart_value' => collect($cartItems)->sum(function($item) {
                    return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                }),
                'currency' => 'USD',
                'items_count' => count($cartItems),
                'abandonment_stage' => 'cart',
                'abandonment_reason' => 'Detected via 24h inactivity check',
                'recovered' => false,
                'ip_address' => $session->ip_address,
                'device_type' => $session->device_type,
            ]);

            $detected++;
        }

        $this->info("✅ Detection complete:");
        $this->info("   - Detected: {$detected} abandoned carts");
        $this->info("   - Skipped: {$skipped} (already tracked or completed)");

        return Command::SUCCESS;
    }

    /**
     * Reconstruct cart items from add_to_cart events
     */
    protected function reconstructCartFromEvents($sessionId)
    {
        $addEvents = DB::table('user_events')
            ->where('session_id', $sessionId)
            ->where('event_name', 'add_to_cart')
            ->whereNotNull('event_data')
            ->orderBy('created_at', 'desc')
            ->limit(50) // Safety limit
            ->get();

        $removeEvents = DB::table('user_events')
            ->where('session_id', $sessionId)
            ->where('event_name', 'remove_from_cart')
            ->whereNotNull('event_data')
            ->get();

        $cartItems = [];
        $removedIds = [];

        // Get removed product IDs
        foreach ($removeEvents as $event) {
            $data = json_decode($event->event_data, true);
            if (isset($data['product_id'])) {
                $removedIds[] = $data['product_id'];
            }
        }

        // Build cart from add events (excluding removed items)
        foreach ($addEvents as $event) {
            $data = json_decode($event->event_data, true);
            $productId = $data['product_id'] ?? null;

            if ($productId && !in_array($productId, $removedIds)) {
                $cartItems[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $data['product_name'] ?? 'Unknown Product',
                    'price' => $data['product_price'] ?? 0,
                    'quantity' => $data['quantity'] ?? 1,
                ];
            }
        }

        return array_values($cartItems);
    }
}

