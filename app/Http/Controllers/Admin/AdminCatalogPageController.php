<?php

namespace App\Http\Controllers\Admin;

use App\Models\CatalogPage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Admin page for publishing uploaded HTML files at permanent URLs
 * ({frontend}/catalog/{slug}). Re-uploading for a slug replaces its file.
 */
class AdminCatalogPageController extends BaseAdminController
{
    protected string $permissionPrefix = 'catalog-pages';

    private const MAX_KB = 10240; // 10 MB

    public function index()
    {
        $this->checkPermission('index');

        return view('admin.catalog-pages.index', [
            'pages' => CatalogPage::orderBy('slug')->get(),
            'baseUrl' => CatalogPage::frontendOrigin() . '/catalog/',
            'daily' => DB::table('catalog_page_daily_hits')
                ->where('date', '>=', now()->subDays(29)->toDateString())
                ->orderByDesc('date')->get()->groupBy('catalog_page_id'),
            'today' => now()->toDateString(),
            'weekStart' => now()->subDays(6)->toDateString(),
            'maxMb' => self::MAX_KB / 1024,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->merge(['slug' => strtolower(trim((string) $request->input('slug')))]);

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', 'unique:catalog_pages,slug'],
            'title' => ['nullable', 'string', 'max:150'],
            'html_file' => ['required', 'file', 'extensions:html,htm', 'max:' . self::MAX_KB],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers, "-" or "_" only (e.g. summer-catalog).',
            'slug.unique' => 'That link already exists — open it in the list below and replace its file instead.',
            'html_file.extensions' => 'The file must be an .html or .htm file.',
        ]);

        $page = new CatalogPage([
            'slug' => $data['slug'],
            'title' => $data['title'] ?? null,
            'is_active' => true,
            'version' => 1,
            'updated_by' => auth()->id(),
        ]);
        $this->storeFile($page, $request->file('html_file'));
        $page->save();

        return redirect()->route('admin.catalog-pages.index')
            ->with('success', "Published: {$page->publicUrl()}");
    }

    public function update(Request $request, CatalogPage $catalogPage)
    {
        $this->checkPermission('update');

        // The slug is deliberately not editable: the link must never change.
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'html_file' => ['nullable', 'file', 'extensions:html,htm', 'max:' . self::MAX_KB],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'html_file.extensions' => 'The file must be an .html or .htm file.',
        ]);

        $catalogPage->title = $data['title'] ?? $catalogPage->title;
        if ($request->has('is_active') || $request->has('_toggle')) {
            $catalogPage->is_active = $request->boolean('is_active');
        }
        $replaced = false;
        if ($request->hasFile('html_file')) {
            $this->storeFile($catalogPage, $request->file('html_file'));
            $catalogPage->version++;
            $replaced = true;
        }
        $catalogPage->updated_by = auth()->id();
        $catalogPage->save();

        return redirect()->route('admin.catalog-pages.index')
            ->with('success', $replaced
                ? "File replaced. {$catalogPage->publicUrl()} now shows the new file (allow up to a minute for caches)."
                : 'Saved.');
    }

    public function destroy(CatalogPage $catalogPage)
    {
        $this->checkPermission('destroy');

        if ($catalogPage->file_path) {
            Storage::disk('public')->delete($catalogPage->file_path);
        }
        $slug = $catalogPage->slug;
        $catalogPage->delete();

        return redirect()->route('admin.catalog-pages.index')->with('success', "Deleted /catalog/{$slug}.");
    }

    /** Download the file currently published for a page (to edit and re-upload). */
    public function download(CatalogPage $catalogPage)
    {
        $this->checkPermission('index');

        abort_unless($catalogPage->fileExists(), 404);

        return Storage::disk('public')->download(
            $catalogPage->file_path,
            $catalogPage->original_name ?: "{$catalogPage->slug}.html"
        );
    }

    /**
     * Save the upload as storage/app/public/catalog-pages/{slug}.html, overwriting the
     * previous file. Always UTF-8 (the site serves it as UTF-8), without a BOM.
     */
    private function storeFile(CatalogPage $page, UploadedFile $file): void
    {
        $content = (string) file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (trim($content) === '') {
            throw ValidationException::withMessages(['html_file' => 'The file is empty.']);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $path = "catalog-pages/{$page->slug}.html";
        Storage::disk('public')->put($path, $content);

        $page->file_path = $path;
        $page->original_name = $file->getClientOriginalName();
        $page->size = strlen($content);
    }
}
