<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BotDetectionService
{
    /**
     * Common bot user agent patterns
     */
    protected array $botPatterns = [
        // Search Engine Bots
        'googlebot',
        'google-inspectiontool',
        'bingbot',
        'slurp',        // Yahoo
        'duckduckbot',
        'baiduspider',
        'yandexbot',
        'sogou',
        'exabot',
        'facebot',
        'ia_archiver',

        // Social Media Crawlers
        'facebookexternalhit',
        'twitterbot',
        'whatsapp',
        'telegrambot',
        'slackbot',
        'discordbot',
        'linkedinbot',
        'pinterestbot',
        'instagram',

        // SEO & Monitoring Tools
        'ahrefsbot',
        'semrushbot',
        'dotbot',
        'rogerbot',
        'mj12bot',
        'screaming frog',
        'sitebulb',
        'serpstatbot',
        'seobilitybot',
        'linkchecker',

        // Monitoring & Uptime
        'pingdom',
        'uptimerobot',
        'statuscake',
        'newrelic',
        'datadog',
        'site24x7',

        // Security & Vulnerability Scanners
        'nikto',
        'nessus',
        'openvas',
        'nmap',
        'masscan',
        'nuclei',
        'acunetix',
        'qualys',

        // Feed Readers
        'feedfetcher',
        'feedburner',
        'feedly',
        'inoreader',

        // Specific Scrapers & Crawlers (only specific names, not generic)
        'python-requests',
        'scrapy',
        'phantomjs',
        'puppeteer',
        'selenium',
        'archive.org_bot',
        'ccbot',
        'magpie-crawler',
        'opensiteexplorer',
        'proximic',
        'siteimprove',
        'domainappender',
        'feedparser',
        'go-http-client',
        'okhttp',
        'apache-httpclient',
    ];

    /**
     * Bot hostname patterns
     */
    protected array $botHostnamePatterns = [
        'googlebot.com',
        'google.com',
        'crawl.yahoo.net',
        'search.msn.com',
        'yandex.com',
        'yandex.ru',
        'baidu.com',
        'amazonaws.com',  // Often used by bots/scrapers
        'cloudflare.com',
        'facebook.com',
    ];

    /**
     * Check if the request is from a bot
     */
    public function isBot(?string $userAgent = null, ?string $ip = null): bool
    {
        // Use current request if no parameters provided
        if ($userAgent === null) {
            $userAgent = request()->userAgent();
        }

        if ($ip === null) {
            $ip = request()->ip();
        }

        // Empty user agent is suspicious
        if (empty($userAgent)) {
            return true;
        }

        // PRIORITY 1: Check for known bot IP addresses (catches deceptive bots)
        if ($ip && $this->isSuspiciousIP($ip)) {
            return true;
        }

        // PRIORITY 2: Check for suspicious/fake browser versions
        if ($this->hasFakeBrowserVersion($userAgent)) {
            return true;
        }

        // PRIORITY 3: Check user agent against specific bot patterns
        $userAgentLower = strtolower($userAgent);
        foreach ($this->botPatterns as $pattern) {
            if (str_contains($userAgentLower, strtolower($pattern))) {
                return true;
            }
        }

        // PRIORITY 4: Check for headless browser patterns
        if ($this->isHeadlessBrowser($userAgent)) {
            return true;
        }

        // PRIORITY 5: Finally, check if it's a legitimate browser
        // If it passes all bot checks and looks legitimate, allow it
        if ($this->isLegitimateUser($userAgent)) {
            return false;
        }

        // Default: If we can't confirm it's legitimate, treat as suspicious
        return false;
    }

    /**
     * Check for fake or suspicious browser versions
     */
    protected function hasFakeBrowserVersion(string $userAgent): bool
    {
        // Check for unrealistic Chrome versions (case-insensitive)
        if (preg_match('/chrome\/(\d+)\./i', $userAgent, $matches)) {
            $chromeVersion = (int)$matches[1];
            // Chrome 147 is current stable as of April 2026. Cap at 160 for ~3 months headroom.
            // Versions below 90 are very old and suspicious.
            if ($chromeVersion > 160 || $chromeVersion < 90) {
                return true;
            }
        }

        // Check for unrealistic Firefox versions
        if (preg_match('/firefox\/(\d+)\./i', $userAgent, $matches)) {
            $firefoxVersion = (int)$matches[1];
            // Firefox ~127 is current as of April 2026. Cap at 160 for headroom.
            if ($firefoxVersion > 160 || $firefoxVersion < 90) {
                return true;
            }
        }

        // Check for unrealistic Safari versions
        if (preg_match('/version\/(\d+)\./i', $userAgent, $matches)) {
            $safariVersion = (int)$matches[1];
            // Safari 26 ships with iOS 26 / macOS 26 (2026). Cap at 35 for headroom.
            if ($safariVersion > 35 || $safariVersion < 10) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user agent is from a legitimate browser/user
     * This prevents false positives
     */
    protected function isLegitimateUser(string $userAgent): bool
    {
        // Check for common legitimate browser patterns
        // Real browsers typically have Mozilla/5.0 and specific browser identifiers

        $legitimatePatterns = [
            // Modern browsers with full UA strings
            'mozilla/5.0' => [
                'chrome/',
                'safari/',
                'firefox/',
                'edg/',           // Edge
                'opr/',           // Opera
                'brave/',         // Brave
                'vivaldi/',       // Vivaldi
            ],
            // Mobile browsers
            'mobile' => ['safari', 'chrome'],
            // Specific browser checks
            'patterns' => [
                'applewebkit/',
                'gecko/',
                'trident/',       // IE
            ],
        ];

        $userAgentLower = strtolower($userAgent);

        // Check if it has Mozilla/5.0 AND a known browser identifier
        if (str_contains($userAgentLower, 'mozilla/5.0')) {
            foreach ($legitimatePatterns['mozilla/5.0'] as $browser) {
                if (str_contains($userAgentLower, $browser)) {
                    // Additionally verify it has AppleWebKit or Gecko (real browsers)
                    if (str_contains($userAgentLower, 'applewebkit') ||
                        str_contains($userAgentLower, 'gecko')) {
                        return true;
                    }
                }
            }
        }

        // Check for mobile browsers
        if (str_contains($userAgentLower, 'mobile')) {
            foreach ($legitimatePatterns['mobile'] as $browser) {
                if (str_contains($userAgentLower, $browser)) {
                    return true;
                }
            }
        }

        // Check for specific browser engines
        foreach ($legitimatePatterns['patterns'] as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                // Make sure it's not a bot pretending to be a browser
                $hasBotPattern = false;
                foreach (['bot', 'crawler', 'spider', 'scraper'] as $botWord) {
                    if (str_contains($userAgentLower, $botWord)) {
                        $hasBotPattern = true;
                        break;
                    }
                }
                if (!$hasBotPattern) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detect headless browsers (often used by bots)
     */
    protected function isHeadlessBrowser(string $userAgent): bool
    {
        $headlessPatterns = [
            'headlesschrome',
            'headless chrome',
            'phantomjs',
            'htmlunit',
            'chrome-lighthouse',
        ];

        $userAgentLower = strtolower($userAgent);
        foreach ($headlessPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is suspicious (data centers, proxies, bots, etc.)
     * Result cached per IP for 2 hours — CIDR checks are O(50) bitwise ops each call.
     */
    protected function isSuspiciousIP(string $ip): bool
    {
        // Skip for local development
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return false;
        }

        return Cache::remember('bot_ip:' . $ip, now()->addHours(2), function () use ($ip) {
            if ($this->isKnownBotIP($ip)) {
                return true;
            }
            if ($this->isDatacenterIP($ip)) {
                return true;
            }
            return false;
        });
    }

    /**
     * Check if IP belongs to known bot networks
     */
    protected function isKnownBotIP(string $ip): bool
    {
        // Search engine bot IP ranges
        $searchBotRanges = [
            // Googlebot
            '66.249.64.0/19',
            '66.102.0.0/20',
            '64.233.160.0/19',
            '72.14.192.0/18',
            '209.85.128.0/17',
            '216.239.32.0/19',
            '74.125.0.0/16',
            '64.68.90.0/24',
            // Bingbot
            '40.77.167.0/24',
            '157.55.39.0/24',
            '207.46.13.0/24',
            '207.46.199.0/24',
            '40.77.188.0/22',
            '40.77.180.0/22',
            // Other crawlers
            '185.93.229.0/24',     // Ahrefs
            '199.59.150.0/24',     // Archive.org
            '207.241.224.0/20',    // Archive.org
        ];

        foreach ($searchBotRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP belongs to a datacenter / cloud provider
     * Datacenter IPs are very commonly used by bots and scrapers
     */
    protected function isDatacenterIP(string $ip): bool
    {
        $datacenterRanges = [
            // -------------------------------------------------------
            // Tencent Cloud (AS132203, AS45090) — Singapore, HK, etc.
            // -------------------------------------------------------
            '43.128.0.0/14',   // Tencent Cloud wide block
            '43.132.0.0/14',
            '43.136.0.0/14',
            '43.140.0.0/14',
            '43.153.0.0/16',
            '43.154.0.0/15',
            '43.156.0.0/14',
            '43.160.0.0/13',
            '43.168.0.0/13',
            '43.176.0.0/13',
            '101.32.0.0/12',   // Tencent Cloud China + Asia
            '119.28.0.0/14',
            '129.226.0.0/16',  // Tencent SG
            '150.109.0.0/16',  // Tencent HK
            '162.62.0.0/16',   // Tencent Cloud

            // -------------------------------------------------------
            // Alibaba Cloud (AS45102, AS37963)
            // -------------------------------------------------------
            '8.210.0.0/16',    // Alibaba HK
            '8.218.0.0/16',
            '8.219.0.0/16',
            '47.52.0.0/14',    // Alibaba Cloud wide
            '47.56.0.0/14',
            '47.74.0.0/14',
            '47.88.0.0/14',
            '47.98.0.0/14',
            '47.241.0.0/14',
            '47.245.0.0/16',
            '121.196.0.0/14',  // Alibaba China

            // -------------------------------------------------------
            // Amazon Web Services (AS16509, AS14618)
            // -------------------------------------------------------
            '52.0.0.0/11',
            '54.64.0.0/11',
            '54.144.0.0/12',
            '54.160.0.0/11',
            '54.192.0.0/12',
            '18.140.0.0/14',   // AWS AP-Southeast (Singapore)
            '18.136.0.0/15',
            '18.138.0.0/15',

            // -------------------------------------------------------
            // DigitalOcean (AS14061)
            // -------------------------------------------------------
            '104.236.0.0/16',
            '138.68.0.0/15',
            '139.59.0.0/16',
            '159.65.0.0/16',
            '159.89.0.0/16',
            '165.227.0.0/16',
            '167.172.0.0/16',
            '167.99.0.0/16',
            '178.62.0.0/16',
            '188.166.0.0/15',
            '206.189.0.0/16',

            // -------------------------------------------------------
            // Vultr (AS20473)
            // -------------------------------------------------------
            '45.32.0.0/14',
            '45.63.0.0/16',
            '45.76.0.0/14',
            '66.42.0.0/16',
            '78.141.0.0/16',
            '95.179.0.0/16',
            '108.61.0.0/16',
            '140.82.0.0/15',
            '149.28.0.0/16',
            '155.138.0.0/16',
            '207.246.0.0/16',
            '216.128.0.0/16',

            // -------------------------------------------------------
            // Linode / Akamai Cloud (AS63949)
            // -------------------------------------------------------
            '45.33.0.0/16',
            '45.56.0.0/16',
            '45.79.0.0/16',
            '50.116.0.0/16',
            '96.126.0.0/16',
            '139.162.0.0/16',
            '172.104.0.0/14',
            '173.255.0.0/16',

            // -------------------------------------------------------
            // Hetzner (AS24940)
            // -------------------------------------------------------
            '5.9.0.0/16',
            '46.4.0.0/16',
            '78.46.0.0/15',
            '85.10.0.0/16',
            '88.198.0.0/16',
            '95.216.0.0/16',
            '116.202.0.0/15',
            '136.243.0.0/16',
            '144.76.0.0/16',
            '148.251.0.0/16',
            '157.90.0.0/16',
            '176.9.0.0/16',
            '195.201.0.0/16',
            '213.133.0.0/16',

            // -------------------------------------------------------
            // OVH / OVHcloud (AS16276)
            // -------------------------------------------------------
            '51.68.0.0/16',
            '51.75.0.0/16',
            '51.77.0.0/16',
            '51.89.0.0/16',
            '54.36.0.0/14',
            '137.74.0.0/16',
            '145.239.0.0/16',
            '151.80.0.0/16',
            '164.132.0.0/16',
            '188.165.0.0/16',
            '213.32.0.0/16',

            // -------------------------------------------------------
            // LeaseWeb (AS60781, AS28753)
            // -------------------------------------------------------
            '5.79.0.0/16',
            '87.233.192.0/19',
            '188.40.0.0/16',

            // -------------------------------------------------------
            // Contabo (AS51167)
            // -------------------------------------------------------
            '144.91.0.0/16',
            '195.201.0.0/16',
            '213.136.0.0/16',
        ];

        foreach ($datacenterRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is within a CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - (int)$mask);
        $subnetLong &= $maskLong; // Normalize subnet

        return ($ipLong & $maskLong) === $subnetLong;
    }

    /**
     * Get bot name if detected
     */
    public function getBotName(?string $userAgent = null): ?string
    {
        if ($userAgent === null) {
            $userAgent = request()->userAgent();
        }

        if (empty($userAgent)) {
            return 'Unknown Bot';
        }

        $userAgentLower = strtolower($userAgent);

        // Check for specific bot names
        $botNames = [
            'googlebot' => 'Googlebot',
            'google-inspectiontool' => 'Google Inspection Tool',
            'bingbot' => 'Bingbot',
            'slurp' => 'Yahoo Slurp',
            'duckduckbot' => 'DuckDuckBot',
            'baiduspider' => 'Baidu Spider',
            'yandexbot' => 'YandexBot',
            'facebookexternalhit' => 'Facebook Bot',
            'twitterbot' => 'TwitterBot',
            'linkedinbot' => 'LinkedIn Bot',
            'ahrefsbot' => 'AhrefsBot',
            'semrushbot' => 'SemrushBot',
            'mj12bot' => 'Majestic Bot',
            'dotbot' => 'DotBot',
            'uptimerobot' => 'UptimeRobot',
            'pingdom' => 'Pingdom',
        ];

        foreach ($botNames as $pattern => $name) {
            if (str_contains($userAgentLower, $pattern)) {
                return $name;
            }
        }

        return 'Unknown Bot';
    }

    /**
     * Check if request should be tracked in analytics
     */
    public function shouldTrack(?string $userAgent = null, ?string $ip = null): bool
    {
        return !$this->isBot($userAgent, $ip);
    }

    /**
     * Get detection details for logging/debugging
     */
    public function getDetectionDetails(?string $userAgent = null, ?string $ip = null): array
    {
        $isBot = $this->isBot($userAgent, $ip);

        return [
            'is_bot' => $isBot,
            'bot_name' => $isBot ? $this->getBotName($userAgent) : null,
            'user_agent' => $userAgent ?? request()->userAgent(),
            'ip' => $ip ?? request()->ip(),
            'detection_method' => $isBot ? $this->getDetectionMethod($userAgent) : null,
        ];
    }

    /**
     * Get the method that detected the bot
     */
    protected function getDetectionMethod(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'empty_user_agent';
        }

        if ($this->isHeadlessBrowser($userAgent)) {
            return 'headless_browser';
        }

        return 'user_agent_pattern';
    }

    /**
     * Add custom bot pattern
     */
    public function addBotPattern(string $pattern): void
    {
        if (!in_array(strtolower($pattern), array_map('strtolower', $this->botPatterns))) {
            $this->botPatterns[] = $pattern;
        }
    }

    /**
     * Get all bot patterns
     */
    public function getBotPatterns(): array
    {
        return $this->botPatterns;
    }
}

