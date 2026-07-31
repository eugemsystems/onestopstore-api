<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AISearchService
{
    private string $provider;
    private string $apiKey;
    private string $model;

    // Fallback models only used when no model is configured in .env
    private const FALLBACK_MODELS = [
        'anthropic'  => 'claude-haiku-4-5',
        'groq'       => 'llama-3.3-70b-versatile',
        'gemini'     => 'gemini-2.0-flash',
        'openrouter' => 'meta-llama/llama-3.1-8b-instruct:free',
    ];

    public function __construct()
    {
        $this->provider = strtolower(env('AI_PROVIDER', 'gemini'));
        $cfg            = config("services.ai_providers.{$this->provider}", []);
        $this->apiKey   = $cfg['key'] ?? '';
        // Use the model configured in .env (e.g. gemini-2.5-flash) — better accuracy for African languages
        $this->model    = $cfg['model'] ?? self::FALLBACK_MODELS[$this->provider] ?? '';
    }

    // ─── Public: expand a natural-language query into search keywords ─────────

    public function expand(string $rawQuery): array
    {
        $key = 'ai_search_expand_' . md5(strtolower(trim($rawQuery)));

        return Cache::remember($key, now()->addHours(48), function () use ($rawQuery) {
            return $this->callExpand($rawQuery);
        });
    }

    // ─── AI call ──────────────────────────────────────────────────────────────

    private function callExpand(string $query): array
    {
        if (empty($this->apiKey)) {
            Log::warning('AISearch: no API key for provider', ['provider' => $this->provider]);
            return $this->tryFallbackProvider($query);
        }

        $prompt = <<<PROMPT
You are a highly accurate multilingual product search assistant for an African e-commerce marketplace. Your job is to translate any search query into English and expand it into product keywords.

SUPPORTED LANGUAGES (with examples):
- Shona (Zimbabwe): "chikafu" = food, "mvura" = water, "hembe" = shirt/clothes, "bhutsu" = shoes, "mota" = car, "imba" = house, "imbwa" = dog, "katsi" = cat, "mwana" = child, "mukadzi" = woman, "murume" = man, "kurapa" = to heal/medicine, "zvinhu" = things/items, "kutsvaga" = to look for/search, "ndiri" = I am, "ndinoda" = I want/need, "kuwana" = to find/get
- Ndebele (Zimbabwe): "ukudla" = food, "amanzi" = water, "inja" = dog, "ikati" = cat, "izingubo" = clothes, "izicathulo" = shoes, "imoto" = car, "indlu" = house, "ngilindele" = I need, "ngifuna" = I want
- Zulu (South Africa): "ukudla" = food, "amanzi" = water, "inja" = dog, "impahla" = clothes, "izicathulo" = shoes, "imoto" = car, "indlu" = house, "ngifuna" = I want, "ngidinga" = I need, "ukuthenga" = to buy
- Xhosa (South Africa): "ukutya" = food, "amanzi" = water, "inja" = dog, "iimpahla" = clothes, "amaxabiso" = prices, "ndifuna" = I want, "ndikhangela" = I am looking for
- Sotho/Sesotho (South Africa/Lesotho): "dijo" = food, "ntja" = dog, "diaparo" = clothes, "seeta" = shoe, "motokara" = car, "ke batla" = I want, "ke batlisisa" = I am looking for
- Bemba (Zambia): "ichikulya" = food, "ifyakuswala" = clothes, "imbwa" = dog, "nkumbuyapo" = I need/looking for, "ifimba" = things
- Nyanja/Chewa (Zambia/Malawi): "chakudya" = food, "nyama" = meat, "malaya" = shirt, "nsapato" = shoes, "galu" = dog, "ndikufuna" = I want, "ndikufunira" = I am looking for
- Swahili (East Africa): "chakula" = food, "mbwa" = dog, "nguo" = clothes, "viatu" = shoes, "gari" = car, "nyumba" = house, "natafuta" = I am looking for, "ninataka" = I want
- Afrikaans (South Africa): "kos" = food, "hond" = dog, "klere" = clothes, "skoene" = shoes, "motor" = car, "huis" = house, "ek soek" = I am looking for, "ek wil hê" = I want

TASK: Translate and expand this search query for product search: "{$query}"

Respond with ONLY a valid JSON object — no markdown, no explanation, nothing else.

JSON structure:
{
  "language": "<detected language>",
  "english_query": "<RULES: (1) Non-English input → translate to a concise English product name. (2) English description ('I need X', 'looking for Y', 'what is a good Z') → condense to the core product keywords, e.g. 'cat tumbler'. (3) English product name/model ('Cat In The Winter 20 Oz Straight Skinny Tumbler 226', 'Samsung S24 Ultra 256GB') → return it EXACTLY as written, preserving all numbers, codes, and identifiers.>",
  "intent": "<one English sentence about what they want to buy>",
  "keywords": ["<8-10 specific English product search terms — for product-name queries, include the original as the first keyword>"],
  "categories": ["<2-3 product category names>"],
  "is_translated": <true if not English, false if English>
}

Examples:
- "ndiri kutsvaga chikafu cheimbwa" → {"language":"Shona","english_query":"dog food","intent":"Looking for food for their dog","keywords":["dog food","dry dog food","wet dog food","puppy food","dog treats","pet food","dog kibble","dog biscuits","dog nutrition","puppy chow"],"categories":["Pet Supplies","Pet Food"],"is_translated":true}
- "ngifuna izicathulo zegalofu" → {"language":"Zulu","english_query":"golf shoes","intent":"Looking for golf shoes","keywords":["golf shoes","golf cleats","golf footwear","spiked golf shoes","waterproof golf shoes","men golf shoes","golf accessories"],"categories":["Sports","Footwear","Golf"],"is_translated":true}
- "ke batla diaparo tsa bana" → {"language":"Sotho","english_query":"children's clothing","intent":"Looking for clothes for children","keywords":["kids clothes","children clothing","baby clothes","toddler outfit","school uniform","kids fashion","children wear","boys clothes","girls clothes"],"categories":["Kids","Clothing","Fashion"],"is_translated":true}
- "Cat In The Winter 20 Oz Straight Skinny Tumbler 226" → {"language":"English","english_query":"Cat In The Winter 20 Oz Straight Skinny Tumbler 226","intent":"Looking for a specific cat-themed 20oz skinny tumbler variant 226","keywords":["Cat In The Winter 20 Oz Straight Skinny Tumbler 226","cat skinny tumbler 20oz","cat winter tumbler","20 oz straight tumbler","cat design tumbler","skinny tumbler 226","cat themed drinkware","insulated tumbler cat","cat tumbler cup"],"categories":["Drinkware","Kitchen","Tumblers"],"is_translated":false}
- "laptop repair tools" → {"language":"English","english_query":"laptop repair tools","intent":"Looking for tools to repair a laptop","keywords":["laptop repair tools","laptop repair kit","laptop screwdriver set","thermal paste","laptop screen replacement","keyboard replacement","laptop battery","soldering iron","anti-static mat","laptop opening tools"],"categories":["Electronics","Tools","Computer Parts"],"is_translated":false}

Now output the JSON for: "{$query}"
PROMPT;

        $response = $this->callAI('', $prompt, 500);

        if (!$response['success']) {
            Log::warning('AISearch: expansion failed', ['provider' => $this->provider, 'error' => $response['error'], 'query' => $query]);
            return $this->tryFallbackProvider($query);
        }

        $content = $this->extractJSON($response['content']);
        $decoded = json_decode($content, true);

        if (!$decoded || empty($decoded['keywords'])) {
            Log::warning('AISearch: parse failed', ['provider' => $this->provider, 'raw' => $response['content'], 'query' => $query]);
            return $this->tryFallbackProvider($query);
        }

        return [
            'language'     => $decoded['language']     ?? 'Unknown',
            'english_query'=> $decoded['english_query'] ?? $query,
            'intent'       => $decoded['intent']        ?? '',
            'keywords'     => array_slice($decoded['keywords'] ?? [$query], 0, 12),
            'categories'   => $decoded['categories']   ?? [],
            'is_translated'=> (bool)($decoded['is_translated'] ?? false),
            'original'     => $query,
        ];
    }

    // Try Groq as fallback when the primary provider fails
    private function tryFallbackProvider(string $query): array
    {
        if ($this->provider === 'groq') {
            return $this->fallback($query);
        }

        $groqKey = config('services.ai_providers.groq.key', '');
        if (empty($groqKey)) {
            return $this->fallback($query);
        }

        Log::info('AISearch: falling back to Groq', ['query' => $query]);

        $savedProvider = $this->provider;
        $savedKey      = $this->apiKey;
        $savedModel    = $this->model;

        $this->provider = 'groq';
        $this->apiKey   = $groqKey;
        $this->model    = self::FALLBACK_MODELS['groq'];

        $result = $this->callExpand($query);

        $this->provider = $savedProvider;
        $this->apiKey   = $savedKey;
        $this->model    = $savedModel;

        return $result;
    }

    private function fallback(string $query): array
    {
        return [
            'language'     => 'Unknown',
            'english_query'=> $query,
            'intent'       => '',
            'keywords'     => [$query],
            'categories'   => [],
            'is_translated'=> false,
            'original'     => $query,
        ];
    }

    // ─── Provider calls ───────────────────────────────────────────────────────

    private function callAI(string $system, string $userMessage, int $maxTokens): array
    {
        return match ($this->provider) {
            'anthropic'  => $this->callAnthropic($system, $userMessage, $maxTokens),
            'groq'       => $this->callOpenAI('https://api.groq.com/openai/v1/chat/completions', $system, $userMessage, $maxTokens),
            'openrouter' => $this->callOpenAI('https://openrouter.ai/api/v1/chat/completions', $system, $userMessage, $maxTokens),
            'gemini'     => $this->callGemini($system, $userMessage, $maxTokens),
            default      => ['success' => false, 'error' => 'Unknown provider'],
        };
    }

    private function callAnthropic(string $system, string $msg, int $max): array
    {
        try {
            $r = Http::timeout(15)->withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
                ->post('https://api.anthropic.com/v1/messages', ['model' => $this->model, 'max_tokens' => $max, 'system' => $system, 'messages' => [['role' => 'user', 'content' => $msg]]]);
            return $r->successful() ? ['success' => true, 'content' => $r->json('content.0.text', '')] : ['success' => false, 'error' => $r->json('error.message', $r->body())];
        } catch (\Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
    }

    private function callOpenAI(string $url, string $system, string $msg, int $max): array
    {
        try {
            $headers = ['Authorization' => 'Bearer ' . $this->apiKey, 'Content-Type' => 'application/json'];
            if (str_contains($url, 'openrouter')) { $headers['HTTP-Referer'] = config('app.url'); $headers['X-Title'] = 'Raines Africa Search'; }
            $r = Http::timeout(15)->withHeaders($headers)->post($url, ['model' => $this->model, 'max_tokens' => $max, 'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $msg]]]);
            return $r->successful() ? ['success' => true, 'content' => $r->json('choices.0.message.content', '')] : ['success' => false, 'error' => $r->json('error.message', $r->body())];
        } catch (\Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
    }

    private function callGemini(string $system, string $msg, int $max): array
    {
        try {
            $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
            $body = [
                'contents'         => [['role' => 'user', 'parts' => [['text' => $msg]]]],
                'generationConfig' => ['maxOutputTokens' => $max, 'temperature' => 0],
            ];
            if (!empty($system)) {
                $body['system_instruction'] = ['parts' => [['text' => $system]]];
            }
            $r = Http::timeout(15)->post($url, $body);
            return $r->successful() ? ['success' => true, 'content' => $r->json('candidates.0.content.parts.0.text', '')] : ['success' => false, 'error' => $r->json('error.message', $r->body())];
        } catch (\Throwable $e) { return ['success' => false, 'error' => $e->getMessage()]; }
    }

    private function extractJSON(string $content): string
    {
        $content = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/\s*```$/i', '', trim($content))));
        if (str_starts_with($content, '{')) return $content;
        $start = strpos($content, '{');
        if ($start === false) return $content;
        $depth = 0; $inStr = false; $esc = false; $len = strlen($content);
        for ($i = $start; $i < $len; $i++) {
            $ch = $content[$i];
            if ($esc)                       { $esc = false; continue; }
            if ($ch === '\\' && $inStr)     { $esc = true; continue; }
            if ($ch === '"')                { $inStr = !$inStr; continue; }
            if ($inStr)                      continue;
            if ($ch === '{')               $depth++;
            elseif ($ch === '}' && --$depth === 0) return substr($content, $start, $i - $start + 1);
        }
        return $content;
    }
}
