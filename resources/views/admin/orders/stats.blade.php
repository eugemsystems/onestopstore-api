@extends('admin.layout')

@section('title', 'Order Statistics')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
    .col-md-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }
    @media (max-width: 768px) {
        .col-md-2-4 {
            width: 100%;
        }
    }
    #status-funnel {
        font-size: 0.9rem;
    }
    .funnel-item {
        padding: 10px;
        margin-bottom: 8px;
        border-radius: 5px;
        background: linear-gradient(90deg, var(--color) 0%, transparent 100%);
        border-left: 4px solid var(--color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .daterangepicker {
        z-index: 9999 !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h2 class="mb-1">📊 Order Statistics</h2>
            <p class="text-muted mb-0">Comprehensive analytics and insights for your orders</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary period-filter active" data-days="7">7 Days</button>
                <button type="button" class="btn btn-outline-primary period-filter" data-days="30">30 Days</button>
                <button type="button" class="btn btn-outline-primary period-filter" data-days="90">90 Days</button>
                <button type="button" class="btn btn-outline-primary period-filter" data-days="365">1 Year</button>
            </div>
            <div class="input-group" style="width: 300px;">
                <input type="text" id="dateRangePicker" class="form-control" placeholder="Custom Date Range" readonly>
                <button class="btn btn-outline-secondary" type="button" id="clearDateRange" title="Clear custom date">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-5">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading statistics...</p>
    </div>

    <!-- Main Content (Hidden initially) -->
    <div id="stats-content" style="display: none;">
        <!-- Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Total Orders</p>
                                <h3 class="mb-0" id="total-orders">0</h3>
                                <small class="text-success" id="orders-period-text"></small>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-cart-check fs-4 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Total Revenue</p>
                                <h3 class="mb-0" id="total-revenue">$0</h3>
                                <small class="text-success" id="revenue-period-text"></small>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-currency-dollar fs-4 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Avg Order Value</p>
                                <h3 class="mb-0" id="avg-order-value">$0</h3>
                                <small class="text-muted">All time average</small>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-graph-up fs-4 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Period Orders</p>
                                <h3 class="mb-0" id="period-orders">0</h3>
                                <small class="text-muted" id="period-label">Last 30 days</small>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-calendar-range fs-4 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-2-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;">
                    <div class="card-body text-white">
                        <small class="d-block mb-2" style="color: rgba(255,255,255,0.8);">Total Shipping Revenue</small>
                        <h4 class="mb-0 mt-2 text-white" id="total-shipping">$0</h4>
                        <small class="d-block mt-2" style="color: rgba(255,255,255,0.7);" id="shipping-period">In period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;">
                    <div class="card-body text-white">
                        <small class="d-block mb-2" style="color: rgba(255,255,255,0.8);">Total Discounts</small>
                        <h4 class="mb-0 mt-2 text-white" id="total-discounts">$0</h4>
                        <small class="d-block mt-2" style="color: rgba(255,255,255,0.7);" id="discounts-period">In period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;">
                    <div class="card-body text-white">
                        <small class="d-block mb-2" style="color: rgba(255,255,255,0.8);">Avg Items Per Order</small>
                        <h4 class="mb-0 mt-2 text-white" id="avg-items">0</h4>
                        <small class="d-block mt-2" style="color: rgba(255,255,255,0.7);">Items per transaction</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;">
                    <div class="card-body text-white">
                        <small class="d-block mb-2" style="color: rgba(255,255,255,0.8);">Order Completion Rate</small>
                        <h4 class="mb-0 mt-2 text-white" id="completion-rate">0%</h4>
                        <small class="d-block mt-2" style="color: rgba(255,255,255,0.7);">Delivered vs Total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;">
                    <div class="card-body text-white">
                        <small class="d-block mb-2" style="color: rgba(255,255,255,0.8);">Cancellation Rate</small>
                        <h4 class="mb-0 mt-2 text-white" id="cancellation-rate">0%</h4>
                        <small class="d-block mt-2" style="color: rgba(255,255,255,0.7);">Cancelled orders</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Breakdown Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">💰 Revenue Breakdown</h5>
                        <small class="text-white-50">Detailed revenue components analysis</small>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueBreakdownChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📊 Order Status Funnel</h5>
                        <small class="text-white-50">Conversion through stages</small>
                    </div>
                    <div class="card-body">
                        <div id="status-funnel" class="p-2">
                            <!-- Funnel will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping & Delivery Analytics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🚚 Shipping Methods Performance</h5>
                        <small class="text-white-50">Orders by shipping type</small>
                    </div>
                    <div class="card-body">
                        <canvas id="shippingMethodsChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">⏱️ Delivery Performance</h5>
                        <small class="text-white-50">Average delivery times by status</small>
                    </div>
                    <div class="card-body">
                        <canvas id="deliveryPerformanceChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Geographic Analysis -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🌍 Top 10 Countries by Orders</h5>
                        <small class="text-white-50">Geographic distribution of orders</small>
                    </div>
                    <div class="card-body">
                        <canvas id="countriesChart" height="60"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly & Daily Patterns -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🕐 Orders by Hour of Day</h5>
                        <small class="text-white-50">Peak ordering hours</small>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📅 Orders by Day of Week</h5>
                        <small class="text-white-50">Weekly pattern analysis</small>
                    </div>
                    <div class="card-body">
                        <canvas id="weekdayChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cohort & Variations Analysis -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">💳 Revenue by Currency</h5>
                        <small class="text-white-50">Multi-currency breakdown</small>
                    </div>
                    <div class="card-body">
                        <canvas id="currencyChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🎨 Top Product Variations</h5>
                        <small class="text-white-50">Most popular variation combinations</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Variation</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="variations-table">
                                    <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Original Charts Row 1 -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📈 Orders & Revenue Timeline</h5>
                        <small class="text-white-50">Daily orders and revenue over time</small>
                    </div>
                    <div class="card-body">
                        <canvas id="ordersTimelineChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🎯 Order Status Distribution</h5>
                        <small class="text-white-50">Current status breakdown</small>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">💳 Payment Methods</h5>
                        <small class="text-white-50">Distribution by payment type</small>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentMethodChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">✅ Payment Status</h5>
                        <small class="text-white-50">Payment completion status</small>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentStatusChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Table -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">🏆 Top 10 Best Selling Products</h5>
                        <small class="text-white-50">Most purchased products in selected period</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th class="text-end">Quantity Sold</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Revenue</th>
                                        <th class="text-end">Avg Price</th>
                                    </tr>
                                </thead>
                                <tbody id="top-products-table">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">💰 Top Revenue Products</h5>
                        <small class="text-white-50">Highest earning products</small>
                    </div>
                    <div class="card-body">
                        <canvas id="topRevenueChart" height="140"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📦 Item Status Distribution</h5>
                        <small class="text-white-50">Order items by status</small>
                    </div>
                    <div class="card-body">
                        <canvas id="itemStatusChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📂 Top 10 Categories by Revenue</h5>
                        <small class="text-white-50">Best performing product categories</small>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryPerformanceChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Insights -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">👥 Top Customers by Orders</h5>
                        <small class="text-white-50">Most frequent buyers</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody id="top-customers-orders-table">
                                    <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">💎 Top Customers by Revenue</h5>
                        <small class="text-white-50">Highest spenders</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody id="top-customers-revenue-table">
                                    <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Acquisition -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-plus-fill text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1" id="new-customers">0</h3>
                        <p class="text-muted mb-0">New Customers</p>
                        <small class="text-muted">First-time buyers in period</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-arrow-repeat text-success" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1" id="returning-customers">0</h3>
                        <p class="text-muted mb-0">Returning Customers</p>
                        <small class="text-muted">Repeat buyers in period</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-percent text-info" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-1" id="retention-rate">0%</h3>
                        <p class="text-muted mb-0">Retention Rate</p>
                        <small class="text-muted">Returning vs new customers</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0">📊 Average Time in Each Status</h5>
                        <small class="text-white-50">How long orders stay in each status</small>
                    </div>
                    <div class="card-body">
                        <canvas id="avgTimeStatusChart" height="60"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
// Disable datalabels plugin globally - we'll enable it per chart
Chart.register(ChartDataLabels);
Chart.defaults.set('plugins.datalabels', {
    display: false
});

let currentDays = 30;
let customDateRange = null;
let charts = {};

// Chart color schemes
const colors = {
    primary: '#0d6efd',
    success: '#198754',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#0dcaf0',
    purple: '#6f42c1',
    pink: '#d63384',
    teal: '#20c997',
    orange: '#fd7e14',
    indigo: '#6610f2'
};

const colorPalette = [
    colors.primary, colors.success, colors.danger, colors.warning,
    colors.info, colors.purple, colors.pink, colors.teal, colors.orange, colors.indigo
];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    $('#dateRangePicker').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        },
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment()],
        }
    });

    $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        customDateRange = {
            start: picker.startDate.format('YYYY-MM-DD'),
            end: picker.endDate.format('YYYY-MM-DD')
        };
        // Deactivate period buttons
        document.querySelectorAll('.period-filter').forEach(b => b.classList.remove('active'));
        loadAllStatsCustom(customDateRange.start, customDateRange.end);
    });

    $('#clearDateRange').on('click', function() {
        $('#dateRangePicker').val('');
        customDateRange = null;
        // Reactivate 30 days button
        document.querySelectorAll('.period-filter').forEach(b => b.classList.remove('active'));
        document.querySelector('.period-filter[data-days="30"]').classList.add('active');
        currentDays = 30;
        loadAllStats(30);
    });

    loadAllStats(30);

    // Period filter buttons
    document.querySelectorAll('.period-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentDays = parseInt(this.dataset.days);
            customDateRange = null;
            $('#dateRangePicker').val('');
            loadAllStats(currentDays);
        });
    });
});

