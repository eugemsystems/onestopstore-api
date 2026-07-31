@php
    // Fetch pending counts for admin notifications
    $pendingApplicationsCount = 0;
    $pendingWithdrawalsCount = 0;
    $pendingLaybyCount = 0;
    $pendingVendorProductsCount = 0;
    $pendingRefundsCount = 0;
    $pendingReturnsCount = 0;
    $processingOverdueItemsCount = 0;
    $unansweredQnaCount = 0;

    if (auth()->check()) {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            // Count pending vendor applications (not approved)
            $pendingApplicationsCount = \App\Models\Store::where('is_approved', 0)
                ->whereNull('deleted_at')
                ->count();

            // Count pending withdrawal requests
            $pendingWithdrawalsCount = \App\Models\WithdrawRequest::where('status', 'pending')
                ->whereNull('deleted_at')
                ->count();

            // Count pending layby applications
            $pendingLaybyCount = \App\Models\LaybyApplication::where('status', 'pending')
                ->whereNull('deleted_at')
                ->count();

            // Count pending vendor products (status=0 AND is_approved=0 AND has store_id)
            $pendingVendorProductsCount = \App\Models\Product::where('status', 0)
                ->where('is_approved', 0)
                ->whereNotNull('store_id')
                ->whereNull('deleted_at')
                ->count();

            // Count pending refunds
            $pendingRefundsCount = \App\Models\Refund::where('status', 'pending')
                ->whereNull('deleted_at')
                ->count();

            // Count pending returns (no soft deletes on this table)
            $pendingReturnsCount = \App\Models\ReturnRequest::where('status', 'pending')
                ->count();

            // Count unanswered Q&A questions
            $unansweredQnaCount = \App\Models\QuestionAndAnswer::whereNull('answer')
                ->whereNull('deleted_at')
                ->count();

            // Count processing overdue items (order products in processing status for more than 3 days)
            $threeDaysAgo = \Carbon\Carbon::now()->subDays(3);
            $processingOverdueItemsCount = \DB::table('order_products')
                ->where('item_status', 'processing')
                ->where('updated_at', '<=', $threeDaysAgo)
                ->whereNull('deleted_at')
                ->count();
        } elseif ($user->hasRole('Staff Raines')) {
            // Count processing overdue items for Staff Raines filtered by branch
            $threeDaysAgo = \Carbon\Carbon::now()->subDays(3);
            $processingOverdueItemsCount = \DB::table('order_products')
                ->where('order_products.item_status', 'processing')
                ->where('order_products.updated_at', '<=', $threeDaysAgo)
                ->whereNull('order_products.deleted_at')
                ->count();
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - Raines Africa')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #7f0000;
            --secondary-color: #d32f2f;
            --sidebar-bg: linear-gradient(180deg, #b71c1c 0%, #4a0000 100%);
            --sidebar-hover: rgba(239, 83, 80, 0.15);
            --sidebar-active: linear-gradient(135deg, #ef5350 0%, #b71c1c 100%);

            /* Bootstrap 5.3 (loaded from CDN) exposes its palette as CSS
               variables — overriding these here recolors .btn-primary,
               links, badges, etc. across every admin view without having
               to touch each blade file individually. */
            --bs-primary: #d32f2f;
            --bs-primary-rgb: 211, 47, 47;
            --bs-link-color: #d32f2f;
            --bs-link-color-rgb: 211, 47, 47;
            --bs-link-hover-color: #b71c1c;
            --bs-link-hover-color-rgb: 183, 28, 28;

            /* Responsive font sizes */
            --base-font-size: 15px;
            --small-font-size: 14px;
            --heading-font-size: 17px;
            --table-font-size: 15px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Responsive font sizing based on screen resolution */
        @media (min-width: 1920px) {
            :root {
                --base-font-size: 16px;
                --small-font-size: 14px;
                --heading-font-size: 20px;
                --table-font-size: 15px;
            }
        }

        @media (min-width: 2560px) {
            :root {
                --base-font-size: 18px;
                --small-font-size: 16px;
                --heading-font-size: 24px;
                --table-font-size: 17px;
            }
        }

        @media (max-width: 1366px) {
            :root {
                --base-font-size: 14px;
                --small-font-size: 12px;
                --heading-font-size: 16px;
                --table-font-size: 13px;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
            font-size: var(--base-font-size);
            line-height: 1.4;
        }

        /* Navbar hidden — layout now sidebar-only */
        .navbar { display: none !important; }

        .nav-link {
            font-size: var(--base-font-size);
            padding: 0.4rem 0.8rem !important;
        }

        /* ═══════════════════════════════════════
           ICON RAIL SIDEBAR
        ════════════════════════════════════════ */
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #141928 0%, #0d1117 60%, #0a0d14 100%);
            padding: 0;
            border-right: 1px solid rgba(255,255,255,.06);
            box-shadow: 2px 0 12px rgba(0,0,0,.35);
            position: fixed;
            top: 0;
            left: 0;
            width: 76px;
            overflow: visible;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #ef5350 0%, #b71c1c 50%, #ff8a80 100%);
            z-index: 1;
        }

        /* Rail sections */
        .rail-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 6px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            min-height: 56px;
            flex-shrink: 0;
        }
        .rail-logo img { max-height: 36px; width: auto; max-width: 56px; }

        .rail-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: visible;
            padding: 4px 0;
            scrollbar-width: none;
        }
        .rail-nav::-webkit-scrollbar { display: none; }

        .rail-footer {
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 4px 0;
            flex-shrink: 0;
        }

        .rail-divider {
            height: 1px;
            background: rgba(255,255,255,.06);
            margin: 4px 12px;
        }

        /* Rail item */
        .rail-item { position: relative; }

        .rail-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 9px 4px 8px;
            min-height: 54px;
            color: rgba(255,255,255,.5);
            text-decoration: none !important;
            cursor: pointer;
            border-left: 2px solid transparent;
            transition: background .15s, color .15s, border-left-color .15s;
            user-select: none;
        }
        .rail-link:hover,
        .rail-item.has-flyout:hover > .rail-link {
            color: #ffcdd2;
            background: rgba(239,83,80,.12);
            border-left-color: #ef5350;
            text-decoration: none !important;
        }
        .rail-item.rail-active > .rail-link,
        .rail-item.rail-active > a.rail-link {
            background: linear-gradient(135deg, rgba(239,83,80,.25) 0%, rgba(183,28,28,.25) 100%);
            color: #ffcdd2;
            border-left-color: #fbbf24;
        }
        .rail-link i { font-size: 1.05rem; line-height: 1; }
        .rail-label { font-size: 9px; font-weight: 500; text-align: center; line-height: 1.2; white-space: nowrap; }

        /* Dot badge on rail icon for categories with pending items */
        .rail-badge {
            position: absolute;
            top: 8px; right: 11px;
            width: 7px; height: 7px;
            background: #ff416c;
            border-radius: 50%;
            border: 1px solid #0d1117;
        }

        /* Rail user button */
        .rail-user-btn {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef5350, #b71c1c);
            border: none;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .73rem; color: #fff;
            cursor: pointer; transition: transform .15s;
            margin: 0 auto;
        }
        .rail-user-btn:hover { transform: scale(1.08); }

        /* Flyout panel */
        .rail-flyout {
            position: fixed;
            left: 76px;
            top: 0;
            width: 240px;
            background: #0f1629;
            border: 1px solid rgba(255,255,255,.08);
            border-left: none;
            border-radius: 0 12px 12px 0;
            box-shadow: 8px 4px 24px rgba(0,0,0,.45);
            z-index: 99999;
            display: none;
            overflow: hidden;
            max-height: calc(100vh - 16px);
            overflow-y: auto;
            scrollbar-width: none;
        }
        .rail-flyout::-webkit-scrollbar { display: none; }

        .flyout-title {
            padding: 10px 14px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #ef5350;
            background: rgba(239,83,80,.08);
            border-bottom: 1px solid rgba(255,255,255,.06);
            position: sticky;
            top: 0;
        }

        .flyout-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 14px;
            color: #90a4ae;
            text-decoration: none !important;
            font-size: 12.5px;
            font-weight: 500;
            border-left: 2px solid transparent;
            transition: background .12s, color .12s, border-left-color .12s;
            white-space: nowrap;
        }
        .flyout-link:hover {
            background: rgba(239,83,80,.12);
            color: #ffcdd2;
            border-left-color: #ef5350;
            text-decoration: none !important;
        }
        .flyout-link.flyout-active {
            background: linear-gradient(135deg, rgba(239,83,80,.2) 0%, rgba(183,28,28,.2) 100%);
            color: #ffcdd2;
            border-left-color: #fbbf24;
            font-weight: 600;
        }
        .flyout-link i { font-size: .82rem; width: 14px; text-align: center; flex-shrink: 0; }
        .flyout-link .notification-badge { margin-left: auto; flex-shrink: 0; }

        /* Notification Badges */
        .notification-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 16px; height: 16px;
            padding: 1px 4px;
            font-size: 9px; font-weight: 700; line-height: 1;
            color: #fff;
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border-radius: 8px;
            margin-left: auto;
            box-shadow: 0 2px 6px rgba(255,65,108,.3);
        }

        /* Content pushed right for narrower rail */
        .content-wrapper {
            padding: 1rem;
            background-color: #f8f9fa;
            min-height: 100vh;
            margin-left: 76px;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s ease; }
            .sidebar.mobile-open { transform: none; }
            .content-wrapper { margin-left: 0; }
        }

        /* Buttons - Compact */
        .btn {
            padding: 0.4rem 1rem;
            font-size: var(--base-font-size);
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: var(--small-font-size);
        }

        .btn-lg {
            padding: 0.6rem 1.5rem;
            font-size: var(--base-font-size);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            box-shadow: 0 2px 8px rgba(6, 42, 106, 0.25);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(6, 42, 106, 0.35);
        }

        /* ═══════════════════════════════════════
           GLOBAL CARD — Premium Design System
        ════════════════════════════════════════ */
        .card {
            border: 1px solid #f0f4f8;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            margin-bottom: 1.25rem;
            border-radius: 16px;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .card:hover {
            box-shadow: 0 8px 28px rgba(0,0,0,.1);
            transform: translateY(-2px);
        }

        /* Card header: same dark gradient as table header */
        .card-header {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 100%);
            color: #fff;
            border-radius: 0 !important;
            padding: 13px 18px;
            font-size: var(--base-font-size);
            font-weight: 700;
            letter-spacing: .2px;
            border-bottom: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header .card-title {
            margin: 0;
            color: #fff;
            font-size: var(--base-font-size);
            font-weight: 700;
        }

        .card-body {
            padding: 1rem 1.1rem;
        }

        /* Page-level stat mini-cards — matches auctions stat row */
        .admin-stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            border: 1px solid #f0f4f8;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform .2s, box-shadow .2s;
        }
        .admin-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .admin-stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .admin-stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .admin-stat-label { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

        /* Page header — same hero treatment */
        .orders-page-header, .admin-page-header {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .orders-page-header::before, .admin-page-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168,85,247,.3) 0%, transparent 70%);
            pointer-events: none;
        }
        .orders-icon-wrap, .admin-page-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0,0,0,.25);
        }

        /* Headings - Compact */
        h1, h2, h3, h4, h5, h6 {
            margin-bottom: 0.75rem;
            font-weight: 600;
            line-height: 1.3;
        }

        h1 { font-size: calc(var(--heading-font-size) + 6px); }
        h2 { font-size: calc(var(--heading-font-size) + 2px); }
        h3 { font-size: var(--heading-font-size); }
        h4 { font-size: var(--base-font-size); }
        h5 { font-size: var(--base-font-size); }
        h6 { font-size: var(--small-font-size); }

        /* ═══════════════════════════════════════
           GLOBAL TABLE — Premium Design System
           Matches /admin/auctions table aesthetic
        ════════════════════════════════════════ */
        .table {
            font-size: var(--table-font-size);
            margin-bottom: 0;
            line-height: 1.35;
        }

        /* Wrap every table in a rounded card shell */
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            border: 1px solid #f0f4f8;
        }

        /* Dark gradient header — same as auctions */
        .table thead th {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 100%);
            color: #94a3b8;
            font-weight: 600;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .7px;
            padding: 13px 16px;
            border: none;
            white-space: nowrap;
            vertical-align: middle;
        }

        /* First column label is white for emphasis */
        .table thead th:first-child { color: #fff; }

        .table tbody td {
            padding: 13px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }

        /* Zebra striping */
        .table tbody tr:nth-child(odd)  { background: #ffffff; }
        .table tbody tr:nth-child(even) { background: #f8f6ff; }

        /* Hover — soft purple wash */
        .table tbody tr:hover td {
            background: #ede9fe !important;
            transition: background .15s;
        }

        .table tbody tr:last-child td { border-bottom: none; }

        /* Table image optimization */
        .table img {
            max-width: 44px;
            max-height: 44px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        /* Compact media breakpoint */
        @media (max-width: 1600px) {
            .table thead th { padding: 10px 12px; font-size: .68rem; }
            .table tbody td { padding: 10px 12px; }
        }

        /* Badge styling */
        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Status Badges - Compact */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
            white-space: nowrap;
        }

        .status-pending { background-color: #ffc107; color: #000; }
        .status-processing { background-color: #17a2b8; color: #fff; }
        .status-shipped { background-color: #007bff; color: #fff; }
        .status-delivered { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .status-completed { background-color: #28a745; color: #fff; }

        /* Form Controls - Compact */
        .form-control, .form-select {
            font-size: var(--base-font-size);
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.15rem rgba(6, 42, 106, 0.15);
        }

        .form-label {
            font-size: var(--base-font-size);
            font-weight: 500;
            margin-bottom: 0.3rem;
            color: #495057;
        }

        .form-check-label {
            font-size: var(--base-font-size);
        }

        /* Input group styling */
        .input-group-text {
            font-size: var(--base-font-size);
            padding: 0.4rem 0.75rem;
        }

        /* Pagination - Compact */
        .pagination {
            margin-bottom: 0.5rem;
        }

        .page-link {
            font-size: var(--small-font-size);
            padding: 0.3rem 0.6rem;
        }

        /* Alerts - Compact */
        .alert {
            padding: 0.6rem 1rem;
            font-size: var(--base-font-size);
            border-radius: 6px;
            margin-bottom: 0.75rem;
        }

        /* Modal styling */
        .modal-header {
            padding: 0.75rem 1rem;
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-footer {
            padding: 0.75rem 1rem;
        }

        .modal-title {
            font-size: var(--heading-font-size);
        }

        /* Dropdown menu */
        .dropdown-menu {
            font-size: var(--base-font-size);
            padding: 0.25rem 0;
        }

        .dropdown-item {
            padding: 0.4rem 1rem;
            font-size: var(--base-font-size);
        }

        /* Button group */
        .btn-group .btn {
            padding: 0.3rem 0.6rem;
        }

        .btn-group-sm .btn {
            padding: 0.2rem 0.4rem;
            font-size: var(--small-font-size);
        }

        /* List group */
        .list-group-item {
            padding: 0.5rem 0.75rem;
            font-size: var(--base-font-size);
        }

        /* Breadcrumb */
        .breadcrumb {
            font-size: var(--small-font-size);
            padding: 0.5rem 1rem;
            margin-bottom: 0.75rem;
        }

        /* Nav tabs */
        .nav-tabs .nav-link {
            font-size: var(--base-font-size);
            padding: 0.4rem 1rem;
        }

        /* Utility spacing overrides - More compact */
        .mb-3 { margin-bottom: 0.75rem !important; }
        .mb-4 { margin-bottom: 1rem !important; }
        .mt-3 { margin-top: 0.75rem !important; }
        .mt-4 { margin-top: 1rem !important; }
        .p-3 { padding: 0.75rem !important; }
        .p-4 { padding: 1rem !important; }
        .py-3 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
        .px-3 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }

        /* Text utilities */
        .text-muted {
            color: #6c757d !important;
            font-size: var(--small-font-size);
        }

        .small, small {
            font-size: var(--small-font-size);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: none;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner-border {
            width: 2rem;
            height: 2rem;
            border-width: 0.25rem;
        }

        /* Tablet specific - icon only sidebar */
        @media (min-width: 768px) and (max-width: 1024px) {
            .sidebar {
                width: 60px;
                overflow: visible;
            }

            .sidebar .nav-link span,
            .sidebar .nav-dropdown .dropdown-icon,
            .sidebar .nav-section {
                display: none;
            }

            .sidebar .nav-link {
                padding: 10px 0;
                text-align: center;
                position: relative;
                margin: 1px 5px;
            }

            .sidebar .nav-link i {
                margin-right: 0;
                font-size: calc(var(--base-font-size) + 4px);
            }

            .sidebar .nav-submenu .nav-link {
                padding-left: 0;
            }

            /* Tooltip for icon-only sidebar */
            .sidebar .nav-link:hover::after {
                content: attr(data-title);
                position: absolute;
                left: 60px;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(0,0,0,0.9);
                color: white;
                padding: 6px 10px;
                border-radius: 4px;
                white-space: nowrap;
                z-index: 1000;
                font-size: var(--small-font-size);
            }

            .content-wrapper {
                margin-left: 60px !important;
                padding: 0.75rem;
            }

            .notification-badge {
                position: absolute;
                top: 2px;
                right: 5px;
            }
        }

        /* Laptop specific (1366px-1600px) - More compact */
        @media (min-width: 1025px) and (max-width: 1600px) {
            .content-wrapper {
                padding: 0.75rem;
            }

            .sidebar .nav-link {
                padding: 5px 12px;
                margin: 1px 8px;
            }

            .card {
                margin-bottom: 0.75rem;
            }
        }

        /* Mobile sidebar — starts from top now */
        @media (max-width: 767px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                z-index: 1000;
                transition: left 0.3s ease;
                width: 260px;
                max-height: 100vh;
                overflow-y: auto;
            }

            .sidebar.show {
                left: 0;
                box-shadow: 4px 0 12px rgba(0,0,0,0.2);
            }

            .content-wrapper {
                margin-left: 0 !important;
                padding: 0.5rem;
            }

            .table {
                font-size: 10px;
            }

            .table thead th {
                padding: 0.25rem 0.3rem;
                font-size: 9px;
            }

            .table tbody td {
                padding: 0.25rem 0.3rem;
            }

            h1, h2 {
                font-size: var(--heading-font-size);
            }

            .btn {
                padding: 0.3rem 0.6rem;
                font-size: var(--small-font-size);
            }

            .card-header {
                padding: 0.4rem 0.75rem;
            }

            .card-body {
                padding: 0.5rem 0.75rem;
            }
        }

        /* High resolution displays (2K, 4K) */
        @media (min-width: 2560px) {
            .content-wrapper {
                max-width: 2400px;
                margin-left: auto;
                margin-right: auto;
            }
        }

        /* Print styles */
        @media print {
            .sidebar, .navbar, .btn, .loading-overlay {
                display: none !important;
            }

            .content-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                page-break-inside: avoid;
            }

            .table {
                font-size: 10pt;
            }
        }
        .badge {
            padding: 0.25em 0.5em !important;
            font-size: 0.6rem !important;
        }
        .btn-sm {
            padding: 0.15rem 0.4rem !important;
            font-size: var(--small-font-size) !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>


    <!-- Sidebar now contains the header/brand/user area -->

    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <nav class="sidebar col-md-2 d-md-block">

                    {{-- Logo --}}
                    <div class="rail-logo">
                        <a href="{{ route('admin.dashboard') }}">
                            <img src="https://media.onestopstore.co.zw/storage/uploads/2026/07/31/18/57/8e1a3572-d6fc-477e-ac87-0deeb4f54043.png"
                                 style="max-height:36px;width:auto;max-width:56px;" alt="Raines">
                        </a>
                    </div>

                    {{-- User profile flyout --}}
                    <div class="rail-item has-flyout" style="border-bottom:1px solid rgba(255,255,255,.06);padding:8px 0;">
                        <div class="rail-link" style="min-height:44px;">
                            <button class="rail-user-btn" title="{{ auth()->user()->name ?? 'Admin' }}">
                                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </button>
                        </div>
                        <div class="rail-flyout">
                            <div class="flyout-title">{{ auth()->user()->name ?? 'Admin' }}</div>
                            <a class="flyout-link" href="{{ route('admin.profile') }}">
                                <i class="bi bi-person-fill"></i> Profile
                            </a>
                            <div style="height:1px;background:rgba(255,255,255,.06);margin:4px 0;"></div>
                            <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="flyout-link" style="width:100%;border:none;background:none;color:#f87171;text-align:left;">
                                    <i class="bi bi-box-arrow-right" style="color:#f87171;"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="rail-nav">

                        {{-- Dashboard --}}
                        @can('dashboard.view')
                        <div class="rail-item {{ request()->routeIs('admin.dashboard') ? 'rail-active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}" class="rail-link">
                                <i class="bi bi-speedometer2"></i>
                                <span class="rail-label">Dash</span>
                            </a>
                        </div>
                        @endcan

                        @if(!auth()->user()->hasAnyRole(['vendor','consumer']))

                        {{-- Orders --}}
                        @if(auth()->user()->can('order.index'))
                        @php
                            $excludedSlugs = ['pending','cancelled','collected','ready-for-delivery','ready_for_delivery','delivered'];
                            $excludedStatusIds = \App\Models\OrderStatus::whereIn('slug',$excludedSlugs)->pluck('id')->toArray();
                            $getItTomorrowCount = \App\Models\Order::whereNull('parent_id')->whereNotIn('order_status_id',$excludedStatusIds)->whereHas('products',fn($q)=>$q->where('products.estimated_delivery_text','Get It Tomorrow')->where('order_products.item_status','processing'))->count();
                            $sameDayCount = \App\Models\Order::whereNull('parent_id')->whereNotIn('order_status_id',$excludedStatusIds)->whereHas('products',fn($q)=>$q->where('products.estimated_delivery_text','LIKE','Same Day%')->where('order_products.item_status','processing'))->count();
                            $corporateCount = \App\Models\Order::whereNull('parent_id')->whereNotIn('order_status_id',$excludedStatusIds)->where('order_status_id',\App\Models\OrderStatus::where('slug','processing')->value('id'))->whereHas('consumer',fn($q)=>$q->whereNotNull('company_name')->where('company_name','!=',''))->count();
                            $lateOrderCount = \Cache::remember('admin_late_orders_count',300,function(){return \DB::table('orders as o')->join('order_status as os','o.order_status_id','=','os.id')->whereIn('o.payment_status',['Success','COMPLETED','COMPLETE','CASH_ON_DELIVERY','Credit'])->whereNotIn('os.slug',['cancelled','delivered','collected','ready-for-collection','ready_for_collection','ready-for-delivery','ready_for_delivery'])->whereExists(fn($sub)=>$sub->from('order_products as op')->whereColumn('op.order_id','o.id')->whereNotNull('op.eta')->whereRaw("op.eta::date < CURRENT_DATE")->whereNotIn('op.item_status',['cancelled','out of stock','out_of_stock','delivered','collected','ready for collection'])->whereNull('op.deleted_at'))->count();});
                        @endphp
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.orders.*','admin.order-reminders.*','admin.late-orders.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-bag"></i>
                                <span class="rail-label">Orders</span>
                                @if($getItTomorrowCount + $sameDayCount + $lateOrderCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Orders</div>
                                @can('order.index')
                                <a class="flyout-link {{ request()->routeIs('admin.orders.index') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.index') }}"><i class="bi bi-cart-check"></i> All Orders</a>
                                <a class="flyout-link {{ request()->routeIs('admin.orders.get-it-tomorrow') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.get-it-tomorrow') }}"><i class="bi bi-lightning-fill text-warning"></i> Get It Tomorrow @if($getItTomorrowCount > 0)<span class="notification-badge">{{ $getItTomorrowCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.orders.same-day-delivery') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.same-day-delivery') }}"><i class="bi bi-rocket-fill text-danger"></i> Same Day Delivery @if($sameDayCount > 0)<span class="notification-badge">{{ $sameDayCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.orders.corporate') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.corporate') }}"><i class="bi bi-building-fill"></i> Corporate Orders @if($corporateCount > 0)<span class="notification-badge">{{ $corporateCount }}</span>@endif</a>
                                @endcan
                                @can('order.create')<a class="flyout-link {{ request()->routeIs('admin.orders.create') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.create') }}"><i class="bi bi-plus-circle-fill"></i> Create Order</a>@endcan
                                @can('order.edit')<a class="flyout-link {{ request()->routeIs('admin.orders.qr-scanner') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.qr-scanner') }}"><i class="bi bi-upc-scan"></i> QR Scanner</a>@endcan
                                @can('order-stats.view')<a class="flyout-link {{ request()->routeIs('admin.orders.stats.*') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.stats.index') }}"><i class="bi bi-graph-up-arrow"></i> Statistics</a>@endcan
                                @can('order-reminder.index')<a class="flyout-link {{ request()->routeIs('admin.order-reminders.*') ? 'flyout-active' : '' }}" href="{{ route('admin.order-reminders.index') }}"><i class="bi bi-envelope-exclamation"></i> Reminders</a>@endcan
                                @can('order-item.search')<a class="flyout-link {{ request()->routeIs('admin.orders.item-search.*') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.item-search.index') }}"><i class="bi bi-search"></i> Search Items</a>@endcan
                                @can('processing-link-builder.index')<a class="flyout-link {{ request()->routeIs('admin.orders.processing-link-builder') ? 'flyout-active' : '' }}" href="{{ route('admin.orders.processing-link-builder') }}"><i class="bi bi-link-45deg"></i> Takealot Link Builder</a>@endcan
                                <a class="flyout-link {{ request()->routeIs('admin.late-orders.*') ? 'flyout-active' : '' }}" href="{{ route('admin.late-orders.index') }}"><i class="bi bi-exclamation-triangle-fill text-danger"></i> Late Orders @if($lateOrderCount > 0)<span class="notification-badge">{{ $lateOrderCount }}</span>@endif</a>
                            </div>
                        </div>
                        @endif

                        {{-- Auctions --}}
                        @if(auth()->user()->hasRole('admin') || auth()->user()->can('order.index'))
                        @php
                            $activeAuctions = \App\Models\AuctionItem::where('status','active')->where('ends_at','>',now())->count();
                            $pendingAuctionRefunds = \App\Models\AuctionDepositRefund::where('status','pending')->count();
                            $pendingFulfillments = \App\Models\AuctionItem::whereNotNull('winner_id')->whereNotNull('order_id')->where('fulfillment_status','pending')->whereHas('order',fn($q)=>$q->whereIn(\Illuminate\Support\Facades\DB::raw('lower(payment_status)'),['paid','complete','completed','success','approved','captured']))->count();
                            $unpaidAuctionCount = \App\Models\AuctionItem::where('status','ended')->whereNotNull('winner_id')->where(function($q){$q->whereNull('fulfillment_status')->orWhereNotIn('fulfillment_status',['confirmed','ready_for_collection','out_for_delivery','collected','delivered']);})->where(function($q){$q->whereNull('order_id')->orWhereHas('order',fn($oq)=>$oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)->whereRaw("LOWER(payment_status) NOT IN (?,?,?,?,?,?)",['paid','complete','completed','success','approved','captured']));})->count();
                            $activeBansCount = \App\Models\AuctionBan::whereNull('lifted_at')->count();
                            $approvedBiddersCount = \App\Models\User::where('auction_approved',true)->count();
                        @endphp
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.auctions.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-hammer"></i>
                                <span class="rail-label">Auctions</span>
                                @if($activeAuctions > 0 || $pendingAuctionRefunds > 0 || $unpaidAuctionCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Auctions</div>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.index') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.index') }}"><i class="bi bi-list-ul"></i> All Auctions @if($activeAuctions > 0)<span class="notification-badge" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">{{ $activeAuctions }} live</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.deposit-refunds') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.deposit-refunds') }}"><i class="bi bi-arrow-counterclockwise"></i> Deposit Refunds @if($pendingAuctionRefunds > 0)<span class="notification-badge">{{ $pendingAuctionRefunds }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.won') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.won') }}"><i class="bi bi-trophy" style="color:#f59e0b"></i> Won Auctions</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.fulfilled') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.fulfilled') }}"><i class="bi bi-box-seam"></i> Fulfilled Items @if($pendingFulfillments > 0)<span class="notification-badge">{{ $pendingFulfillments }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.unpaid') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.unpaid') }}"><i class="bi bi-hourglass-split" style="color:#f59e0b"></i> Unpaid Auctions @if($unpaidAuctionCount > 0)<span class="notification-badge" style="background:#f59e0b">{{ $unpaidAuctionCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.bans') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.bans') }}"><i class="bi bi-slash-circle" style="color:#ef4444"></i> Bans @if($activeBansCount > 0)<span class="notification-badge" style="background:#ef4444">{{ $activeBansCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.deposits') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.deposits') }}"><i class="bi bi-cash-stack" style="color:#3b82f6"></i> Deposits</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.bidders') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.bidders') }}"><i class="bi bi-people-fill" style="color:#10b981"></i> Bidders @if($approvedBiddersCount > 0)<span class="notification-badge" style="background:#10b981">{{ $approvedBiddersCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.auctions.settings') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.settings') }}"><i class="bi bi-gear-fill" style="color:#94a3b8"></i> Settings</a>
                                @can('auction-analytics.view')<a class="flyout-link {{ request()->routeIs('admin.auctions.statistics','admin.auctions.auction-stats') ? 'flyout-active' : '' }}" href="{{ route('admin.auctions.statistics') }}"><i class="bi bi-bar-chart-line" style="color:#a78bfa"></i> Analytics</a>@endcan
                            </div>
                        </div>
                        @endif

                        {{-- Products --}}
                        @if(auth()->user()->can('product.index'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.products.*','admin.product-feed.*','admin.import-fast*','admin.categories.*','admin.attributes.*','admin.bulk-promotion.*','admin.back-orders.*','admin.promo-templates.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-box-seam"></i>
                                <span class="rail-label">Products</span>
                                @if($pendingVendorProductsCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Products</div>
                                @can('product.index')
                                <a class="flyout-link {{ request()->routeIs('admin.products.index') && !request()->routeIs('admin.products.vendor-products') ? 'flyout-active' : '' }}" href="{{ route('admin.products.index') }}"><i class="bi bi-grid-3x3"></i> All Products</a>
                                <a class="flyout-link {{ request()->routeIs('admin.products.statistics') ? 'flyout-active' : '' }}" href="{{ route('admin.products.statistics') }}"><i class="bi bi-graph-up-arrow" style="color:#7c3aed"></i> Statistics</a>
                                <a class="flyout-link {{ request()->routeIs('admin.products.search-export') ? 'flyout-active' : '' }}" href="{{ route('admin.products.search-export') }}"><i class="bi bi-file-earmark-excel" style="color:#15803d"></i> Search & Export</a>
                                @endcan
                                @can('product.bulk-disable')<a class="flyout-link {{ request()->routeIs('admin.products.bulk-disable*') ? 'flyout-active' : '' }}" href="{{ route('admin.products.bulk-disable') }}"><i class="bi bi-slash-circle" style="color:#dc2626"></i> Bulk Disable</a>@endcan
                                @can('product.index')<a class="flyout-link {{ request()->routeIs('admin.products.vendor-products') ? 'flyout-active' : '' }}" href="{{ route('admin.products.vendor-products') }}"><i class="bi bi-shop"></i> Vendor Products @if($pendingVendorProductsCount > 0)<span class="notification-badge">{{ $pendingVendorProductsCount }}</span>@endif</a>@endcan
                                @can('product.create')<a class="flyout-link {{ request()->routeIs('admin.products.create') ? 'flyout-active' : '' }}" href="{{ route('admin.products.create') }}"><i class="bi bi-plus-square-fill"></i> Add Product</a>@endcan
                                @can('product-feed.index')<a class="flyout-link {{ request()->routeIs('admin.product-feed.index') ? 'flyout-active' : '' }}" href="{{ route('admin.product-feed.index') }}"><i class="bi bi-rss-fill"></i> Export Feed</a>@endcan
                                @can('import.index')
                                <a class="flyout-link {{ request()->routeIs('admin.import-fast.index') ? 'flyout-active' : '' }}" href="{{ route('admin.import-fast.index') }}"><i class="bi bi-lightning-charge-fill"></i> Fast Import</a>
                                <a class="flyout-link {{ request()->routeIs('admin.import-fast-update.index') ? 'flyout-active' : '' }}" href="{{ route('admin.import-fast-update.index') }}"><i class="bi bi-arrow-repeat"></i> Fast Import [Update]</a>
                                @endcan
                                @can('back_order.index')<a class="flyout-link {{ request()->routeIs('admin.back-orders.index') ? 'flyout-active' : '' }}" href="{{ route('admin.back-orders.index') }}"><i class="bi bi-box-seam"></i> Back Order Mgmt</a>@endcan
                                @can('category.index')<a class="flyout-link {{ request()->routeIs('admin.categories.*') ? 'flyout-active' : '' }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags-fill"></i> Categories</a>@endcan
                                @can('attribute.index')<a class="flyout-link {{ request()->routeIs('admin.attributes.*') ? 'flyout-active' : '' }}" href="{{ route('admin.attributes.index') }}"><i class="bi bi-sliders"></i> Attributes</a>@endcan
                                <a class="flyout-link {{ request()->routeIs('admin.promo-templates.*') ? 'flyout-active' : '' }}" href="{{ route('admin.promo-templates.index') }}"><i class="bi bi-file-earmark-image"></i> Promo Templates</a>
                                @can('product.edit')<a class="flyout-link {{ request()->routeIs('admin.bulk-promotion.*') ? 'flyout-active' : '' }}" href="{{ route('admin.bulk-promotion.index') }}"><i class="bi bi-tags"></i> Bulk Promotion</a>@endcan
                            </div>
                        </div>
                        @endif

                        {{-- Content --}}
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.blogs.*','admin.questions.*','admin.media.*','admin.invoices-quotations.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-journal-richtext"></i>
                                <span class="rail-label">Content</span>
                                @if(($unansweredQnaCount ?? 0) > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Content</div>
                                @can('invoice-quotation.index')<a class="flyout-link {{ request()->routeIs('admin.invoices-quotations.*') ? 'flyout-active' : '' }}" href="{{ route('admin.invoices-quotations.index') }}"><i class="bi bi-file-earmark-text"></i> Invoices & Quotations</a>@endcan
                                <a class="flyout-link {{ request()->routeIs('admin.blogs.*') ? 'flyout-active' : '' }}" href="{{ route('admin.blogs.index') }}"><i class="bi bi-journal-richtext"></i> Blogs</a>
                                @can('question_and_answer.index')<a class="flyout-link {{ request()->routeIs('admin.questions.*') ? 'flyout-active' : '' }}" href="{{ route('admin.questions.index') }}"><i class="bi bi-chat-square-quote-fill" style="color:#ef5350"></i> Q &amp; A @if(($unansweredQnaCount ?? 0) > 0)<span class="notification-badge">{{ $unansweredQnaCount }}</span>@endif</a>@endcan
                                @can('media.index')<a class="flyout-link {{ request()->routeIs('admin.media.*') ? 'flyout-active' : '' }}" href="{{ route('admin.media.index') }}"><i class="bi bi-folder2-open"></i> Media Library</a>@endcan
                            </div>
                        </div>

                        {{-- Customers --}}
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.layby.*','admin.membership-cards.*','admin.wallet.*','admin.points.*','admin.vouchers.*','admin.refunds.*','admin.returns.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-people-fill"></i>
                                <span class="rail-label">Customers</span>
                                @if($pendingLaybyCount + $pendingRefundsCount + $pendingReturnsCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Customer Management</div>
                                @can('layby.index')<a class="flyout-link {{ request()->routeIs('admin.layby.*') ? 'flyout-active' : '' }}" href="{{ route('admin.layby.index') }}"><i class="bi bi-calendar-check"></i> Layby Applications @if($pendingLaybyCount > 0)<span class="notification-badge">{{ $pendingLaybyCount }}</span>@endif</a>@endcan
                                @can('membership_card.view')<a class="flyout-link {{ request()->routeIs('admin.membership-cards.*') ? 'flyout-active' : '' }}" href="{{ route('admin.membership-cards.scanner') }}"><i class="bi bi-credit-card-2-front"></i> Membership Cards</a>@endcan
                                @can('wallet.index')
                                <a class="flyout-link {{ request()->routeIs('admin.wallet.index') && !request()->routeIs('admin.wallet.list') ? 'flyout-active' : '' }}" href="{{ route('admin.wallet.index') }}"><i class="bi bi-wallet2"></i> Wallet</a>
                                <a class="flyout-link {{ request()->routeIs('admin.wallet.list') ? 'flyout-active' : '' }}" href="{{ route('admin.wallet.list') }}"><i class="bi bi-list-ul"></i> All Wallets</a>
                                @endcan
                                @can('point.index')<a class="flyout-link {{ request()->routeIs('admin.points.*') ? 'flyout-active' : '' }}" href="{{ route('admin.points.index') }}"><i class="bi bi-coin"></i> Points</a>@endcan
                                @can('vouchers.index')<a class="flyout-link {{ request()->routeIs('admin.vouchers.*') ? 'flyout-active' : '' }}" href="{{ route('admin.vouchers.index') }}"><i class="bi bi-gift"></i> Gift Vouchers</a>@endcan
                                @can('refund.index')<a class="flyout-link {{ request()->routeIs('admin.refunds.*') ? 'flyout-active' : '' }}" href="{{ route('admin.refunds.index') }}"><i class="bi bi-arrow-counterclockwise"></i> Refunds @if($pendingRefundsCount > 0)<span class="notification-badge">{{ $pendingRefundsCount }}</span>@endif</a>@endcan
                                @can('return.index')<a class="flyout-link {{ request()->routeIs('admin.returns.*') ? 'flyout-active' : '' }}" href="{{ route('admin.returns.index') }}"><i class="bi bi-box-arrow-in-left"></i> Returns @if($pendingReturnsCount > 0)<span class="notification-badge">{{ $pendingReturnsCount }}</span>@endif</a>@endcan
                            </div>
                        </div>

                        {{-- Inventory --}}
                        @if(auth()->user()->hasRole('admin') || auth()->user()->can('inventory-shipment.index'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.inventory-shipments.*','admin.inventory-receiving.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-boxes"></i>
                                <span class="rail-label">Inventory</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Inventory</div>
                                @can('inventory-shipment.index')<a class="flyout-link {{ request()->routeIs('admin.inventory-shipments.*') ? 'flyout-active' : '' }}" href="{{ route('admin.inventory-shipments.index') }}"><i class="bi bi-truck"></i> Inventory Shipments</a>@endcan
                                @can('inventory-shipment.edit')<a class="flyout-link {{ request()->routeIs('admin.inventory-receiving.*') ? 'flyout-active' : '' }}" href="{{ route('admin.inventory-receiving.index') }}"><i class="bi bi-box-arrow-in-down"></i> Receive Inventory</a>@endcan
                            </div>
                        </div>
                        @endif

                        {{-- Vendor Management (admin) --}}
                        @if(auth()->user()->hasRole('admin'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.vendor-applications.*','admin.commissions.*','admin.withdrawals.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-shop"></i>
                                <span class="rail-label">Vendors</span>
                                @if($pendingApplicationsCount > 0 || $pendingWithdrawalsCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Vendor Management</div>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor-applications.*') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor-applications.index') }}"><i class="bi bi-person-badge-fill"></i> Applications @if($pendingApplicationsCount > 0)<span class="notification-badge">{{ $pendingApplicationsCount }}</span>@endif</a>
                                <a class="flyout-link {{ request()->routeIs('admin.commissions.*') ? 'flyout-active' : '' }}" href="{{ route('admin.commissions.index') }}"><i class="bi bi-cash-stack"></i> Commissions</a>
                                <a class="flyout-link {{ request()->routeIs('admin.withdrawals.*') ? 'flyout-active' : '' }}" href="{{ route('admin.withdrawals.index') }}"><i class="bi bi-wallet2"></i> Withdrawals @if($pendingWithdrawalsCount > 0)<span class="notification-badge">{{ $pendingWithdrawalsCount }}</span>@endif</a>
                            </div>
                        </div>
                        @endif

                        {{-- Analytics --}}
                        @if(auth()->check() && auth()->user()->hasRole('admin'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.analytics.*','admin.search-analytics.*','admin.ai-analytics.*','admin.marketing-feedback.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-bar-chart-line-fill"></i>
                                <span class="rail-label">Analytics</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Analytics</div>
                                @can('analytics.dashboard')<a class="flyout-link {{ request()->routeIs('admin.analytics.dashboard') ? 'flyout-active' : '' }}" href="{{ route('admin.analytics.dashboard') }}"><i class="bi bi-bar-chart-line-fill"></i> Analytics</a>@endcan
                                <a class="flyout-link {{ request()->routeIs('admin.search-analytics.*') ? 'flyout-active' : '' }}" href="{{ route('admin.search-analytics.index') }}"><i class="bi bi-search"></i> Search Analytics</a>
                                <a class="flyout-link {{ request()->routeIs('admin.ai-analytics.*') ? 'flyout-active' : '' }}" href="{{ route('admin.ai-analytics.index') }}"><i class="bi bi-stars"></i> AI Analytics</a>
                                @can('marketing-feedback.index')<a class="flyout-link {{ request()->routeIs('admin.marketing-feedback.*') ? 'flyout-active' : '' }}" href="{{ route('admin.marketing-feedback.index') }}"><i class="bi bi-chat-square-text"></i> Marketing Feedback</a>@endcan
                            </div>
                        </div>
                        @endif

                        {{-- Finance --}}
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.gateway-transactions.*','admin.cash-book.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-cash-stack"></i>
                                <span class="rail-label">Finance</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Finance</div>
                                @can('gateway-transactions.index')<a class="flyout-link {{ request()->routeIs('admin.gateway-transactions.*') ? 'flyout-active' : '' }}" href="{{ route('admin.gateway-transactions.index') }}"><i class="bi bi-credit-card-2-front-fill"></i> Gateway Transactions</a>@endcan
                                @can('cashbook.index')<a class="flyout-link {{ request()->routeIs('admin.cash-book.*') ? 'flyout-active' : '' }}" href="{{ route('admin.cash-book.index') }}"><i class="bi bi-cash-stack"></i> Cash Book</a>@endcan
                            </div>
                        </div>

                        {{-- Support --}}
                        @php
                            $openTicketsCount = \App\Models\Ticket::whereIn('status',['open','waiting_admin'])->count();
                            $openSystemTicketsCount = \App\Models\SystemTicket::whereIn('status',['open','in_progress','reopened'])->count();
                        @endphp
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.tickets.*','admin.system-tickets.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-ticket-perforated"></i>
                                <span class="rail-label">Support</span>
                                @if($openTicketsCount + $openSystemTicketsCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Support</div>
                                @can('ticket.index')<a class="flyout-link {{ request()->routeIs('admin.tickets.*') ? 'flyout-active' : '' }}" href="{{ route('admin.tickets.index') }}"><i class="bi bi-ticket-perforated"></i> Support Tickets @if($openTicketsCount > 0)<span class="notification-badge">{{ $openTicketsCount }}</span>@endif</a>@endcan
                                @can('system-ticket.index')<a class="flyout-link {{ request()->routeIs('admin.system-tickets.*') ? 'flyout-active' : '' }}" href="{{ route('admin.system-tickets.index') }}"><i class="bi bi-bug"></i> System Tickets @if($openSystemTicketsCount > 0)<span class="notification-badge">{{ $openSystemTicketsCount }}</span>@endif</a>@endcan
                            </div>
                        </div>

                        {{-- Operations --}}
                        @php
                            try {
                                $overdueCount = \DB::table('order_products as op')
                                    ->join('orders as o','op.order_id','=','o.id')
                                    ->leftJoin('order_status as os','o.order_status_id','=','os.id')
                                    ->whereNotNull('op.eta')
                                    ->where('op.eta','<',now()->format('Y-m-d'))
                                    ->whereNull('op.deleted_at')
                                    ->whereNull('o.deleted_at')
                                    ->whereRaw("LOWER(COALESCE(os.name,'')) NOT LIKE '%collected%'")
                                    ->whereRaw("LOWER(COALESCE(os.name,'')) NOT LIKE '%delivered%'")
                                    ->whereRaw("LOWER(COALESCE(os.name,'')) NOT LIKE '%cancelled%'")
                                    ->whereRaw("LOWER(COALESCE(op.item_status,'')) NOT LIKE '%cancelled%'")
                                    ->whereRaw("LOWER(COALESCE(op.item_status,'')) NOT LIKE '%out of stock%'")
                                    ->count();
                            } catch(\Exception $e) { $overdueCount = 0; }
                        @endphp
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.order-products-eta.*','admin.processing-overdue.*','admin.activity-log.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-clock-history"></i>
                                <span class="rail-label">Ops</span>
                                @if($overdueCount > 0 || $processingOverdueItemsCount > 0)<span class="rail-badge"></span>@endif
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Operations</div>
                                @can('eta-overdue.index')<a class="flyout-link {{ request()->routeIs('admin.order-products-eta.*') ? 'flyout-active' : '' }}" href="{{ route('admin.order-products-eta.index') }}"><i class="bi bi-clock-history"></i> Overdue Items (ETA) @if($overdueCount > 0)<span class="notification-badge">{{ $overdueCount }}</span>@endif</a>@endcan
                                @can('processing-overdue.index')<a class="flyout-link {{ request()->routeIs('admin.processing-overdue.*') ? 'flyout-active' : '' }}" href="{{ route('admin.processing-overdue.index') }}"><i class="bi bi-clock-history"></i> Processing Overdue @if($processingOverdueItemsCount > 0)<span class="notification-badge">{{ $processingOverdueItemsCount }}</span>@endif</a>@endcan
                                @can('activity-log.view')<a class="flyout-link {{ request()->routeIs('admin.activity-log.*') ? 'flyout-active' : '' }}" href="{{ route('admin.activity-log.index') }}"><i class="bi bi-shield-lock-fill" style="color:#c084fc"></i> Audit Trail</a>@endcan
                            </div>
                        </div>

                        {{-- Configuration --}}
                        @if(auth()->user()->can('settings.index') || auth()->user()->can('theme-options.index') || auth()->user()->can('home-pages.index') || auth()->user()->can('currency.index'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.settings.*','admin.elasticsearch.*','admin.theme-options.*','admin.home-pages.*','admin.currencies.*','admin.app-version.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-sliders"></i>
                                <span class="rail-label">Config</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Configuration</div>
                                @can('settings.index')
                                <a class="flyout-link {{ request()->routeIs('admin.settings.*') ? 'flyout-active' : '' }}" href="{{ route('admin.settings.index') }}"><i class="bi bi-gear-fill"></i> Settings</a>
                                <a class="flyout-link {{ request()->routeIs('admin.elasticsearch.*') ? 'flyout-active' : '' }}" href="{{ route('admin.elasticsearch.reindex') }}"><i class="bi bi-arrow-repeat"></i> Elasticsearch Reindex</a>
                                @endcan
                                @can('currency.index')<a class="flyout-link {{ request()->routeIs('admin.currencies.*') ? 'flyout-active' : '' }}" href="{{ route('admin.currencies.index') }}"><i class="bi bi-currency-exchange"></i> Currencies</a>@endcan
                                @can('theme-options.index')<a class="flyout-link {{ request()->routeIs('admin.theme-options.*') ? 'flyout-active' : '' }}" href="{{ route('admin.theme-options.index') }}"><i class="bi bi-palette-fill"></i> Theme Options</a>@endcan
                                @can('home-pages.index')<a class="flyout-link {{ request()->routeIs('admin.home-pages.*') ? 'flyout-active' : '' }}" href="{{ route('admin.home-pages.index') }}"><i class="bi bi-house-fill"></i> Home Pages</a>@endcan
                                @can('settings.index')<a class="flyout-link {{ request()->routeIs('admin.app-version.*') ? 'flyout-active' : '' }}" href="{{ route('admin.app-version.index') }}"><i class="bi bi-phone-fill"></i> App Version</a>@endcan
                            </div>
                        </div>
                        @endif

                        {{-- Users & Roles --}}
                        @if(auth()->user()->can('user.index') || auth()->user()->can('role.index'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.users.*','admin.roles.*','admin.whatsapp.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-person-badge"></i>
                                <span class="rail-label">Users</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Users & Roles</div>
                                @can('user.index')<a class="flyout-link {{ request()->routeIs('admin.users.*') ? 'flyout-active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-person-fill"></i> Users</a>@endcan
                                @can('role.index')<a class="flyout-link {{ request()->routeIs('admin.roles.*') ? 'flyout-active' : '' }}" href="{{ route('admin.roles.index') }}"><i class="bi bi-shield-check"></i> Roles</a>@endcan
                                <a class="flyout-link {{ request()->routeIs('admin.whatsapp.*') ? 'flyout-active' : '' }}" href="{{ route('admin.whatsapp.agents.index') }}"><i class="bi bi-whatsapp"></i> WhatsApp Agents</a>
                            </div>
                        </div>
                        @endif

                        @endif {{-- end !vendor|consumer --}}

                        {{-- Vendor (vendor role only) --}}
                        @if(auth()->user()->hasRole('vendor'))
                        <div class="rail-item has-flyout {{ request()->routeIs('admin.vendor.*') ? 'rail-active' : '' }}">
                            <div class="rail-link">
                                <i class="bi bi-shop"></i>
                                <span class="rail-label">My Shop</span>
                            </div>
                            <div class="rail-flyout">
                                <div class="flyout-title">Vendor</div>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.dashboard') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.products') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.products') }}"><i class="bi bi-box-seam"></i> My Products</a>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.products.import') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.products.import') }}"><i class="bi bi-file-earmark-arrow-up"></i> Import Products</a>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.orders') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.orders') }}"><i class="bi bi-cart-check"></i> My Orders</a>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.commissions') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.commissions') }}"><i class="bi bi-cash-coin"></i> My Commissions</a>
                                <a class="flyout-link {{ request()->routeIs('admin.vendor.withdrawals.*') ? 'flyout-active' : '' }}" href="{{ route('admin.vendor.withdrawals.index') }}"><i class="bi bi-piggy-bank"></i> My Withdrawals</a>
                            </div>
                        </div>
                        @endif

                    </div>{{-- end rail-nav --}}

                    {{-- Footer: currency picker --}}
                    <div class="rail-footer">
                        <div class="rail-item has-flyout">
                            <div class="rail-link" style="min-height:44px;">
                                <i class="bi bi-currency-exchange"></i>
                                <span class="rail-label">{{ auth()->user()->preferred_currency ?? 'USD' }}</span>
                            </div>
                            <div class="rail-flyout" id="currencyFlyout">
                                <div class="flyout-title">Currency</div>
                                @foreach(getCachedActiveCurrencies() as $curr)
                                <a class="flyout-link currency-option {{ (auth()->user()->preferred_currency ?? 'USD') === $curr->code ? 'flyout-active' : '' }}"
                                   href="#" data-currency="{{ $curr->code }}" data-symbol="{{ $curr->symbol }}" data-rate="{{ $curr->exchange_rate }}">
                                    <i class="bi bi-{{ (auth()->user()->preferred_currency ?? 'USD') === $curr->code ? 'check-circle-fill text-success' : 'circle' }}"></i>
                                    {{ $curr->code }} ({{ $curr->symbol }})
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

            </nav>

            <!-- Main Content -->
            <main class="content-wrapper" style="flex:1;">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Logout function
        function handleLogout(event) {
            event.preventDefault();
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_user');
            window.location.href = '/admin/login';
        }

        // Icon rail flyout hover behaviour
        document.addEventListener('DOMContentLoaded', function() {
            var openTimer, closeTimer, currentItem = null;

            function positionFlyout(item) {
                var flyout = item.querySelector('.rail-flyout');
                if (!flyout) return;
                var rect = item.getBoundingClientRect();
                var top = rect.top;
                var maxTop = window.innerHeight - flyout.offsetHeight - 8;
                if (top > maxTop) top = maxTop;
                if (top < 8) top = 8;
                flyout.style.top = top + 'px';
            }

            document.querySelectorAll('.rail-item.has-flyout').forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    clearTimeout(closeTimer);
                    openTimer = setTimeout(function() {
                        if (currentItem && currentItem !== item) {
                            var prev = currentItem.querySelector('.rail-flyout');
                            if (prev) prev.style.display = 'none';
                        }
                        currentItem = item;
                        var flyout = item.querySelector('.rail-flyout');
                        if (flyout) {
                            flyout.style.display = 'block';
                            positionFlyout(item);
                        }
                    }, 130);
                });
                item.addEventListener('mouseleave', function() {
                    clearTimeout(openTimer);
                    closeTimer = setTimeout(function() {
                        var flyout = item.querySelector('.rail-flyout');
                        if (flyout) flyout.style.display = 'none';
                        if (currentItem === item) currentItem = null;
                    }, 150);
                });
            });

            // Handle currency selection
            document.querySelectorAll('.currency-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();

                    const currency = this.dataset.currency;
                    const symbol = this.dataset.symbol;
                    const rate = this.dataset.rate;

                    // Show loading
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    loadingOverlay.classList.add('show');

                    // Save to backend
                    fetch('/admin/update-currency', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            currency: currency,
                            symbol: symbol,
                            exchange_rate: rate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload page to apply new currency
                            window.location.reload();
                        } else {
                            showError('Error', 'Failed to update currency: ' + (data.message || 'Unknown error'));
                            loadingOverlay.classList.remove('show');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Error', 'Failed to update currency');
                        loadingOverlay.classList.remove('show');
                    });
                });
            });

            // Show loading overlay on navigation ONLY (NOT on forms - interferes with streaming imports)
            const loadingOverlay = document.getElementById('loadingOverlay');
            document.querySelectorAll('a:not([target="_blank"]):not([data-bs-toggle])').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.includes('javascript:')) {
                        loadingOverlay.classList.add('show');
                    }
                });
            });
            // REMOVED: Form submit loading overlay - it blocks streaming imports (CSV import, fast import, etc.)
            // Forms that need loading indicators should implement their own button states
        });
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global SweetAlert Helper Functions -->
    <script>
        // Global SweetAlert Configuration
        const SwalConfig = {
            confirmColor: '#ef5350',
            successColor: '#28a745',
            errorColor: '#dc3545',
            warningColor: '#ffc107',
            cancelColor: '#6c757d'
        };

        // Success Alert
        function showSuccess(title, text = '') {
            return Swal.fire({
                icon: 'success',
                title: title,
                text: text,
                confirmButtonColor: SwalConfig.confirmColor,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Error Alert
        function showError(title, text = '') {
            return Swal.fire({
                icon: 'error',
                title: title,
                text: text,
                confirmButtonColor: SwalConfig.confirmColor
            });
        }

        // Warning Alert
        function showWarning(title, text = '') {
            return Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                confirmButtonColor: SwalConfig.confirmColor
            });
        }

        // Confirm Dialog (for delete actions)
        function confirmDelete(title = 'Are you sure?', text = 'This action cannot be undone!') {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: SwalConfig.errorColor,
                cancelButtonColor: SwalConfig.cancelColor,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });
        }

        // Confirm Action (generic)
        function confirmAction(title, text = '', confirmText = 'Yes, proceed', icon = 'question') {
            return Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: SwalConfig.successColor,
                cancelButtonColor: SwalConfig.cancelColor,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel'
            });
        }

        // Form Submit with Loading
        function submitFormWithLoading(formElement, options = {}) {
            const {
                title = 'Confirm',
                text = 'Are you sure you want to proceed?',
                icon = 'question',
                confirmText = 'Yes, submit',
                successTitle = 'Success!',
                successText = 'Operation completed successfully',
                redirectUrl = null
            } = options;

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: SwalConfig.successColor,
                cancelButtonColor: SwalConfig.cancelColor,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => {
                    return fetch(formElement.action, {
                        method: formElement.method || 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: new FormData(formElement)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }
                        return response;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: successTitle,
                        text: successText,
                        confirmButtonColor: SwalConfig.confirmColor,
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else {
                            window.location.reload();
                        }
                    });
                }
            });
        }

        // Loading Toast
        function showLoadingToast(message = 'Processing...') {
            return Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        // Toast Notification
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        function showToast(type, message) {
            Toast.fire({
                icon: type,
                title: message
            });
        }

        // ── Global native alert() override ────────────────────────────────────
        window.alert = function(message) {
            const lc = String(message).toLowerCase();
            let icon = 'info';
            if (/error|fail|invalid|unable|cannot|could not|wrong/.test(lc)) icon = 'error';
            else if (/success|saved|done|complete|updated|deleted|created|added|removed|copied/.test(lc)) icon = 'success';
            else if (/warning|warn|careful|caution|please/.test(lc)) icon = 'warning';
            Swal.fire({ icon, title: message, confirmButtonColor: SwalConfig.confirmColor });
        };

        // ── Global data-swal-confirm delegation (forms & click elements) ──────
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form[data-swal-confirm]');
            if (!form) return;
            e.preventDefault();
            const isDelete = /delete|remove|clear|destroy/i.test(form.getAttribute('data-swal-confirm'));
            Swal.fire({
                title: 'Are you sure?',
                text: form.getAttribute('data-swal-confirm'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isDelete ? SwalConfig.errorColor : SwalConfig.confirmColor,
                cancelButtonColor: SwalConfig.cancelColor,
                confirmButtonText: isDelete ? 'Yes, proceed' : 'Confirm',
                cancelButtonText: 'Cancel',
            }).then(result => {
                if (result.isConfirmed) {
                    form.removeAttribute('data-swal-confirm');
                    form.submit();
                }
            });
        }, true);

        document.addEventListener('click', function(e) {
            const el = e.target.closest('a[data-swal-confirm], button[data-swal-confirm]');
            if (!el) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            const isDelete = /delete|remove|clear|destroy/i.test(el.getAttribute('data-swal-confirm'));
            Swal.fire({
                title: 'Are you sure?',
                text: el.getAttribute('data-swal-confirm'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isDelete ? SwalConfig.errorColor : SwalConfig.confirmColor,
                cancelButtonColor: SwalConfig.cancelColor,
                confirmButtonText: isDelete ? 'Yes, proceed' : 'Confirm',
                cancelButtonText: 'Cancel',
            }).then(result => {
                if (result.isConfirmed) {
                    if (el.form) { el.removeAttribute('data-swal-confirm'); el.form.submit(); }
                    else if (el.href && el.href !== '#') window.location.href = el.href;
                }
            });
        }, true);

        // Auto-show alerts from session
        @if(session('success'))
            showSuccess('Success!', '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("success"))) }}');
        @endif

        @if(session('error'))
            showError('Error!', '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("error"))) }}');
        @endif

        @if(session('warning'))
            showWarning('Warning!', '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("warning"))) }}');
        @endif

        @if(session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Information',
                text: '{{ session("info") }}',
                confirmButtonColor: SwalConfig.confirmColor
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>



