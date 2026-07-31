@extends('admin.layout')

@section('title', 'AI Analytics — Raines Africa Admin')

@section('content')
<style>
    .ai-analytics-wrap { max-width: 1240px; margin: 0 auto; padding: 28px 20px; }

    /* Header */
    .ai-header { margin-bottom: 24px; }
    .ai-header h1 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .ai-header p  { color: #6b7280; margin: 0; font-size: 14px; }

    /* Query card */
    .ai-query-card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
    .ai-query-card label { display: block; font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 8px; }
    .ai-query-card textarea { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 14px; resize: vertical; min-height: 72px; transition: border-color .2s; outline: none; font-family: inherit; box-sizing: border-box; color: #111827; }
    .ai-query-card textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
    .ai-actions { display: flex; gap: 10px; margin-top: 12px; align-items: center; flex-wrap: wrap; }
    .btn-ask { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: opacity .2s, transform .1s; }
    .btn-ask:hover { opacity: .9; transform: translateY(-1px); }
    .btn-ask:disabled { opacity: .6; cursor: not-allowed; transform: none; }
    .btn-ask .spinner { display: none; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
    .btn-ask.loading .spinner { display: inline-block; }
    .btn-ask.loading .btn-label { display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Suggestion categories */
    .suggest-wrap { margin-top: 20px; }
    .suggest-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
    .suggest-tab { background: #f3f4f6; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: #6b7280; cursor: pointer; transition: all .15s; }
    .suggest-tab.active, .suggest-tab:hover { background: #ede9fe; border-color: #a5b4fc; color: #4f46e5; }
    .suggest-group { display: none; flex-wrap: wrap; gap: 8px; }
    .suggest-group.active { display: flex; }
    .pill { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #374151; cursor: pointer; transition: all .15s; line-height: 1.4; }
    .pill:hover { background: #ede9fe; border-color: #a5b4fc; color: #4f46e5; transform: translateY(-1px); box-shadow: 0 2px 6px rgba(99,102,241,.15); }

    /* History */
    .ai-history { margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6; }
    .history-label { font-size: 12px; color: #9ca3af; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; font-weight: 500; }
    .history-label a { font-size: 11px; color: #9ca3af; text-decoration: none; }
    .history-label a:hover { color: #ef4444; }
    .history-items { display: flex; flex-wrap: wrap; gap: 6px; }
    .history-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 10px; font-size: 12px; color: #374151; cursor: pointer; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .history-item:hover { background: #ede9fe; border-color: #a5b4fc; color: #4f46e5; }

    /* Result */
    .ai-result-card { background: #fff; border-radius: 14px; padding: 0; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }
    .ai-result-meta { background: #f8f7ff; border-bottom: 1px solid #ede9fe; padding: 14px 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .ai-result-meta .meta-item { font-size: 12px; color: #6b7280; }
    .ai-result-meta .meta-item strong { color: #374151; }
    .ai-result-body { padding: 0; }
    .ai-sql-toggle { padding: 10px 20px; background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
    .ai-sql-toggle summary { font-size: 12px; color: #9ca3af; cursor: pointer; user-select: none; }
    .ai-sql-code { background: #1e1e2e; color: #cdd6f4; padding: 12px 16px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-word; margin-top: 8px; }

    /* Error / empty */
    .ai-error { padding: 40px; text-align: center; color: #dc2626; }
    .ai-error h4 { margin: 0 0 8px; }

    /* Loading */
    .ai-loading { padding: 60px 20px; text-align: center; color: #6b7280; }
    .ai-loading .loader { width: 44px; height: 44px; border: 3px solid #e5e7eb; border-top-color: #6366f1; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 16px; }
</style>

<div class="ai-analytics-wrap">

    {{-- Header --}}
    @php $service = app(\App\Services\AIAnalyticsService::class); @endphp
    <div class="ai-header">
        <h1>🤖 AI Analytics
            <span style="font-size:12px;font-weight:600;background:#ede9fe;color:#6d28d9;padding:4px 12px;border-radius:20px;vertical-align:middle;">
                {{ $service->getProviderName() }}
            </span>
        </h1>
        <p>Ask any question about your business data in plain English — the AI reads your live database and generates a full visual report.</p>
        <p style="margin-top:4px;font-size:12px;color:#9ca3af;">
            Switch provider via <code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;">AI_PROVIDER</code> in <code style="background:#f3f4f6;padding:1px 5px;border-radius:3px;">.env</code>:
            @foreach(\App\Services\AIAnalyticsService::allProviders() as $key => $p)
                <strong style="{{ $service->getActiveProvider() === $key ? 'color:#6d28d9;' : 'color:#9ca3af;' }}">{{ $key }}</strong>{{ !$loop->last ? ' · ' : '' }}
            @endforeach
        </p>
    </div>

    {{-- Flash error --}}
    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#dc2626;margin-bottom:20px;font-size:14px;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Query card --}}
    <div class="ai-query-card">
        <label for="ai-question">Ask a question about your data</label>

        <form id="ai-form" method="POST" action="{{ route('admin.ai-analytics.query') }}">
            @csrf
            <textarea
                id="ai-question"
                name="question"
                placeholder="e.g. Who are the top 10 customers by total spend on completed orders this month?"
                autocomplete="off"
                spellcheck="false"
            >{{ old('question', session('question', '')) }}</textarea>

            <div class="ai-actions">
                <button type="submit" class="btn-ask" id="ask-btn">
                    <span class="spinner"></span>
                    <span class="btn-label">✨ Generate Report</span>
                </button>
                <span style="font-size:12px;color:#9ca3af;">{{ $service->getProviderName() }} · live database · results cached 10 min</span>
            </div>
        </form>

        {{-- Suggested questions by category --}}
        <div class="suggest-wrap">
            <div style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">💡 Suggested Reports</div>
            <div class="suggest-tabs">
                <button class="suggest-tab active" onclick="switchTab(this,'tab-revenue')">💰 Revenue</button>
                <button class="suggest-tab" onclick="switchTab(this,'tab-orders')">📦 Orders</button>
                <button class="suggest-tab" onclick="switchTab(this,'tab-products')">🛍️ Products</button>
                <button class="suggest-tab" onclick="switchTab(this,'tab-customers')">👥 Customers</button>
                <button class="suggest-tab" onclick="switchTab(this,'tab-cart')">🛒 Cart &amp; Behaviour</button>
                <button class="suggest-tab" onclick="switchTab(this,'tab-vendors')">🏪 Vendors</button>
            </div>

            <div id="tab-revenue" class="suggest-group active">
                @foreach([
                    'Show daily revenue for the last 30 days',
                    'Show monthly revenue for the last 6 months',
                    'What is the total revenue this month compared to last month?',
                    'Show revenue breakdown by payment method',
                    'What is the average order value for completed orders this month?',
                    'Show hourly revenue distribution for today',
                    'Which day of the week generates the most revenue?',
                    'Show cumulative revenue growth over the last 90 days',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>

            <div id="tab-orders" class="suggest-group">
                @foreach([
                    'How many orders were placed in the last 7 days?',
                    'Show order count by status for this month',
                    'What is the order completion rate this month?',
                    'Show orders placed per day for the last 2 weeks',
                    'What are the top 10 highest value orders this month?',
                    'How many orders were refunded this month?',
                    'Show orders by payment status breakdown',
                    'What is the average time between orders per customer?',
                    'Show failed vs completed orders by payment method',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>

            <div id="tab-products" class="suggest-group">
                @foreach([
                    'What are the top 10 products by revenue this month?',
                    'Show top 20 products by quantity sold this month',
                    'Which products have the most orders but lowest revenue?',
                    'What are the top 5 product categories by sales?',
                    'Which products have zero sales in the last 30 days?',
                    'Show the most reviewed products with their average rating',
                    'Which products have the lowest average rating?',
                    'Show products with the most coupon usage',
                    'What are the top selling products per category?',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>

            <div id="tab-customers" class="suggest-group">
                @foreach([
                    'Who are the top 20 customers by total spend on completed orders?',
                    'How many new customers registered this month?',
                    'Show new customer registrations per day for the last 30 days',
                    'Which customers have placed more than 5 orders?',
                    'Show customers who have not ordered in the last 90 days',
                    'What is the average spend per customer this month?',
                    'Show customers with the most refunds',
                    'Which customers have used coupons the most?',
                    'Show top customers by number of reviews submitted',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>

            <div id="tab-cart" class="suggest-group">
                @foreach([
                    'Show cart abandonment count by device type',
                    'How many carts were abandoned in the last 30 days?',
                    'What is the cart abandonment trend over the last 2 weeks?',
                    'Show abandoned cart value by day for the last 7 days',
                    'Which pages have the most views this month?',
                    'Show user session count by device type',
                    'What are the most used coupons this month?',
                    'Show total discount given by coupon code this month',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>

            <div id="tab-vendors" class="suggest-group">
                @foreach([
                    'Which vendor has the highest sales this month?',
                    'Show top 10 vendors by revenue this month',
                    'Show total commission earned per vendor this month',
                    'Which vendors have the most products listed?',
                    'Show vendor sales compared to last month',
                    'Which vendors have pending withdrawal requests?',
                    'Show top vendors by number of completed orders',
                    'Which vendor has the highest average order value?',
                ] as $q)
                    <span class="pill" onclick="setQuestion(this)">{{ $q }}</span>
                @endforeach
            </div>
        </div>

        {{-- History --}}
        @if(!empty($history))
        <div class="ai-history">
            <div class="history-label">
                <span>🕐 Recent questions</span>
                <a href="{{ route('admin.ai-analytics.clear-history') }}">Clear history</a>
            </div>
            <div class="history-items">
                @foreach($history as $h)
                    <span class="history-item" onclick="setQuestion(this)" title="{{ $h['question'] }}">
                        {{ $h['time'] }} — {{ Str::limit($h['question'], 55) }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Result --}}
    <div id="result-container">
    @if(session('result'))
        @php $result = session('result'); $q = session('question', ''); @endphp
        @if(!($result['success'] ?? false))
            <div class="ai-result-card">
                <div class="ai-error">
                    <h4>⚠️ Could not generate report</h4>
                    <p style="color:#6b7280;margin:0;">{{ $result['error'] ?? 'Unknown error' }}</p>
                </div>
            </div>
        @else
            <div class="ai-result-card">
                <div class="ai-result-meta">
                    <div class="meta-item" style="flex:1;min-width:200px"><strong>Question:</strong> {{ $q }}</div>
                    <div class="meta-item"><strong>Rows:</strong> {{ number_format($result['row_count'] ?? 0) }}</div>
                    @if(!empty($result['provider']))
                        <div class="meta-item"><strong>Model:</strong> {{ $result['provider'] }}</div>
                    @endif
                    @if(!empty($result['explanation']))
                        <div class="meta-item" style="flex:2;min-width:240px"><strong>What this shows:</strong> {{ $result['explanation'] }}</div>
                    @endif
                </div>

                @if(!empty($result['sql']))
                <div class="ai-sql-toggle">
                    <details>
                        <summary>View generated SQL query</summary>
                        <div class="ai-sql-code">{{ $result['sql'] }}</div>
                    </details>
                </div>
                @endif

                <div class="ai-result-body" id="report-html">
                    {!! $result['html'] ?? '' !!}
                </div>
            </div>
        @endif
    @endif
    </div>

</div>

<script>
function setQuestion(el) {
    const text = el.dataset.question || el.title || el.textContent.trim();
    const clean = text.replace(/^\d{2}:\d{2}\s*—\s*/, '');
    const ta = document.getElementById('ai-question');
    ta.value = clean;
    ta.focus();
    ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function switchTab(btn, groupId) {
    document.querySelectorAll('.suggest-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.suggest-group').forEach(g => g.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(groupId).classList.add('active');
}

document.getElementById('ai-form').addEventListener('submit', function () {
    const btn = document.getElementById('ask-btn');
    btn.classList.add('loading');
    btn.disabled = true;
    document.getElementById('result-container').innerHTML = `
        <div class="ai-result-card">
            <div class="ai-loading">
                <div class="loader"></div>
                <p style="margin:0;font-weight:600;font-size:15px;">Analysing your data…</p>
                <p style="margin:6px 0 0;font-size:13px;color:#9ca3af;">Generating SQL · running query · building report</p>
            </div>
        </div>`;
});
</script>
@endsection