async function loadAllStatsCustom(startDate, endDate) {
    showLoading();
    const params = `start_date=${startDate}&end_date=${endDate}`;
    try {
        const [overview, topProducts, statusDetails, productPerformance, customerInsights] = await Promise.all([
            fetch(`/admin/orders/stats/overview?${params}`).then(r => r.json()),
            fetch(`/admin/orders/stats/top-products?${params}&limit=10`).then(r => r.json()),
            fetch(`/admin/orders/stats/status-details?${params}`).then(r => r.json()),
            fetch(`/admin/orders/stats/product-performance?${params}`).then(r => r.json()),
            fetch(`/admin/orders/stats/customer-insights?${params}`).then(r => r.json())
        ]);

        if (overview.success) {
            const days = moment(endDate).diff(moment(startDate), 'days') + 1;
            renderOverviewCards(overview.data, days, true);
            renderAllCharts(overview.data, statusDetails.data, productPerformance.data, customerInsights.data);
        }

        if (topProducts.success) {
            renderTopProductsTable(topProducts.data);
        }

        hideLoading();
    } catch (error) {
        console.error('Error loading stats:', error);
        showError('Error', 'Failed to load statistics. Please try again.');
        hideLoading();
    }
}

async function loadAllStats(days) {
    showLoading();

    try {
        // Load all data in parallel
        const [overview, topProducts, statusDetails, productPerformance, customerInsights] = await Promise.all([
            fetch(`/admin/orders/stats/overview?days=${days}`).then(r => r.json()),
            fetch(`/admin/orders/stats/top-products?days=${days}&limit=10`).then(r => r.json()),
            fetch(`/admin/orders/stats/status-details?days=${days}`).then(r => r.json()),
            fetch(`/admin/orders/stats/product-performance?days=${days}`).then(r => r.json()),
            fetch(`/admin/orders/stats/customer-insights?days=${days}`).then(r => r.json())
        ]);

        if (overview.success) {
            renderOverviewCards(overview.data, days);
            renderOrdersTimeline(overview.data.daily);
            renderStatusPieChart(overview.data.orders_by_status);
            renderPaymentMethodChart(overview.data.by_payment_method);
            renderPaymentStatusChart(overview.data.by_payment_status);

            // Render new charts if data exists
            if (overview.data.by_hour) renderHourlyChart(overview.data.by_hour);
            if (overview.data.by_weekday) renderWeekdayChart(overview.data.by_weekday);
            if (overview.data.top_countries) renderCountriesChart(overview.data.top_countries);
            if (overview.data.by_currency) renderCurrencyChart(overview.data.by_currency);

            // Render additional analysis charts
            renderRevenueBreakdownChart(overview.data);
            renderStatusFunnel(overview.data.orders_by_status);
        }

        if (topProducts.success) {
            renderTopProductsTable(topProducts.data);
        }

        if (statusDetails.success) {
            renderAvgTimeStatusChart(statusDetails.data.avg_time_in_status);
        }

        if (productPerformance.success) {
            renderTopRevenueChart(productPerformance.data.top_revenue_products);
            renderItemStatusChart(productPerformance.data.item_status_distribution);
            renderCategoryPerformanceChart(productPerformance.data.category_performance);

            // Render variations if available
            if (productPerformance.data.top_variations) {
                renderProductVariations(productPerformance.data.top_variations);
            }
        }

        if (statusDetails.success) {
            // Render shipping and delivery charts
            if (statusDetails.data.shipping_methods) {
                renderShippingMethodsChart(statusDetails.data.shipping_methods);
            }
            if (statusDetails.data.avg_time_in_status) {
                renderDeliveryPerformanceChart(statusDetails.data.avg_time_in_status);
            }
        }

        if (customerInsights.success) {
            renderCustomerInsights(customerInsights.data);
        }

        hideLoading();
    } catch (error) {
        console.error('Error loading stats:', error);
        showError('Error', 'Failed to load statistics. Please try again.');
        hideLoading();
    }
}

