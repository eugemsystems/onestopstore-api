<?php

namespace App\Console\Commands;

use App\Mail\AuctionWonMail;
use App\Mail\ContactUs;
use App\Mail\ForgotPassword;
use App\Mail\GiftVoucherMail;
use App\Mail\InvoiceQuotationMail;
use App\Mail\PendingOrderReminder;
use App\Mail\TicketReplyNotification;
use App\Mail\VendorApplicationApproved;
use App\Mail\VendorApplicationRejected;
use App\Mail\VendorBanned;
use App\Models\Voucher;
use App\Mail\WithdrawalApprovedMail;
use App\Models\AuctionItem;
use App\Models\GiftVoucher;
use App\Models\InvoiceQuotation;
use App\Models\LaybyApplication;
use App\Models\Order;
use App\Models\Store;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\WithdrawRequest;
use App\Notifications\PlaceOrderNotification;
use App\Notifications\UpdateOrderStatusNotification;
use App\Enums\RoleEnum;
use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;

class TestAllEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-all
                            {--to= : Override recipient address (default: MAIL_FROM_ADDRESS)}
                            {--only= : Comma-separated list of email keys to test (e.g. forgot-password,contact-us)}
                            {--list : List all available email test keys}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email for every Mail class using real data from the database.';

    // ---------------------------------------------------------------------------
    // Registry — add a new entry here when a new Mailable is created
    // ---------------------------------------------------------------------------
    private array $registry = [
        'forgot-password'            => 'testForgotPassword',
        'contact-us'                 => 'testContactUs',
        'invoice-quotation'          => 'testInvoiceQuotation',
        'ticket-reply'               => 'testTicketReply',
        'auction-won'                => 'testAuctionWon',
        'gift-voucher'               => 'testGiftVoucher',
        'pending-order-reminder'     => 'testPendingOrderReminder',
        'vendor-approved'            => 'testVendorApplicationApproved',
        'vendor-rejected'            => 'testVendorApplicationRejected',
        'vendor-banned'              => 'testVendorBanned',
        'withdrawal-approved'        => 'testWithdrawalApproved',
        'layby-approved'             => 'testLaybyApproved',
        'layby-rejected'             => 'testLaybyRejected',
        'layby-payment-received'     => 'testLaybyPaymentReceived',
        'layby-completed'            => 'testLaybyCompleted',
        'order-placed'               => 'testOrderPlaced',
        'order-status-update'        => 'testOrderStatusUpdate',
    ];

    /** @var string */
    private string $to;

    // ---------------------------------------------------------------------------

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listKeys();
            return self::SUCCESS;
        }

        $this->to = $this->option('to') ?? env('MAIL_FROM_ADDRESS', 'admin@raines.africa');

        $only = $this->option('only')
            ? array_map('trim', explode(',', $this->option('only')))
            : array_keys($this->registry);

        $results = [];

        foreach ($only as $key) {
            if (!isset($this->registry[$key])) {
                $this->warn("  ⚠  Unknown key: {$key} — skipped");
                $results[$key] = '⚠  Unknown key';
                continue;
            }

            $this->line("  ➜  Sending <comment>{$key}</comment> …");

            try {
                $result = $this->{$this->registry[$key]}();
                if ($result === null) {
                    $results[$key] = '⚠  Skipped (no DB records)';
                    $this->warn("     Skipped — no suitable data found in DB");
                } else {
                    $results[$key] = '✅  Sent';
                    $this->info("     Sent to <{$this->to}>");
                }
            } catch (\Throwable $e) {
                $results[$key] = '❌  ' . $e->getMessage();
                $this->error("     Failed: " . $e->getMessage());
            }

            // Avoid Mailtrap free-tier rate limits (1 email/second)
            sleep(1);
        }

        $this->newLine();
        $this->table(['Template', 'Result'], collect($results)->map(
            fn ($v, $k) => [$k, $v]
        )->values()->toArray());

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function listKeys(): void
    {
        $this->line('Available email test keys:');
        foreach (array_keys($this->registry) as $key) {
            $this->line("  • {$key}");
        }
        $this->newLine();
        $this->line('Usage: php artisan email:test-all --to=me@example.com --only=forgot-password,contact-us');
    }

    private function send(object $mailable): void
    {
        Mail::to($this->to)->send($mailable);
    }

    // ---------------------------------------------------------------------------
    // Individual test methods
    // ---------------------------------------------------------------------------

    private function testForgotPassword(): ?bool
    {
        $this->send(new ForgotPassword('TEST-RESET-TOKEN-' . strtoupper(substr(md5(now()), 0, 8))));
        return true;
    }

    private function testContactUs(): ?bool
    {
        // Build a fake contact object matching every property the blade template uses
        $contact = (object) [
            'name'    => 'Jane Doe',
            'email'   => $this->to,
            'phone'   => '+263779411028',
            'subject' => 'Test enquiry from artisan command',
            'message' => 'This is a test message generated by the email:test-all artisan command to verify the contact-us template renders correctly.',
        ];
        $this->send(new ContactUs($contact));
        return true;
    }

    private function testInvoiceQuotation(): ?bool
    {
        $doc = InvoiceQuotation::with('items')->latest()->first();
        if (!$doc) {
            return null;
        }
        $this->send(new InvoiceQuotationMail($doc, 'Test custom message from artisan test command.', auth()->user()?->name ?? 'Admin'));
        return true;
    }

    private function testTicketReply(): ?bool
    {
        $message = TicketMessage::with('ticket', 'user')->latest()->first();
        if (!$message || !$message->ticket) {
            return null;
        }
        $this->send(new TicketReplyNotification($message->ticket, $message));
        return true;
    }

    private function testAuctionWon(): ?bool
    {
        // AuctionItem uses winner() relationship (BelongsTo User via winner_id)
        $auction = AuctionItem::with('winner')->whereNotNull('winner_bid')->whereNotNull('winner_id')->latest()->first();
        if (!$auction || !$auction->winner) {
            return null;
        }
        $this->send(new AuctionWonMail($auction, $auction->winner));
        return true;
    }

    private function testGiftVoucher(): ?bool
    {
        // Vouchers are stored in the `vouchers` table with an order_id column.
        // Order has no giftVouchers() relationship — query Voucher directly.
        $voucher = Voucher::with(['product'])->whereNotNull('order_id')->latest()->first();
        if (!$voucher) {
            return null;
        }
        $order = Order::find($voucher->order_id);
        if (!$order) {
            return null;
        }
        $vouchers = Voucher::with('product')->where('order_id', $order->id)->get();
        $this->send(new GiftVoucherMail($order, $vouchers));
        return true;
    }

    private function testPendingOrderReminder(): ?bool
    {
        // `currency` and `currency_symbol` are plain columns on Order, not a relationship
        $order = Order::with(['consumer', 'orderStatus'])
            ->whereHas('consumer')
            ->latest()
            ->first();
        if (!$order) {
            return null;
        }
        // Send all three reminder types (sleep between each to respect rate limits)
        foreach (['first', 'second', 'cancellation'] as $type) {
            $this->line("       • reminder type: <comment>{$type}</comment>");
            Mail::to($this->to)->send(new PendingOrderReminder($order, $type));
            sleep(1);
        }
        return true;
    }

    private function testVendorApplicationApproved(): ?bool
    {
        $store = Store::with('vendor')->latest()->first();
        if (!$store) {
            return null;
        }
        $this->send(new VendorApplicationApproved($store));
        return true;
    }

    private function testVendorApplicationRejected(): ?bool
    {
        $store = Store::with('vendor')->latest()->first();
        if (!$store) {
            return null;
        }
        $this->send(new VendorApplicationRejected($store, 'Test rejection reason: documentation was incomplete.'));
        return true;
    }

    private function testVendorBanned(): ?bool
    {
        $store = Store::with('vendor')->latest()->first();
        if (!$store) {
            return null;
        }
        $this->send(new VendorBanned($store));
        return true;
    }

    private function testWithdrawalApproved(): ?bool
    {
        $withdrawal = WithdrawRequest::with(['user', 'user.store'])->latest()->first();
        if (!$withdrawal) {
            return null;
        }
        $this->send(new WithdrawalApprovedMail($withdrawal));
        return true;
    }

    // ---------------------------------------------------------------------------
    // Layby
    // ---------------------------------------------------------------------------

    private function testLaybyApproved(): ?bool
    {
        $application = LaybyApplication::with('user')->latest()->first();
        if (!$application) {
            return null;
        }

        // Use the dedicated Layby mail class if it exists, otherwise render the view directly
        if (class_exists(\App\Mail\LaybyApplicationApproved::class)) {
            $this->send(new \App\Mail\LaybyApplicationApproved($application));
        } else {
            $this->sendLaybyView('emails.layby.approved', ['application' => $application],
                'Layby Application Approved — ' . ($application->product_name ?? ''));
        }
        return true;
    }

    private function testLaybyRejected(): ?bool
    {
        $application = LaybyApplication::with('user')->latest()->first();
        if (!$application) {
            return null;
        }

        if (class_exists(\App\Mail\LaybyApplicationRejected::class)) {
            $this->send(new \App\Mail\LaybyApplicationRejected($application, 'Test rejection reason.'));
        } else {
            $this->sendLaybyView('emails.layby.rejected', ['application' => $application],
                'Layby Application Update — ' . ($application->product_name ?? ''));
        }
        return true;
    }

    private function testLaybyPaymentReceived(): ?bool
    {
        $application = LaybyApplication::with(['user', 'payments' => fn ($q) => $q->latest()])
            ->whereHas('payments')
            ->latest()
            ->first();
        if (!$application || $application->payments->isEmpty()) {
            return null;
        }

        if (class_exists(\App\Mail\LaybyPaymentReceived::class)) {
            $this->send(new \App\Mail\LaybyPaymentReceived($application, $application->payments->first()));
        } else {
            $this->sendLaybyView('emails.layby.payment-received', [
                'application' => $application,
                'payment'     => $application->payments->first(),
            ], 'Layby Payment Received — ' . ($application->product_name ?? ''));
        }
        return true;
    }

    private function testLaybyCompleted(): ?bool
    {
        $application = LaybyApplication::with(['user', 'order'])->latest()->first();
        if (!$application) {
            return null;
        }

        if (class_exists(\App\Mail\LaybyCompleted::class)) {
            $this->send(new \App\Mail\LaybyCompleted($application));
        } else {
            $this->sendLaybyView('emails.layby.completed', [
                'application' => $application,
                'order'       => $application->order ?? null,
            ], 'Your Layby is Complete — ' . ($application->product_name ?? ''));
        }
        return true;
    }

    /**
     * Fallback: build & send a basic HTML mailable directly from a blade view
     * for layby templates that may not have a dedicated Mailable class yet.
     */
    private function sendLaybyView(string $view, array $data, string $subject): void
    {
        $noreply = env('MAIL_NOREPLY_ADDRESS', 'no-reply@raines.africa');
        $name    = env('MAIL_NOREPLY_NAME', 'Raines Africa');

        Mail::send($view, $data, function (\Illuminate\Mail\Message $msg) use ($subject, $noreply, $name) {
            $msg->to($this->to)
                ->from($noreply, $name)
                ->subject($subject);
        });
    }

    // ---------------------------------------------------------------------------
    // Order Notifications
    // ---------------------------------------------------------------------------

    /**
     * Test PlaceOrderNotification for all three roles (consumer, vendor, admin).
     * Uses AnonymousNotifiable so the mail channel routes to $this->to without
     * touching the database or Slack channels.
     */
    private function testOrderPlaced(): ?bool
    {
        $order = Order::with(['consumer', 'products', 'sub_orders.products', 'billing_address', 'shipping_address', 'orderStatus'])
            ->whereHas('consumer')
            ->latest()
            ->first();

        if (!$order) {
            return null;
        }

        foreach ([RoleEnum::CONSUMER, RoleEnum::VENDOR, RoleEnum::ADMIN] as $role) {
            $roleName = is_string($role) ? $role : $role->value;
            $this->line("       • role: <comment>{$roleName}</comment>");

            \Illuminate\Support\Facades\Notification::route('mail', $this->to)
                ->notify((new PlaceOrderNotification($order, $roleName))->onConnection('sync'));

            sleep(1);
        }

        return true;
    }

    /**
     * Test UpdateOrderStatusNotification using the latest order from DB.
     */
    private function testOrderStatusUpdate(): ?bool
    {
        $order = Order::with(['consumer', 'products', 'sub_orders.products', 'billing_address', 'shipping_address', 'orderStatus'])
            ->whereHas('consumer')
            ->latest()
            ->first();

        if (!$order) {
            return null;
        }

        \Illuminate\Support\Facades\Notification::route('mail', $this->to)
            ->notify((new UpdateOrderStatusNotification($order))->onConnection('sync'));

        return true;
    }
}
