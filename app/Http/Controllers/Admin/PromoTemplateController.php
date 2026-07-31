<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoTemplate;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PromoTemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index()
    {
        $templates = PromoTemplate::orderBy('created_at', 'desc')->get();
        return view('admin.promo-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('admin.promo-templates.create');
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'status' => 'required|boolean',
        ]);

        try {
            $template = PromoTemplate::create($validated);

            return redirect()
                ->route('admin.promo-templates.edit', $template->id)
                ->with('success', 'Template created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating promo template: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to create template: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit($id)
    {
        $template = PromoTemplate::findOrFail($id);
        return view('admin.promo-templates.edit', compact('template'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'html_content'     => 'required|string',
            'status'           => 'required|boolean',
            'preview_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'preview_image_url'  => 'nullable|url|max:1000',
        ]);

        try {
            $template = PromoTemplate::findOrFail($id);

            $template->name         = $request->input('name');
            $template->description  = $request->input('description');
            $template->html_content = $request->input('html_content');
            $template->status       = $request->boolean('status');

            // Handle preview image: uploaded file takes priority over URL
            if ($request->hasFile('preview_image_file')) {
                // Delete old uploaded file if it's a local path
                if ($template->preview_image && !str_starts_with($template->preview_image, 'http')) {
                    Storage::disk('public')->delete($template->preview_image);
                }
                $path = $request->file('preview_image_file')
                    ->store('promo-previews', 'public');
                $template->preview_image = Storage::disk('public')->url($path);
            } elseif ($request->filled('preview_image_url')) {
                $template->preview_image = $request->input('preview_image_url');
            } elseif ($request->input('clear_preview_image')) {
                $template->preview_image = null;
            }

            $template->save();

            return back()->with('success', 'Template updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating promo template: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to update template: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified template
     */
    public function destroy($id)
    {
        try {
            $template = PromoTemplate::findOrFail($id);
            $template->delete();

            return redirect()
                ->route('admin.promo-templates.index')
                ->with('success', 'Template deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting promo template: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Show the template generator page
     */
    public function generate($id)
    {
        $template = PromoTemplate::findOrFail($id);
        return view('admin.promo-templates.generate', compact('template'));
    }

    /**
     * Duplicate a template
     */
    public function duplicate($id)
    {
        try {
            $template = PromoTemplate::findOrFail($id);

            $newTemplate = $template->replicate();
            $newTemplate->name = $template->name . ' (Copy)';
            $newTemplate->save();

            return redirect()
                ->route('admin.promo-templates.edit', $newTemplate->id)
                ->with('success', 'Template duplicated successfully!');
        } catch (\Exception $e) {
            Log::error('Error duplicating promo template: ' . $e->getMessage());
            return back()->with('error', 'Failed to duplicate template: ' . $e->getMessage());
        }
    }

    /**
     * Show the SKU-based single-product banner generator page
     */
    public function generateSku($id)
    {
        $template  = PromoTemplate::findOrFail($id);
        $templates = PromoTemplate::where('status', 1)->orderBy('name')->get(['id','name','preview_image']);

        // Extract the background-image URL from the template's stored CSS
        $bgUrl    = null;
        $bgBase64 = null;

        if ($template->html_content) {
            if (preg_match('/background-image\s*:\s*url\s*\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/i', $template->html_content, $match)) {
                $bgUrl = $match[1];
            }
        }

        // Convert to base64 server-side so the browser never needs to fetch it
        // (avoids CORS restrictions on external image hosts like media.raines.africa)
        if ($bgUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(15)->get($bgUrl);
                if ($response->successful()) {
                    $mime     = $response->header('Content-Type') ?: 'image/jpeg';
                    $mime     = strtok($mime, ';'); // strip charset if present
                    $bgBase64 = 'data:' . $mime . ';base64,' . base64_encode($response->body());
                }
            } catch (\Exception $e) {
                // Leave $bgBase64 null — banner will render without background
            }
        }

        // Extract the full CSS from the template's <style> block
        // Strip the background-image rule (we handle that separately via base64)
        $templateCss = '';
        if ($template->html_content) {
            if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $template->html_content, $styleMatch)) {
                $css = $styleMatch[1];
                // Remove background-image declarations (served as base64 inline)
                $css = preg_replace('/background-image\s*:[^;]+;?/i', '', $css);
                $templateCss = trim($css);
            }
        }

        return view('admin.promo-templates.generate-sku', compact('template', 'templates', 'bgUrl', 'bgBase64', 'templateCss'));
    }

    /**
     * Accept an array of { sku, imageDataUrl, product } objects,
     * store each PNG to public storage, and return public URLs.
     */
    public function saveImages(Request $request)
    {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'No images provided'], 422);
        }

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory('promo-banners');

        $saved = [];

        foreach ($items as $item) {
            try {
                $sku          = preg_replace('/[^a-zA-Z0-9\-_]/', '', $item['sku'] ?? 'unknown');
                $dataUrl      = $item['imageDataUrl'] ?? '';
                $productData  = $item['product'] ?? [];

                if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
                    continue;
                }

                // Detect format (WebP or PNG) and use correct extension
                preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $fmtMatch);
                $ext    = strtolower($fmtMatch[1] ?? 'png') === 'webp' ? 'webp' : 'png';
                $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
                $binary = base64_decode($base64);

                $basename = $sku . '-' . now()->format('Ymd-His') . '-' . uniqid() . '.' . $ext;
                $storagePath = 'promo-banners/' . $basename;

                // storage/app/public is owned by www-data — always writable
                Storage::disk('public')->put($storagePath, $binary);

                // Serve via a dedicated stream route (bypasses nginx symlink entirely)
                $publicUrl = rtrim(config('app.url'), '/') . '/admin/promo-banners/' . $basename;

                $saved[] = [
                    'sku'     => $sku,
                    'url'     => $publicUrl,
                    'product' => $productData,
                ];
            } catch (\Exception $e) {
                Log::error('Error saving promo image for SKU ' . ($item['sku'] ?? '?') . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'saved'   => $saved,
            'count'   => count($saved),
        ]);
    }

    /**
     * Get available currencies for promo template
     */
    /**
     * Stream a saved promo banner directly from storage.
     * Bypasses nginx symlink (disable_symlinks issue on production).
     * Route: GET /admin/promo-banners/{file}
     */
    public function servePromoBanner(string $file)
    {
        $file = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $file);
        $path = storage_path('app/public/promo-banners/' . $file);

        abort_unless(file_exists($path), 404);

        // Serve correct Content-Type based on file extension
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'webp'         => 'image/webp',
            'jpg', 'jpeg'  => 'image/jpeg',
            default        => 'image/png',
        };

        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Get available currencies for promo template
     */
    public function getCurrencies()
    {
        try {
            $currencies = Currency::where('status', 1)
                ->select('id', 'code', 'symbol', 'exchange_rate')
                ->orderBy('code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'currencies' => $currencies
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching currencies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch currencies'
            ], 500);
        }
    }

    /**
     * Remove background from a product image using rembg (AI-based, Python).
     * Caches the result as promo-nobg/{sku}.png so repeated calls are instant.
     * Works on both Windows and Linux/Docker.
     */
    public function removeBackground(Request $request)
    {
        $request->validate([
            'image_url' => 'required|url',
            'sku'       => 'required|string|max:100',
        ]);

        $sku      = preg_replace('/[^a-zA-Z0-9\-_]/', '', $request->input('sku'));
        $imageUrl = $request->input('image_url');
        $cachePath = 'promo-nobg/' . $sku . '.png';

        // Unwrap proxy URLs: /proxy-image?url=REAL_URL → extract the real external URL
        if (preg_match('/[?&]url=([^&]+)/', $imageUrl, $m)) {
            $imageUrl = urldecode($m[1]);
        }

        // 1) Return cached version if it exists
        if (Storage::disk('public')->exists($cachePath)) {
            $publicUrl = rtrim(config('app.url'), '/') . '/admin/promo-nobg/' . $sku . '.png';
            return response()->json([
                'success' => true,
                'url'     => $publicUrl,
                'cached'  => true,
            ]);
        }

        try {
            // 2) Download the original image directly from external source
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(30)
                ->get($imageUrl);
            if (!$response->successful()) {
                throw new \Exception('Failed to download image: HTTP ' . $response->status());
            }

            // Save to temp file
            Storage::disk('public')->makeDirectory('promo-nobg');
            $tmpInput  = storage_path('app/temp_rembg_input_' . $sku . '.jpg');
            $tmpOutput = storage_path('app/public/' . $cachePath);

            if (!is_dir(dirname($tmpOutput))) {
                mkdir(dirname($tmpOutput), 0755, true);
            }

            file_put_contents($tmpInput, $response->body());

            // 3) Run rembg via Python script
            $scriptPath = base_path('scripts/remove_bg.py');
            $homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: (getenv('HOMEDRIVE') . getenv('HOMEPATH'));
            // Use python3 on Linux/Docker, python on Windows
            $pythonBin = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
            $process = new \Symfony\Component\Process\Process([
                $pythonBin, $scriptPath, $tmpInput, $tmpOutput
            ]);
            // Pass home dir env vars so Python's ~ expansion works and rembg finds the model
            // On Linux/Docker: use /tmp/.u2net (www-data can't write to /var/www)
            $u2netHome = PHP_OS_FAMILY === 'Windows'
                ? $homeDir . DIRECTORY_SEPARATOR . '.u2net'
                : '/tmp/.u2net';
            $process->setEnv(array_merge(getenv(), [
                'USERPROFILE'    => $homeDir,
                'HOME'           => $homeDir,
                'U2NET_HOME'     => $u2netHome,
                'NUMBA_CACHE_DIR'=> sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'numba_cache',
            ]));
            $process->setTimeout(120);
            $process->run();

            // Clean up temp input
            @unlink($tmpInput);

            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();
                throw new \Exception('rembg failed: ' . $error);
            }

            if (!file_exists($tmpOutput)) {
                throw new \Exception('rembg did not produce output file');
            }

            $publicUrl = rtrim(config('app.url'), '/') . '/admin/promo-nobg/' . $sku . '.png';

            return response()->json([
                'success' => true,
                'url'     => $publicUrl,
                'cached'  => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Background removal failed for SKU ' . $sku . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream a cached no-background image from storage.
     * Route: GET /admin/promo-nobg/{file}
     */
    public function servePromoNoBg(string $file)
    {
        $file = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $file);
        $path = storage_path('app/public/promo-nobg/' . $file);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // BG-CONVERT VARIANTS (dedicated page for background-removed banners)
    // ──────────────────────────────────────────────────────────────

    /**
     * Show the BG-Convert SKU-based banner generator page.
     * Mirrors generateSku() but loads the generate-sku-bg view.
     */
    public function generateSkuBg($id)
    {
        $template  = PromoTemplate::findOrFail($id);
        $templates = PromoTemplate::where('status', 1)->orderBy('name')->get(['id','name','preview_image']);

        $bgUrl    = null;
        $bgBase64 = null;

        if ($template->html_content) {
            if (preg_match('/background-image\s*:\s*url\s*\(\s*[\'"]?([^\'")\s]+)[\'"]?\s*\)/i', $template->html_content, $match)) {
                $bgUrl = $match[1];
            }
        }

        if ($bgUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(15)->get($bgUrl);
                if ($response->successful()) {
                    $mime     = $response->header('Content-Type') ?: 'image/jpeg';
                    $mime     = strtok($mime, ';');
                    $bgBase64 = 'data:' . $mime . ';base64,' . base64_encode($response->body());
                }
            } catch (\Exception $e) {
                // Leave $bgBase64 null
            }
        }

        $templateCss = '';
        if ($template->html_content) {
            if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $template->html_content, $styleMatch)) {
                $css = $styleMatch[1];
                $css = preg_replace('/background-image\s*:[^;]+;?/i', '', $css);
                $templateCss = trim($css);
            }
        }

        return view('admin.promo-templates.generate-sku-bg', compact('template', 'templates', 'bgUrl', 'bgBase64', 'templateCss'));
    }

    /**
     * Save BG-converted banner images to the promo-banners-bg/ folder.
     */
    public function saveImagesBg(Request $request)
    {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'No images provided'], 422);
        }

        Storage::disk('public')->makeDirectory('promo-banners-bg');

        $saved = [];

        foreach ($items as $item) {
            try {
                $sku         = preg_replace('/[^a-zA-Z0-9\-_]/', '', $item['sku'] ?? 'unknown');
                $dataUrl     = $item['imageDataUrl'] ?? '';
                $productData = $item['product'] ?? [];

                if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
                    continue;
                }

                preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $fmtMatch);
                $ext     = strtolower($fmtMatch[1] ?? 'png') === 'webp' ? 'webp' : 'png';
                $base64  = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
                $binary  = base64_decode($base64);

                $basename    = $sku . '-bg-' . now()->format('Ymd-His') . '-' . uniqid() . '.' . $ext;
                $storagePath = 'promo-banners-bg/' . $basename;

                Storage::disk('public')->put($storagePath, $binary);

                $publicUrl = rtrim(config('app.url'), '/') . '/admin/promo-banners-bg/' . $basename;

                $saved[] = [
                    'sku'     => $sku,
                    'url'     => $publicUrl,
                    'product' => $productData,
                ];
            } catch (\Exception $e) {
                Log::error('Error saving BG-convert promo image for SKU ' . ($item['sku'] ?? '?') . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'saved'   => $saved,
            'count'   => count($saved),
        ]);
    }

    /**
     * Stream a saved BG-convert banner from storage.
     * Route: GET /admin/promo-banners-bg/{file}
     */
    public function servePromoBannerBg(string $file)
    {
        $file = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $file);
        $path = storage_path('app/public/promo-banners-bg/' . $file);

        abort_unless(file_exists($path), 404);

        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'webp'         => 'image/webp',
            'jpg', 'jpeg'  => 'image/jpeg',
            default        => 'image/png',
        };

        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