function showLoading() {
    document.getElementById('loading-spinner').style.display = 'block';
    document.getElementById('stats-content').style.display = 'none';
}

function hideLoading() {
    document.getElementById('loading-spinner').style.display = 'none';
    document.getElementById('stats-content').style.display = 'block';
}

function renderOverviewCards(data, days, isCustom = false) {
    document.getElementById('total-orders').textContent = data.total_orders.toLocaleString();
    document.getElementById('total-revenue').textContent = '$' + data.total_revenue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('avg-order-value').textContent = '$' + data.avg_order_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('period-orders').textContent = data.total_orders_period.toLocaleString();

    const periodText = isCustom ? `${days} days` : (days === 7 ? '7 days' : days === 30 ? '30 days' : days === 90 ? '90 days' : days === 365 ? '1 year' : `${days} days`);
    document.getElementById('orders-period-text').textContent = `+${data.total_orders_period} in last ${periodText}`;
    document.getElementById('revenue-period-text').textContent = `$${data.total_revenue_period.toLocaleString(undefined, {minimumFractionDigits: 2})} in last ${periodText}`;
    document.getElementById('period-label').textContent = isCustom ? `${periodText} (custom)` : `Last ${periodText}`;

    // Update new metric cards
    if (data.total_shipping !== undefined) {
        document.getElementById('total-shipping').textContent = '$' + data.total_shipping.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('shipping-period').textContent = `In last ${periodText}`;
    }

    if (data.total_discounts !== undefined) {
        document.getElementById('total-discounts').textContent = '$' + data.total_discounts.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('discounts-period').textContent = `In last ${periodText}`;
    }

    if (data.avg_items_per_order !== undefined) {
        document.getElementById('avg-items').textContent = data.avg_items_per_order.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
    }

    if (data.completion_rate !== undefined) {
        document.getElementById('completion-rate').textContent = data.completion_rate + '%';
    }

    if (data.cancellation_rate !== undefined) {
        document.getElementById('cancellation-rate').textContent = data.cancellation_rate + '%';
    }
}

