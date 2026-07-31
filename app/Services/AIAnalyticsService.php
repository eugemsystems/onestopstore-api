<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAnalyticsService
{
    private const PROVIDERS = [
        'anthropic'  => ['name' => 'Anthropic (Claude)',  'default_model' => 'claude-haiku-4-5',                      'report_mode' => 'ai',       'report_tokens' => 12000, 'report_rows' => 200],
        'groq'       => ['name' => 'Groq (Llama)',        'default_model' => 'llama-3.3-70b-versatile',               'report_mode' => 'template', 'report_tokens' => 0,     'report_rows' => 200],
        'gemini'     => ['name' => 'Google Gemini',       'default_model' => 'gemini-2.0-flash',                      'report_mode' => 'ai',       'report_tokens' => 12000, 'report_rows' => 200],
        'openrouter' => ['name' => 'OpenRouter',          'default_model' => 'meta-llama/llama-3.1-8b-instruct:free', 'report_mode' => 'template', 'report_tokens' => 0,     'report_rows' => 200],
    ];

    // Keyword → tables mapping for smart schema selection
    private const TABLE_KEYWORDS = [
        'orders'              => ['order', 'revenue', 'sale', 'purchase', 'paid', 'payment', 'daily', 'weekly', 'monthly', 'hourly', 'hour', 'spend', 'amount', 'total', 'value', 'checkout', 'cumulative', 'growth', 'completion rate', 'refunded', 'failed'],
        'order_products'      => ['product', 'item', 'ordered', 'sold', 'quantity', 'line'],
        'order_transactions'  => ['transaction', 'payment method', 'gateway', 'mpesa', 'card'],
        'order_status'        => ['status', 'pending', 'delivered', 'cancelled', 'processing'],
        'order_status_histories' => ['history', 'status change', 'tracking'],
        'products'            => ['product', 'item', 'catalog', 'sku', 'price', 'stock', 'inventory'],
        'variations'          => ['variation', 'variant', 'size', 'color', 'option'],
        'product_categories'  => ['category', 'product'],
        'categories'          => ['category'],
        'users'               => ['user', 'customer', 'buyer', 'account', 'member', 'top customer', 'who'],
        'stores'              => ['store', 'vendor', 'seller', 'merchant', 'shop'],
        'reviews'             => ['review', 'rating', 'feedback', 'star'],
        'coupons'             => ['coupon', 'discount', 'promo', 'code', 'voucher'],
        'coupon_histories'    => ['coupon', 'discount used', 'promo used'],
        'wallets'             => ['wallet', 'balance', 'credit'],
        'wallet_histories'    => ['wallet', 'credit transaction', 'top up'],
        'commission_histories'=> ['commission', 'vendor earning', 'payout'],
        'refunds'             => ['refund', 'return', 'chargeback'],
        'cart_abandonments'   => ['cart', 'abandon', 'drop off', 'abandoned'],
        'user_sessions'       => ['session', 'visit', 'device', 'browser', 'behaviour', 'behavior'],
        'page_views'          => ['page view', 'page views', 'visit', 'traffic', 'popular page', 'most views'],
        'user_events'         => ['event', 'click', 'behaviour', 'behavior'],
        'brands'              => ['brand', 'manufacturer'],
        'tags'                => ['tag'],
        'withdraw_requests'   => ['withdraw', 'payout request'],
        'points'              => ['point', 'loyalty', 'reward'],
        'point_histories'     => ['point', 'loyalty', 'reward'],
        'taxes'               => ['tax', 'vat'],
        'shipping_classes'    => ['shipping', 'delivery class'],
        'addresses'           => ['address', 'location', 'city', 'region', 'ship'],
    ];

    private string $provider;
    private string $apiKey;
    private string $model;

    private array $allowedTables;

    public function __construct()
    {
        $this->provider     = strtolower(env('AI_PROVIDER', 'gemini'));
        $cfg                = config("services.ai_providers.{$this->provider}", []);
        $this->apiKey       = $cfg['key'] ?? '';
        $this->model        = $cfg['model'] ?? self::PROVIDERS[$this->provider]['default_model'] ?? '';
        $this->allowedTables = array_keys(self::TABLE_KEYWORDS);
    }

    // ─── Public helpers ───────────────────────────────────────────────────────

    public function getProviderName(): string
    {
        return self::PROVIDERS[$this->provider]['name'] ?? ucfirst($this->provider);
    }

    public function getActiveProvider(): string
    {
        return $this->provider;
    }

    public static function allProviders(): array
    {
        return self::PROVIDERS;
    }

    // ─── Public entry point ───────────────────────────────────────────────────

    public function answer(string $question): array
    {
        $schema = $this->introspectSchema($question);

        $sqlResult = $this->generateSQL($question, $schema);
        if (!$sqlResult['success']) return $sqlResult;

        $queryResult = $this->executeSQL($sqlResult['sql']);
        if (!$queryResult['success']) return $queryResult;

        $cfg        = self::PROVIDERS[$this->provider] ?? [];
        $reportMode = $cfg['report_mode'] ?? 'template';
        $maxRows    = $cfg['report_rows']  ?? 200;

        $html = $reportMode === 'ai'
            ? $this->generateReportAI($question, $sqlResult['sql'], $sqlResult['explanation'], $queryResult['data'], $maxRows, $cfg['report_tokens'] ?? 6000)
            : $this->generateReportTemplate($question, $sqlResult['explanation'], $queryResult['data'], $maxRows);

        return [
            'success'     => true,
            'html'        => $html,
            'sql'         => $sqlResult['sql'],
            'explanation' => $sqlResult['explanation'],
            'row_count'   => count($queryResult['data']),
            'provider'    => $this->getProviderName(),
        ];
    }

    // ─── Phase 1: SQL generation ──────────────────────────────────────────────

    private function generateSQL(string $question, string $schema): array
    {
        $system = <<<SYSTEM
You are a PostgreSQL analyst. Output ONLY a raw JSON object. No markdown, no code fences, no explanation outside the JSON.

SCHEMA:
{$schema}

OUTPUT FORMAT — output this exact structure, nothing else:
{"sql":"SELECT ...","explanation":"one sentence"}

POSTGRESQL RULES:
- SELECT only. No INSERT/UPDATE/DELETE/DROP/TRUNCATE/ALTER/CREATE.
- Use table aliases. End with LIMIT 500.
- orders.consumer_id = users.id  (join key for customers)
- payment_status values: 'pending','completed','failed','refunded'
- Order status name: JOIN order_status os ON os.id = o.order_status_id
- Date ranges: created_at >= NOW() - INTERVAL '14 days'  |  date_trunc('month', NOW()) for current month
- Daily grouping: DATE_TRUNC('day', created_at) AS day
- Monthly grouping: DATE_TRUNC('month', created_at) AS month
- Hourly grouping: DATE_TRUNC('hour', created_at) AS hour
- Day of week: TO_CHAR(created_at, 'Day') AS day_name
- Revenue column in orders: grand_total
- Completed/paid orders filter: payment_status = 'completed'
- Abandoned carts: use cart_abandonments table
- New customers this month: users.created_at >= date_trunc('month', NOW())
- Repeat customers: COUNT(DISTINCT order) > 1 grouped by consumer_id
- If unanswerable: {"sql":"","explanation":"Cannot be answered from available data."}
SYSTEM;

        $userMsg = "Question: {$question}";

        $response = $this->callAI($system, $userMsg, 600);
        if (!$response['success']) return $response;

        Log::debug('AIAnalytics SQL response', ['provider' => $this->provider, 'raw' => $response['content']]);

        $content = $this->extractJSON($response['content']);
        $decoded = json_decode($content, true);

        if (!$decoded || empty($decoded['sql'])) {
            Log::warning('AIAnalytics: parse failed', ['raw' => $response['content'], 'extracted' => $content]);
            return ['success' => false, 'error' => 'Could not generate a query for that question. Please try rephrasing.'];
        }

        $clean = strtoupper(trim(preg_replace('/\s+/', ' ', $decoded['sql'])));
        foreach (['INSERT','UPDATE','DELETE','DROP','TRUNCATE','ALTER','EXEC','EXECUTE','CREATE','GRANT','REVOKE'] as $kw) {
            if (preg_match('/\b' . $kw . '\b/', $clean)) {
                return ['success' => false, 'error' => "Query blocked: contains forbidden keyword '{$kw}'."];
            }
        }
        if (!str_starts_with($clean, 'SELECT')) {
            return ['success' => false, 'error' => 'Only SELECT queries are permitted.'];
        }

        return ['success' => true, 'sql' => $decoded['sql'], 'explanation' => $decoded['explanation'] ?? ''];
    }

    // ─── Execute SQL ──────────────────────────────────────────────────────────

    private function executeSQL(string $sql): array
    {
        try {
            DB::statement('SET statement_timeout = 15000');
            $rows = DB::select($sql);
            $data = array_slice(array_map(fn($r) => (array) $r, $rows), 0, 500);
            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('AIAnalytics SQL error', ['sql' => $sql, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Query failed: ' . $e->getMessage()];
        }
    }

    // ─── Phase 2a: AI-generated HTML report (Anthropic / Gemini) ─────────────

    private function generateReportAI(string $question, string $sql, string $explanation, array $data, int $maxRows, int $maxTokens): string
    {
        if (empty($data)) return $this->emptyReportHtml($question, $explanation);

        $dataJson = json_encode(array_slice($data, 0, $maxRows), JSON_PRETTY_PRINT);
        $rowCount = count($data);
        $shown    = min($rowCount, $maxRows);
        $note     = $rowCount > $maxRows ? " ({$shown} of {$rowCount} rows shown)" : "{$rowCount} rows";

        $system = <<<'SYSTEM'
You are a senior business intelligence engineer. Generate a stunning, production-quality HTML analytics report for an e-commerce dashboard.

═══ OUTPUT RULES ═══
- Return ONLY raw HTML — no markdown, no code fences, no text before or after the HTML.
- No <html>, <head>, or <body> tags.
- All CSS must be inline or in a <style> block at the top.
- Include Chart.js 4 via: <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
- All chart data MUST come from the provided JSON — never fabricate numbers.

═══ REPORT STRUCTURE ═══
Build exactly these sections in order:

1. HEADER BANNER
   - Full-width gradient banner (indigo→purple: #6366f1→#8b5cf6)
   - Large question text in white, bold
   - Sub-line with explanation text, row count, and date generated
   - Subtle decorative background pattern (CSS radial-gradient dots or diagonal lines)

2. KPI SUMMARY CARDS (row of 4 cards)
   - Calculate and show: Total/Sum, Average, Highest value, Lowest or Count
   - Each card: icon (emoji), metric label, large formatted number, subtle trend or context line
   - Card colours: indigo, emerald (#10b981), amber (#f59e0b), rose (#f43f5e)
   - Rounded corners (16px), shadow, hover lift effect

3. PRIMARY CHART
   - Choose the best chart type automatically:
     * Time-series / dates → smooth Line chart with fill gradient
     * Rankings / comparisons (≤20 items) → horizontal Bar chart
     * Distributions / categories (≤8 slices) → Doughnut chart with legend
     * Large rankings → vertical Bar chart
   - Chart height: 320px. Clean design: no border, light gridlines (#f1f5f9), custom tooltip
   - Use colour palette: ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#f43f5e','#ec4899','#84cc16']

4. SECONDARY CHART (only if data has 2+ numeric columns OR >1 meaningful breakdown)
   - A complementary view — e.g., if primary is line trend, secondary could be bar of top 5 items
   - Skip this section entirely if only one numeric dimension exists

5. INSIGHTS BOX
   - White card with left indigo border (4px solid #6366f1)
   - Title: "📊 Key Insights"
   - 3–4 bullet points derived from the actual data:
     * Largest value and what it represents
     * Notable trend, spike, or pattern
     * Comparison (e.g., top vs bottom, first vs last period)
     * Actionable observation

6. DATA TABLE
   - Full responsive table with all columns
   - Sticky header row: dark background (#1e293b), white text
   - Alternating row colours (#fff / #f8fafc), hover highlight (#ede9fe)
   - Right-align numeric columns; left-align text
   - Format numbers: thousand separators; currency values prefixed with "$ "
   - Format dates: DD MMM YYYY
   - Add a "Showing X of Y rows" footer if truncated

═══ DESIGN SYSTEM ═══
- Font: system-ui, -apple-system, sans-serif
- Primary: #6366f1  Success: #10b981  Warning: #f59e0b  Danger: #f43f5e
- Background: #f8fafc  Card bg: #ffffff  Border: #e2e8f0
- Border-radius on cards: 16px; on table: 12px
- Box-shadow: 0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -2px rgba(0,0,0,.05)
- Smooth transitions (0.2s ease) on hover states
- Max-width: 1200px, centred, padding: 24px

═══ NUMBER FORMATTING ═══
- Integers ≥ 1000: use thousand separators (e.g., 12,450)
- Decimals: 2 places (e.g., R 1,234.56)
- Percentages: 1 decimal place + % symbol
- Currency: always prefix with "$ "
SYSTEM;

        $msg = "Question: \"{$question}\"\nExplanation: {$explanation}\nData: {$note}\n\nJSON DATA:\n{$dataJson}\n\nGenerate the complete HTML report now. Return only HTML.";

        $response = $this->callAI($system, $msg, $maxTokens);
        if (!$response['success']) return $this->errorReportHtml($response['error']);

        $html = $this->stripFences($response['content']);
        if (str_starts_with(trim($html), '{')) {
            $decoded = json_decode($html, true);
            $html    = $decoded['html'] ?? $decoded['report'] ?? $html;
        }
        return $html;
    }

    // ─── Phase 2b: Template-based report (Groq / OpenRouter — no second AI call) ─

    private function generateReportTemplate(string $question, string $explanation, array $data, int $maxRows): string
    {
        if (empty($data)) return $this->emptyReportHtml($question, $explanation);

        $rows    = array_slice($data, 0, $maxRows);
        $columns = array_keys($rows[0]);
        $total   = count($data);
        $shown   = count($rows);
        $date    = now()->format('d M Y, H:i');

        // Detect numeric columns
        $numericCols = array_filter($columns, fn($c) => is_numeric($rows[0][$c] ?? null));
        $labelCol    = $columns[0];
        $primaryVal  = $numericCols ? array_values($numericCols)[0] : null;

        // KPI cards
        $kpiHtml = '';
        if ($primaryVal) {
            $vals    = array_column($rows, $primaryVal);
            $sum     = array_sum($vals);
            $avg     = $sum / max(count($vals), 1);
            $max     = max($vals);
            $min     = min($vals);
            $valLabel = ucwords(str_replace('_', ' ', $primaryVal));
            $kpiHtml = $this->buildKpiCards($total, $sum, $avg, $max, $valLabel);
        }

        // Charts
        $chartHtml = $this->buildTemplateChart($rows, $columns, $question);

        // Insights
        $insightsHtml = $primaryVal ? $this->buildInsights($rows, $labelCol, $primaryVal, $question) : '';

        // Table
        $th = implode('', array_map(fn($c) =>
            "<th style='padding:12px 16px;text-align:" . (in_array($c, $numericCols) ? 'right' : 'left') . ";font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap'>"
            . htmlspecialchars(str_replace('_', ' ', $c)) . "</th>", $columns));

        $tbody = '';
        foreach ($rows as $i => $row) {
            $bg  = $i % 2 === 0 ? '#ffffff' : '#f8fafc';
            $tds = '';
            foreach ($columns as $col) {
                $v       = $row[$col] ?? '';
                $isNum   = in_array($col, $numericCols);
                $display = $isNum
                    ? (str_contains(strtolower($col), 'amount') || str_contains(strtolower($col), 'total') || str_contains(strtolower($col), 'revenue') || str_contains(strtolower($col), 'price') || str_contains(strtolower($col), 'value')
                        ? '$' . number_format((float)$v, 2)
                        : number_format((float)$v, is_float($v + 0) ? 2 : 0))
                    : htmlspecialchars((string)$v);
                $align   = $isNum ? 'right' : 'left';
                $tds    .= "<td style='padding:11px 16px;font-size:13px;color:#374151;border-bottom:1px solid #f1f5f9;text-align:{$align}'>{$display}</td>";
            }
            $tbody .= "<tr style='background:{$bg};transition:background .15s' onmouseover=\"this.style.background='#ede9fe'\" onmouseout=\"this.style.background='{$bg}'\">{$tds}</tr>";
        }

        $noteHtml = $shown < $total
            ? "<div style='padding:10px 16px;font-size:12px;color:#94a3b8;text-align:right'>Showing {$shown} of {$total} rows</div>"
            : "<div style='padding:10px 16px;font-size:12px;color:#94a3b8;text-align:right'>{$total} rows</div>";

        $qSafe   = htmlspecialchars($question);
        $expSafe = htmlspecialchars($explanation);

        return <<<HTML
<style>
  .ra-report *{box-sizing:border-box}
  .ra-report{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;padding:0;max-width:1200px;margin:0 auto}
  .ra-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;padding:24px 24px 0}
  .ra-card{background:#fff;border-radius:16px;padding:20px 24px;box-shadow:0 4px 6px -1px rgba(0,0,0,.07),0 2px 4px -2px rgba(0,0,0,.05);transition:transform .2s,box-shadow .2s}
  .ra-card:hover{transform:translateY(-2px);box-shadow:0 10px 15px -3px rgba(0,0,0,.1)}
  .ra-chart-wrap{background:#fff;border-radius:16px;margin:16px 24px 0;padding:24px;box-shadow:0 4px 6px -1px rgba(0,0,0,.07)}
  .ra-insights{background:#fff;border-radius:16px;margin:16px 24px 0;padding:20px 24px;box-shadow:0 4px 6px -1px rgba(0,0,0,.07);border-left:4px solid #6366f1}
  .ra-table-wrap{background:#fff;border-radius:16px;margin:16px 24px 24px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,.07)}
  .ra-table{width:100%;border-collapse:collapse}
  .ra-table thead tr{background:#1e293b}
</style>
<div class="ra-report">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:28px 24px;border-radius:0 0 0 0;position:relative;overflow:hidden">
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 50%,rgba(255,255,255,.08) 0%,transparent 60%)"></div>
    <div style="position:relative">
      <div style="font-size:11px;font-weight:600;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">Analytics Report</div>
      <h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#fff;line-height:1.3">{$qSafe}</h2>
      <p style="margin:0;font-size:13px;color:rgba(255,255,255,.75)">{$expSafe}</p>
      <div style="margin-top:12px;font-size:11px;color:rgba(255,255,255,.5)">Generated {$date} &nbsp;·&nbsp; {$total} records</div>
    </div>
  </div>

  <!-- KPIs -->
  {$kpiHtml}

  <!-- Chart -->
  {$chartHtml}

  <!-- Insights -->
  {$insightsHtml}

  <!-- Table -->
  <div class="ra-table-wrap">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9">
      <span style="font-size:14px;font-weight:600;color:#1e293b">Full Data</span>
    </div>
    <div style="overflow-x:auto">
      <table class="ra-table">
        <thead><tr>{$th}</tr></thead>
        <tbody>{$tbody}</tbody>
      </table>
      {$noteHtml}
    </div>
  </div>
</div>
HTML;
    }

    private function buildKpiCards(int $count, float $sum, float $avg, float $max, string $valLabel): string
    {
        $cards = [
            ['#6366f1', '📦', 'Total Records',            number_format($count),              ''],
            ['#10b981', '💰', "Total {$valLabel}",        '$' . number_format($sum, 2), ''],
            ['#f59e0b', '📊', "Avg {$valLabel}",          '$' . number_format($avg, 2), ''],
            ['#f43f5e', '🏆', "Highest {$valLabel}",      '$' . number_format($max, 2), ''],
        ];
        $html = '<div class="ra-kpi-grid">';
        foreach ($cards as [$color, $icon, $label, $value, $sub]) {
            $html .= <<<CARD
<div class="ra-card">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
    <div style="width:36px;height:36px;border-radius:10px;background:{$color}1a;display:flex;align-items:center;justify-content:center;font-size:18px">{$icon}</div>
    <span style="font-size:12px;color:#64748b;font-weight:500">{$label}</span>
  </div>
  <div style="font-size:24px;font-weight:700;color:#0f172a">{$value}</div>
</div>
CARD;
        }
        return $html . '</div>';
    }

    private function buildInsights(array $rows, string $labelCol, string $valueCol, string $question): string
    {
        $vals      = array_column($rows, $valueCol);
        $labels    = array_column($rows, $labelCol);
        $maxIdx    = array_search(max($vals), $vals);
        $minIdx    = array_search(min($vals), $vals);
        $totalSum  = array_sum($vals);
        $avg       = $totalSum / max(count($vals), 1);
        $count     = count($rows);
        $top       = htmlspecialchars((string)($labels[$maxIdx] ?? ''));
        $topVal    = number_format((float)($vals[$maxIdx] ?? 0), 2);
        $bottom    = htmlspecialchars((string)($labels[$minIdx] ?? ''));
        $botVal    = number_format((float)($vals[$minIdx] ?? 0), 2);
        $pct       = $totalSum > 0 ? number_format(((float)($vals[$maxIdx] ?? 0) / $totalSum) * 100, 1) : 0;
        $avgFmt    = number_format($avg, 2);
        $totalFmt  = number_format($totalSum, 2);

        return <<<HTML
<div class="ra-insights">
  <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:12px">📊 Key Insights</div>
  <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:8px">
    <li style="font-size:13px;color:#475569"><strong style="color:#6366f1">{$top}</strong> leads with ${$topVal}, representing {$pct}% of the total.</li>
    <li style="font-size:13px;color:#475569">Average across all entries is ${$avgFmt} — values above this indicate above-average performers.</li>
    <li style="font-size:13px;color:#475569">The lowest performer is <strong>{$bottom}</strong> at ${$botVal}.</li>
    <li style="font-size:13px;color:#475569">There are <strong>{$count}</strong> records with a combined value of ${$totalFmt}.</li>
  </ul>
</div>
HTML;
    }

    private function buildTemplateChart(array $rows, array $columns, string $question): string
    {
        if (count($columns) < 2 || count($rows) < 2) return '';

        $labelCol = $columns[0];
        $valueCol = null;
        foreach (array_slice($columns, 1) as $col) {
            if (is_numeric($rows[0][$col] ?? null)) { $valueCol = $col; break; }
        }
        if (!$valueCol) return '';

        $chartRows = array_slice($rows, 0, 40);
        $labels    = json_encode(array_map(fn($r) => (string)($r[$labelCol] ?? ''), $chartRows));
        $values    = json_encode(array_map(fn($r) => (float)($r[$valueCol] ?? 0), $chartRows));
        $title     = ucwords(str_replace('_', ' ', $valueCol));

        $q    = strtolower($question);
        $isTime = str_contains($q, 'daily') || str_contains($q, 'trend') || str_contains($q, 'over time')
               || str_contains($q, 'week') || str_contains($q, 'month') || str_contains($q, 'hour');
        $isDist = count($chartRows) <= 8 && (str_contains($q, 'distribut') || str_contains($q, 'breakdown') || str_contains($q, 'by type') || str_contains($q, 'by method'));
        $isHoriz = !$isTime && !$isDist && count($chartRows) > 10;

        $type      = $isTime ? 'line' : ($isDist ? 'doughnut' : ($isHoriz ? 'bar' : 'bar'));
        $id        = 'chart_' . substr(md5(uniqid()), 0, 8);
        $palette   = json_encode(['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#f43f5e','#ec4899','#84cc16']);

        if ($isTime) {
            $dataset = "{label:'{$title}',data:{$values},borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',borderWidth:2.5,fill:true,tension:.4,pointBackgroundColor:'#6366f1',pointRadius:3}";
            $scales  = "scales:{x:{grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',font:{size:11}}},y:{grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',font:{size:11}},beginAtZero:true}}";
        } elseif ($isDist) {
            $dataset = "{data:{$values},backgroundColor:{$palette},borderWidth:0,hoverOffset:6}";
            $scales  = '';
        } else {
            $dataset = "{label:'{$title}',data:{$values},backgroundColor:'rgba(99,102,241,.8)',borderRadius:6,borderSkipped:false}";
            $scales  = "scales:{x:{grid:{display:false},ticks:{color:'#94a3b8',font:{size:11}}},y:{grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',font:{size:11}},beginAtZero:true}}";
            if ($isHoriz) { $type = 'bar'; }
        }

        $scalesStr = $scales ? ",{$scales}" : '';

        return <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<div class="ra-chart-wrap">
  <div style="font-size:14px;font-weight:600;color:#1e293b;margin-bottom:16px">{$title} Overview</div>
  <canvas id="{$id}" style="max-height:320px"></canvas>
</div>
<script>
(function(){
  new Chart(document.getElementById('{$id}'),{
    type:'{$type}',
    data:{labels:{$labels},datasets:[{$dataset}]},
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{backgroundColor:'#1e293b',padding:10,cornerRadius:8,titleFont:{size:12},bodyFont:{size:12}}}{$scalesStr}}
  });
})();
</script>
HTML;
    }

    // ─── Schema introspection (smart — only relevant tables) ─────────────────

    private function introspectSchema(string $question): string
    {
        $tables = $this->selectRelevantTables($question);

        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $columns = DB::select("
            SELECT c.table_name, c.column_name, c.data_type
            FROM information_schema.columns c
            WHERE c.table_schema = 'public'
              AND c.table_name IN ({$placeholders})
            ORDER BY c.table_name, c.ordinal_position
        ", $tables);

        $schema = [];
        foreach ($columns as $col) {
            $schema[$col->table_name][] = $col->column_name . ':' . $this->abbreviateType($col->data_type);
        }

        return implode("\n", array_map(
            fn($t, $cols) => "{$t}(" . implode(', ', $cols) . ")",
            array_keys($schema), $schema
        ));
    }

    private function selectRelevantTables(string $question): array
    {
        $q = strtolower($question);

        // Always include core tables
        $tables = ['orders', 'users'];

        foreach (self::TABLE_KEYWORDS as $table => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($q, $keyword)) {
                    $tables[] = $table;
                    break;
                }
            }
        }

        return array_values(array_unique($tables));
    }

    private function abbreviateType(string $type): string
    {
        return match (true) {
            str_contains($type, 'bigint')                    => 'bigint',
            str_contains($type, 'integer')                   => 'int',
            str_contains($type, 'numeric') || str_contains($type, 'decimal') => 'decimal',
            str_contains($type, 'character varying') || str_contains($type, 'varchar') => 'varchar',
            str_contains($type, 'timestamp')                 => 'timestamp',
            $type === 'boolean'                              => 'bool',
            $type === 'text'                                 => 'text',
            str_contains($type, 'json')                      => 'json',
            $type === 'date'                                 => 'date',
            default                                          => $type,
        };
    }

    // ─── Provider dispatcher ──────────────────────────────────────────────────

    private function callAI(string $system, string $userMessage, int $maxTokens): array
    {
        if (empty($this->apiKey)) {
            $envKey = strtoupper($this->provider) . '_API_KEY';
            return ['success' => false, 'error' => "No API key set. Add {$envKey} to your .env file."];
        }

        return match ($this->provider) {
            'anthropic'  => $this->callAnthropic($system, $userMessage, $maxTokens),
            'groq'       => $this->callOpenAICompatible('https://api.groq.com/openai/v1/chat/completions', $system, $userMessage, $maxTokens),
            'openrouter' => $this->callOpenAICompatible('https://openrouter.ai/api/v1/chat/completions', $system, $userMessage, $maxTokens),
            'gemini'     => $this->callGemini($system, $userMessage, $maxTokens),
            default      => ['success' => false, 'error' => "Unknown provider '{$this->provider}'. Supported: anthropic, groq, gemini, openrouter."],
        };
    }

    // ─── Anthropic ────────────────────────────────────────────────────────────

    private function callAnthropic(string $system, string $userMessage, int $maxTokens): array
    {
        try {
            $res = Http::timeout(60)
                ->withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $this->model,
                    'max_tokens' => $maxTokens,
                    'system'     => $system,
                    'messages'   => [['role' => 'user', 'content' => $userMessage]],
                ]);

            if (!$res->successful()) {
                return ['success' => false, 'error' => 'Anthropic error: ' . ($res->json('error.message') ?? $res->body())];
            }
            return ['success' => true, 'content' => $res->json('content.0.text', '')];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Anthropic connection error: ' . $e->getMessage()];
        }
    }

    // ─── OpenAI-compatible (Groq + OpenRouter) ────────────────────────────────

    private function callOpenAICompatible(string $url, string $system, string $userMessage, int $maxTokens): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ];
            if (str_contains($url, 'openrouter')) {
                $headers['HTTP-Referer'] = config('app.url', 'https://raines.africa');
                $headers['X-Title']      = 'Raines Africa AI Analytics';
            }

            $res = Http::timeout(60)
                ->withHeaders($headers)
                ->post($url, [
                    'model'      => $this->model,
                    'max_tokens' => $maxTokens,
                    'messages'   => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                ]);

            if (!$res->successful()) {
                return ['success' => false, 'error' => ucfirst($this->provider) . ' error: ' . ($res->json('error.message') ?? $res->body())];
            }
            return ['success' => true, 'content' => $res->json('choices.0.message.content', '')];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => ucfirst($this->provider) . ' connection error: ' . $e->getMessage()];
        }
    }

    // ─── Google Gemini ────────────────────────────────────────────────────────

    private function callGemini(string $system, string $userMessage, int $maxTokens): array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $res = Http::timeout(60)
                ->post($url, [
                    'system_instruction' => ['parts' => [['text' => $system]]],
                    'contents'           => [['role' => 'user', 'parts' => [['text' => $userMessage]]]],
                    'generationConfig'   => ['maxOutputTokens' => $maxTokens],
                ]);

            if (!$res->successful()) {
                return ['success' => false, 'error' => 'Gemini error: ' . ($res->json('error.message') ?? $res->body())];
            }
            return ['success' => true, 'content' => $res->json('candidates.0.content.parts.0.text', '')];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Gemini connection error: ' . $e->getMessage()];
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function stripFences(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json|html)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);
        return trim($content);
    }

    private function extractJSON(string $content): string
    {
        $content = $this->stripFences($content);

        // Fix Llama multi-line string split: "key":\n  "value" and "part1"\n  "part2"
        $content = preg_replace('/":\s*\n\s*"/', '": "', $content);
        $content = preg_replace('/"\s*\n\s*"/', ' ', $content);

        $content = trim($content);
        if (str_starts_with($content, '{')) return $content;

        $start = strpos($content, '{');
        if ($start === false) return $content;

        $depth = 0; $inString = false; $escape = false; $len = strlen($content);
        for ($i = $start; $i < $len; $i++) {
            $ch = $content[$i];
            if ($escape)                         { $escape = false; continue; }
            if ($ch === '\\' && $inString)       { $escape = true;  continue; }
            if ($ch === '"')                     { $inString = !$inString; continue; }
            if ($inString)                        continue;
            if ($ch === '{')                     $depth++;
            elseif ($ch === '}' && --$depth === 0) return substr($content, $start, $i - $start + 1);
        }

        return $content;
    }

    private function emptyReportHtml(string $question, string $explanation): string
    {
        return "<div style='text-align:center;padding:60px 20px;color:#666'><div style='font-size:48px;margin-bottom:16px'>📭</div><h3>No data found</h3><p>Question: <em>\"{$question}\"</em></p><p style='color:#999'>{$explanation}</p></div>";
    }

    private function errorReportHtml(string $error): string
    {
        $safe = htmlspecialchars($error);
        return "<div style='text-align:center;padding:60px 20px;color:#dc3545'><div style='font-size:48px;margin-bottom:16px'>⚠️</div><h3>Something went wrong</h3><p style='color:#666'>{$safe}</p></div>";
    }
}
