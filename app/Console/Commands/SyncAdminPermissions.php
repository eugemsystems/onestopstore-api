<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-admin {--fresh : Delete all existing permissions and recreate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all admin panel permissions based on routes and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing Admin Permissions...');
        $this->newLine();

        // Define all admin permissions
        $permissions = $this->getAdminPermissions();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Fresh mode: Deleting all existing permissions...');
            Permission::query()->delete();
            $this->info('✓ All permissions deleted');
            $this->newLine();
        }

        $created = 0;
        $existing = 0;

        foreach ($permissions as $category => $perms) {
            $this->info("📁 {$category}");

            foreach ($perms as $permission) {
                $perm = Permission::firstOrCreate(
                    ['name' => $permission['name']],
                    ['guard_name' => 'web']
                );

                if ($perm->wasRecentlyCreated) {
                    $this->line("  ✓ Created: {$permission['name']} - {$permission['description']}");
                    $created++;
                } else {
                    $this->line("  - Exists: {$permission['name']}");
                    $existing++;
                }
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("✅ Sync Complete!");
        $this->info("   Created: {$created} permissions");
        $this->info("   Existing: {$existing} permissions");
        $this->info("   Total: " . ($created + $existing) . " permissions");

        // Ask if admin role should get all permissions
        if ($this->confirm('Do you want to assign all permissions to the admin role?', true)) {
            $this->assignToAdminRole();
        }

        return Command::SUCCESS;
    }

    /**
     * Get all admin permissions organized by category
     */
    protected function getAdminPermissions(): array
    {
        return [
            'Dashboard' => [
                ['name' => 'dashboard.view', 'description' => 'View admin dashboard'],
            ],

            'Orders' => [
                ['name' => 'order.index', 'description' => 'View all orders'],
                ['name' => 'order.create', 'description' => 'Create new order'],
                ['name' => 'order.show', 'description' => 'View order details'],
                ['name' => 'order.edit', 'description' => 'Edit order'],
                ['name' => 'order.delete', 'description' => 'Delete order'],
                ['name' => 'order.update-status', 'description' => 'Update order status'],
                ['name' => 'order.update-payment', 'description' => 'Update payment method'],
                ['name' => 'order.toggle-fast-shipping', 'description' => 'Toggle fast shipping'],
                ['name' => 'order.get-it-tomorrow', 'description' => 'View Get It Tomorrow orders page'],
                ['name' => 'order.same-day-delivery', 'description' => 'View Same Day Delivery orders page'],
            ],

            'Takealot Link Builder' => [
                ['name' => 'processing-link-builder.index', 'description' => 'View processing link builder page'],
                ['name' => 'processing-link-builder.transfer-to-inventory', 'description' => 'Transfer items to inventory shipments'],
            ],

            'Order Statistics' => [
                ['name' => 'order-stats.view', 'description' => 'View order statistics'],
                ['name' => 'order-stats.overview', 'description' => 'View stats overview'],
                ['name' => 'order-stats.products', 'description' => 'View product stats'],
                ['name' => 'order-stats.customers', 'description' => 'View customer stats'],
            ],

            'Order Reminders' => [
                ['name' => 'order-reminder.index', 'description' => 'View order reminders'],
                ['name' => 'order-reminder.settings', 'description' => 'Manage reminder settings'],
                ['name' => 'order-reminder.resend', 'description' => 'Resend reminders'],
            ],

            'Order Item Search' => [
                ['name' => 'order-item.search', 'description' => 'Search order items'],
            ],

            'Order Notes' => [
                ['name' => 'order-note.create', 'description' => 'Add order notes'],
                ['name' => 'order-note.edit', 'description' => 'Edit order notes'],
                ['name' => 'order-note.delete', 'description' => 'Delete order notes'],
            ],

            'Products' => [
                ['name' => 'product.index', 'description' => 'View all products'],
                ['name' => 'product.create', 'description' => 'Create new product'],
                ['name' => 'product.edit', 'description' => 'Edit product'],
                ['name' => 'product.destroy', 'description' => 'Delete product'],
                ['name' => 'product.toggle-status', 'description' => 'Toggle product status'],
                ['name' => 'product.bulk-disable', 'description' => 'Bulk disable products by SKU'],
            ],

            'Product Variations' => [
                ['name' => 'variation.create', 'description' => 'Create product variation'],
                ['name' => 'variation.edit', 'description' => 'Edit product variation'],
                ['name' => 'variation.delete', 'description' => 'Delete product variation'],
            ],

            'Product Attributes' => [
                ['name' => 'attribute.index', 'description' => 'View attributes'],
                ['name' => 'attribute.create', 'description' => 'Create attribute'],
                ['name' => 'attribute.edit', 'description' => 'Edit attribute'],
                ['name' => 'attribute.destroy', 'description' => 'Delete attribute'],
            ],

            'Product Feed' => [
                ['name' => 'product-feed.index', 'description' => 'View product feed'],
                ['name' => 'product-feed.export', 'description' => 'Export product feed'],
                ['name' => 'product-feed.export-tsv', 'description' => 'Export TSV feed'],
            ],

            'Product Import' => [
                ['name' => 'import.index', 'description' => 'View import page'],
                ['name' => 'import.upload', 'description' => 'Upload import file'],
                ['name' => 'import.run', 'description' => 'Run import process'],
            ],

            'Layby' => [
                ['name' => 'layby.index', 'description' => 'View layby applications'],
                ['name' => 'layby.show', 'description' => 'View layby details'],
                ['name' => 'layby.update-status', 'description' => 'Update layby status'],
                ['name' => 'layby.capture-payment', 'description' => 'Capture layby payment'],
                ['name' => 'layby.edit-payment', 'description' => 'Edit layby payment'],
                ['name' => 'layby.delete-payment', 'description' => 'Delete layby payment'],
                ['name' => 'layby.settings', 'description' => 'Manage layby settings'],
            ],

            'Marketing' => [
                ['name' => 'marketing-feedback.index', 'description' => 'View marketing feedback'],
                ['name' => 'marketing-feedback.show', 'description' => 'View feedback details'],
                ['name' => 'marketing-feedback.delete', 'description' => 'Delete feedback'],
                ['name' => 'marketing-feedback.export', 'description' => 'Export feedback'],
            ],

            'Analytics' => [
                ['name' => 'analytics.dashboard', 'description' => 'View analytics dashboard'],
                ['name' => 'analytics.sessions', 'description' => 'View session analytics'],
                ['name' => 'analytics.cart-abandonments', 'description' => 'View cart abandonments'],
                ['name' => 'activity-log.view', 'description' => 'View audit trail / activity log'],
                ['name' => 'activity-log.export', 'description' => 'Export audit trail data'],
            ],

            'System Configuration' => [
                ['name' => 'settings.index', 'description' => 'View settings'],
                ['name' => 'settings.edit', 'description' => 'Edit settings'],
                ['name' => 'theme-options.index', 'description' => 'View theme options'],
                ['name' => 'theme-options.edit', 'description' => 'Edit theme options'],
                ['name' => 'home-pages.index', 'description' => 'View home pages'],
                ['name' => 'home-pages.create', 'description' => 'Create home page'],
                ['name' => 'home-pages.edit', 'description' => 'Edit home page'],
                ['name' => 'home-pages.delete', 'description' => 'Delete home page'],
            ],

            'Users & Roles' => [
                ['name' => 'user.index', 'description' => 'View all users'],
                ['name' => 'user.create', 'description' => 'Create new user'],
                ['name' => 'user.edit', 'description' => 'Edit user'],
                ['name' => 'user.destroy', 'description' => 'Delete user'],
                ['name' => 'user.assign-roles', 'description' => 'Assign roles to users'],
                ['name' => 'user.assign-permissions', 'description' => 'Assign permissions to users'],
                ['name' => 'role.index', 'description' => 'View all roles'],
                ['name' => 'role.create', 'description' => 'Create new role'],
                ['name' => 'role.edit', 'description' => 'Edit role'],
                ['name' => 'role.destroy', 'description' => 'Delete role'],
            ],

            'Categories' => [
                ['name' => 'category.index', 'description' => 'View categories'],
                ['name' => 'category.create', 'description' => 'Create category'],
                ['name' => 'category.edit', 'description' => 'Edit category'],
                ['name' => 'category.destroy', 'description' => 'Delete category'],
            ],

            'Tags' => [
                ['name' => 'tag.index', 'description' => 'View tags'],
                ['name' => 'tag.create', 'description' => 'Create tag'],
                ['name' => 'tag.edit', 'description' => 'Edit tag'],
                ['name' => 'tag.destroy', 'description' => 'Delete tag'],
            ],

            'Attachments' => [
                ['name' => 'attachment.index', 'description' => 'View attachments'],
                ['name' => 'attachment.create', 'description' => 'Upload attachment'],
                ['name' => 'attachment.destroy', 'description' => 'Delete attachment'],
            ],

            'Currencies' => [
                ['name' => 'currency.index', 'description' => 'View currencies'],
                ['name' => 'currency.create', 'description' => 'Create currency'],
                ['name' => 'currency.edit', 'description' => 'Edit currency'],
                ['name' => 'currency.destroy', 'description' => 'Delete currency'],
            ],

            'Coupons' => [
                ['name' => 'coupon.index', 'description' => 'View coupons'],
                ['name' => 'coupon.create', 'description' => 'Create coupon'],
                ['name' => 'coupon.edit', 'description' => 'Edit coupon'],
                ['name' => 'coupon.destroy', 'description' => 'Delete coupon'],
            ],

            'Shipping' => [
                ['name' => 'shipping.index', 'description' => 'View shipping methods'],
                ['name' => 'shipping.create', 'description' => 'Create shipping method'],
                ['name' => 'shipping.edit', 'description' => 'Edit shipping method'],
                ['name' => 'shipping.destroy', 'description' => 'Delete shipping method'],
            ],

            'Taxes' => [
                ['name' => 'tax.index', 'description' => 'View taxes'],
                ['name' => 'tax.create', 'description' => 'Create tax'],
                ['name' => 'tax.edit', 'description' => 'Edit tax'],
                ['name' => 'tax.destroy', 'description' => 'Delete tax'],
            ],
            'Inventory Shipments' => [
                ['name' => 'inventory-shipment.index', 'description' => 'View inventory shipments'],
                ['name' => 'inventory-shipment.create', 'description' => 'Create inventory shipment'],
                ['name' => 'inventory-shipment.show', 'description' => 'View shipment details'],
                ['name' => 'inventory-shipment.edit', 'description' => 'Edit inventory shipment'],
                ['name' => 'inventory-shipment.destroy', 'description' => 'Delete inventory shipment'],
                ['name' => 'inventory-shipment.download-waybill', 'description' => 'Download shipment waybill PDF (one-time for non-admins)'],
                ['name' => 'inventory-shipment.download-sticker', 'description' => 'Download shipment sticker PDF (one-time for non-admins)'],
            ],

            'Support Tickets' => [
                ['name' => 'ticket.index', 'description' => 'View all tickets'],
                ['name' => 'ticket.show', 'description' => 'View ticket details'],
                ['name' => 'ticket.reply', 'description' => 'Reply to tickets'],
                ['name' => 'ticket.update-status', 'description' => 'Update ticket status'],
                ['name' => 'ticket.assign', 'description' => 'Assign tickets to users'],
                ['name' => 'ticket.close', 'description' => 'Close tickets'],
                ['name' => 'ticket.reopen', 'description' => 'Reopen closed tickets'],
                ['name' => 'ticket.delete', 'description' => 'Delete tickets'],
            ],

            'Customer Management' => [
                ['name' => 'wallet.index', 'description' => 'View customer wallets'],
                ['name' => 'wallet.show', 'description' => 'View wallet details'],
                ['name' => 'wallet.edit', 'description' => 'Edit wallet (credit/debit)'],
                ['name' => 'point.index', 'description' => 'View customer points'],
                ['name' => 'point.show', 'description' => 'View point details'],
                ['name' => 'point.edit', 'description' => 'Edit points (credit/debit)'],
                ['name' => 'refund.index', 'description' => 'View refund requests'],
                ['name' => 'refund.show', 'description' => 'View refund details'],
                ['name' => 'refund.edit', 'description' => 'Process refunds'],
                ['name' => 'return.index', 'description' => 'View return requests'],
                ['name' => 'return.show', 'description' => 'View return details'],
                ['name' => 'return.edit', 'description' => 'Process returns'],
            ],

            'Membership Cards' => [
                ['name' => 'membership_card.view', 'description' => 'View membership card scanner page'],
                ['name' => 'membership_card.scan', 'description' => 'Scan membership cards'],
                ['name' => 'membership_card.assign', 'description' => 'Assign cards to users'],
                ['name' => 'membership_card.manage_points', 'description' => 'Add/manage user points via card scanner'],
                ['name' => 'membership_card.manage_wallet', 'description' => 'Add/manage user wallet via card scanner'],
            ],

            'System Tickets' => [
                ['name' => 'system-ticket.index', 'description' => 'View all system tickets'],
                ['name' => 'system-ticket.create', 'description' => 'Create new system ticket'],
                ['name' => 'system-ticket.show', 'description' => 'View ticket details'],
                ['name' => 'system-ticket.edit', 'description' => 'Update ticket status and add comments'],
                ['name' => 'system-ticket.delete', 'description' => 'Delete system ticket'],
            ],

            'Cash Book' => [
                ['name' => 'cashbook.index', 'description' => 'View cash book page'],
                ['name' => 'cashbook.show', 'description' => 'View cash book entries and statistics'],
                ['name' => 'cashbook.create', 'description' => 'Create cash book entries and import CSV'],
                ['name' => 'cashbook.edit', 'description' => 'Edit cash book entries'],
                ['name' => 'cashbook.delete', 'description' => 'Delete cash book entries'],
                ['name' => 'cashbook.view_stats_card', 'description' => 'View stats cards on cash book page'],
            ],

            'Invoices & Quotations' => [
                ['name' => 'invoice-quotation.index', 'description' => 'View all invoices & quotations'],
                ['name' => 'invoice-quotation.create', 'description' => 'Create new invoice/quotation'],
                ['name' => 'invoice-quotation.show', 'description' => 'View invoice/quotation details'],
                ['name' => 'invoice-quotation.edit', 'description' => 'Edit invoice/quotation'],
                ['name' => 'invoice-quotation.delete', 'description' => 'Delete invoice/quotation'],
                ['name' => 'invoice-quotation.download-pdf', 'description' => 'Download invoice/quotation PDF'],
                ['name' => 'invoice-quotation.send-email', 'description' => 'Send invoice/quotation via email'],
                ['name' => 'invoice-quotation.stats', 'description' => 'View invoice/quotation advanced statistics'],
            ],

            'Vendor Management' => [
                ['name' => 'vendor.index', 'description' => 'View vendor applications'],
                ['name' => 'vendor.approve', 'description' => 'Approve vendor applications'],
                ['name' => 'vendor.commissions', 'description' => 'View vendor commissions'],
                ['name' => 'vendor.withdrawals', 'description' => 'Process vendor withdrawals'],
            ],

            'Media' => [
                ['name' => 'media.index', 'description' => 'View media library'],
                ['name' => 'media.upload', 'description' => 'Upload media'],
                ['name' => 'media.delete', 'description' => 'Delete media'],
            ],

            'Processing Overdue' => [
                ['name' => 'processing-overdue.index', 'description' => 'View processing overdue orders'],
            ],

            'ETA Overdue' => [
                ['name' => 'eta-overdue.index', 'description' => 'View eta overdue orders'],
            ],

            'Back Orders' => [
                ['name' => 'back-order.index', 'description' => 'View back orders page'],
                ['name' => 'back-order.upload', 'description' => 'Upload back order files'],
                ['name' => 'back-order.process', 'description' => 'Process back order files'],
                ['name' => 'back-order.delete', 'description' => 'Delete uploaded files'],
            ],

            'gateway-transactions' => [
                ['name' => 'gateway-transactions.index', 'description' => 'View transactions'],
            ],

            'Q&A' => [
                ['name' => 'question_and_answer.index',   'description' => 'View Q&A management page'],
                ['name' => 'question_and_answer.answer',  'description' => 'Answer customer questions'],
                ['name' => 'question_and_answer.destroy', 'description' => 'Delete questions'],
            ],

            'Auctions' => [
                ['name' => 'auction.index',   'description' => 'View all auctions'],
                ['name' => 'auction.create',  'description' => 'Create new auction'],
                ['name' => 'auction.edit',    'description' => 'Edit auction'],
                ['name' => 'auction.destroy', 'description' => 'Delete auction'],
                ['name' => 'auction.end',     'description' => 'Manually end an auction and set winner'],
            ],

        ];
    }

    /**
     * Assign all permissions to admin role
     */
    protected function assignToAdminRole()
    {
        $this->info('🔐 Assigning permissions to admin role...');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        $this->info("✓ Admin role now has {$allPermissions->count()} permissions");
    }
}