function renderOrdersTimeline(dailyData) {
    const ctx = document.getElementById('ordersTimelineChart');

    if (charts.timeline) charts.timeline.destroy();

    charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'Orders',
                    data: dailyData.map(d => d.count),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue',
                    data: dailyData.map(d => d.revenue),
                    borderColor: colors.success,
                    backgroundColor: colors.success + '20',
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { position: 'left', title: { display: true, text: 'Orders' } },
                y1: { position: 'right', title: { display: true, text: 'Revenue ($)' }, grid: { display: false } }
            },
            plugins: {
                legend: { display: true, position: 'top' }
            }
        }
    });
}

function renderStatusPieChart(statusData) {
    const ctx = document.getElementById('statusPieChart');

    if (charts.statusPie) charts.statusPie.destroy();

    const total = statusData.reduce((sum, s) => sum + s.count, 0);

    charts.statusPie = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(s => s.status_name),
            datasets: [{
                data: statusData.map(s => s.count),
                backgroundColor: colorPalette
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    display: true,
                    formatter: (value, ctx) => {
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        // Only show label if percentage is above 5% to avoid clutter
                        return percentage > 5 ? percentage + '%' : '';
                    },
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        }
    });
}

function renderPaymentMethodChart(paymentData) {
    const ctx = document.getElementById('paymentMethodChart');

    if (charts.paymentMethod) charts.paymentMethod.destroy();

    charts.paymentMethod = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: paymentData.map(p => p.method),
            datasets: [{
                label: 'Orders',
                data: paymentData.map(p => p.count),
                backgroundColor: colors.primary
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderPaymentStatusChart(paymentStatusData) {
    const ctx = document.getElementById('paymentStatusChart');

    if (charts.paymentStatus) charts.paymentStatus.destroy();

    charts.paymentStatus = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: paymentStatusData.map(p => p.status),
            datasets: [{
                label: 'Orders',
                data: paymentStatusData.map(p => p.count),
                backgroundColor: colors.success
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderTopProductsTable(products) {
    const tbody = document.getElementById('top-products-table');

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No data available</td></tr>';
        return;
    }

    tbody.innerHTML = products.map((p, i) => `
        <tr>
            <td><strong>${i + 1}</strong></td>
            <td>${p.name}</td>
            <td class="text-end"><strong>${p.quantity.toLocaleString()}</strong></td>
            <td class="text-end">${p.order_count.toLocaleString()}</td>
            <td class="text-end">$${p.revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end">$${(p.revenue / p.quantity).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

function renderTopRevenueChart(topRevenue) {
    const ctx = document.getElementById('topRevenueChart');

    if (charts.topRevenue) charts.topRevenue.destroy();

    charts.topRevenue = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topRevenue.slice(0, 10).map(p => p.name.length > 30 ? p.name.substring(0, 30) + '...' : p.name),
            datasets: [{
                label: 'Revenue',
                data: topRevenue.slice(0, 10).map(p => p.revenue),
                backgroundColor: colors.success
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
}

function renderItemStatusChart(itemStatusData) {
    const ctx = document.getElementById('itemStatusChart');

    if (charts.itemStatus) charts.itemStatus.destroy();

    charts.itemStatus = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: itemStatusData.map(s => s.item_status || 'Unknown'),
            datasets: [{
                label: 'Items',
                data: itemStatusData.map(s => s.count),
                backgroundColor: colorPalette
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
}

function renderCategoryPerformanceChart(categoryData) {
    const ctx = document.getElementById('categoryPerformanceChart');

    if (charts.categoryPerformance) charts.categoryPerformance.destroy();

    charts.categoryPerformance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categoryData.map(c => c.category_name),
            datasets: [{
                label: 'Revenue',
                data: categoryData.map(c => c.total_revenue),
                backgroundColor: colors.info
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderCustomerInsights(data) {
    // Top customers by orders
    const ordersTableBody = document.getElementById('top-customers-orders-table');
    if (data.top_customers_by_orders.length === 0) {
        ordersTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
    } else {
        ordersTableBody.innerHTML = data.top_customers_by_orders.slice(0, 10).map(c => `
            <tr>
                <td>
                    <div class="fw-bold">${c.customer_name}</div>
                    <small class="text-muted">${c.customer_email}</small>
                </td>
                <td class="text-end"><strong>${c.order_count}</strong></td>
                <td class="text-end">$${c.total_spent.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            </tr>
        `).join('');
    }

    // Top customers by revenue
    const revenueTableBody = document.getElementById('top-customers-revenue-table');
    if (data.top_customers_by_revenue.length === 0) {
        revenueTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
    } else {
        revenueTableBody.innerHTML = data.top_customers_by_revenue.slice(0, 10).map(c => `
            <tr>
                <td>
                    <div class="fw-bold">${c.customer_name}</div>
                    <small class="text-muted">${c.customer_email}</small>
                </td>
                <td class="text-end"><strong>${c.order_count}</strong></td>
                <td class="text-end">$${c.total_spent.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            </tr>
        `).join('');
    }

    // Customer acquisition
    document.getElementById('new-customers').textContent = data.new_customers.toLocaleString();
    document.getElementById('returning-customers').textContent = data.returning_customers.toLocaleString();

    const totalCustomers = data.new_customers + data.returning_customers;
    const retentionRate = totalCustomers > 0 ? ((data.returning_customers / totalCustomers) * 100).toFixed(1) : 0;
    document.getElementById('retention-rate').textContent = retentionRate + '%';
}

function renderAvgTimeStatusChart(avgTimeData) {
    const ctx = document.getElementById('avgTimeStatusChart');

    if (charts.avgTimeStatus) charts.avgTimeStatus.destroy();

    charts.avgTimeStatus = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: avgTimeData.map(s => s.status_name),
            datasets: [{
                label: 'Average Hours',
                data: avgTimeData.map(s => s.avg_hours),
                backgroundColor: colors.warning
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Hours' } }
            }
        }
    });
}

// New chart rendering functions
function renderHourlyChart(hourlyData) {
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;

    if (charts.hourly) charts.hourly.destroy();

    // Create array of 24 hours (0-23)
    const hours = Array.from({length: 24}, (_, i) => i);
    const dataMap = {};
    hourlyData.forEach(h => dataMap[h.hour] = h.count);
    const counts = hours.map(h => dataMap[h] || 0);

    charts.hourly = new Chart(ctx, {
        type: 'line',
        data: {
            labels: hours.map(h => `${h}:00`),
            datasets: [{
                label: 'Orders',
                data: counts,
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderWeekdayChart(weekdayData) {
    const ctx = document.getElementById('weekdayChart');
    if (!ctx) return;

    if (charts.weekday) charts.weekday.destroy();

    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dataMap = {};
    weekdayData.forEach(d => dataMap[d.dow] = d.count);
    const counts = [0,1,2,3,4,5,6].map(d => dataMap[d] || 0);

    charts.weekday = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dayNames,
            datasets: [{
                label: 'Orders',
                data: counts,
                backgroundColor: colorPalette
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderCountriesChart(countriesData) {
    const ctx = document.getElementById('countriesChart');
    if (!ctx) return;

    if (charts.countries) charts.countries.destroy();

    charts.countries = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: countriesData.map(c => c.name),
            datasets: [{
                label: 'Orders',
                data: countriesData.map(c => c.count),
                backgroundColor: colors.info
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderCurrencyChart(currencyData) {
    const ctx = document.getElementById('currencyChart');
    if (!ctx) return;

    if (charts.currency) charts.currency.destroy();

    const total = currencyData.reduce((sum, c) => sum + parseFloat(c.revenue), 0);

    charts.currency = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: currencyData.map(c => c.currency),
            datasets: [{
                data: currencyData.map(c => c.revenue),
                backgroundColor: colorPalette
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': $' + value.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    display: true,
                    formatter: (value, ctx) => {
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        // Only show label if percentage is above 5% to avoid clutter
                        return percentage > 5 ? percentage + '%' : '';
                    },
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        }
    });
}

// Revenue Breakdown Chart
function renderRevenueBreakdownChart(data) {
    const ctx = document.getElementById('revenueBreakdownChart');
    if (!ctx) return;

    if (charts.revenueBreakdown) charts.revenueBreakdown.destroy();

    // Calculate components
    const subtotal = data.total_revenue_period - (data.total_shipping || 0) - (data.total_discounts || 0);

    charts.revenueBreakdown = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Revenue Components'],
            datasets: [{
                label: 'Product Sales',
                data: [subtotal],
                backgroundColor: colors.primary
            }, {
                label: 'Shipping',
                data: [data.total_shipping || 0],
                backgroundColor: colors.info
            }, {
                label: 'Discounts',
                data: [-Math.abs(data.total_discounts || 0)],
                backgroundColor: colors.danger
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + Math.abs(context.parsed.y).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + Math.abs(value).toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Status Funnel
function renderStatusFunnel(statusData) {
    const container = document.getElementById('status-funnel');
    if (!container || !statusData || statusData.length === 0) {
        if (container) container.innerHTML = '<p class="text-muted text-center">No data available</p>';
        return;
    }

    const colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754'];
    const sorted = [...statusData].sort((a, b) => b.count - a.count);
    const maxCount = sorted[0].count;

    container.innerHTML = sorted.map((status, index) => {
        const percentage = (status.count / maxCount) * 100;
        const color = colors[index % colors.length];
        return `
            <div class="funnel-item mb-2" style="--color: ${color}; width: ${percentage}%;">
                <span class="fw-bold">${status.status_name}</span>
                <span>${status.count}</span>
            </div>
        `;
    }).join('');
}

// Shipping Methods Chart
function renderShippingMethodsChart(methodsData) {
    const ctx = document.getElementById('shippingMethodsChart');
    if (!ctx) return;

    if (charts.shippingMethods) charts.shippingMethods.destroy();

    // Create mock data if not provided
    const data = methodsData || [
        {method: 'Standard', count: 0},
        {method: 'Express', count: 0}
    ];

    charts.shippingMethods = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(m => m.method || 'Unknown'),
            datasets: [{
                label: 'Orders',
                data: data.map(m => m.count),
                backgroundColor: [colors.primary, colors.success],
                barPercentage: 0.6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
}

// Delivery Performance Chart
function renderDeliveryPerformanceChart(timeData) {
    const ctx = document.getElementById('deliveryPerformanceChart');
    if (!ctx) return;

    if (charts.deliveryPerformance) charts.deliveryPerformance.destroy();

    if (!timeData || timeData.length === 0) {
        // Show placeholder
        ctx.getContext('2d').fillText('No data available', 10, 50);
        return;
    }

    charts.deliveryPerformance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: timeData.map(s => s.status_name),
            datasets: [{
                label: 'Average Hours',
                data: timeData.map(s => s.avg_hours),
                borderColor: colors.warning,
                backgroundColor: colors.warning + '20',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Hours' }
                }
            }
        }
    });
}

// Product Variations Table
function renderProductVariations(variations) {
    const tbody = document.getElementById('variations-table');
    if (!tbody) return;

    if (!variations || variations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No variations data</td></tr>';
        return;
    }

    tbody.innerHTML = variations.slice(0, 10).map((v, index) => `
        <tr>
            <td>
                <span class="badge bg-secondary">#${index + 1}</span>
                ${v.variation_name || 'N/A'}
            </td>
            <td class="text-end">${v.order_count || 0}</td>
            <td class="text-end">$${(v.revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}
</script>
@endpush

