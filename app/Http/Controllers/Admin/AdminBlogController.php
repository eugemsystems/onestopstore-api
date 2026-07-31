<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminBlogController extends BaseAdminController
{
    protected string $permissionPrefix = 'blog';

    /**
     * Display a listing of blogs with search, pagination, and status filter.
     */
    public function index(Request $request)
    {
        $query = Blog::query();

        // Search by title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        $query->orderBy('created_at', 'desc');

        $blogs = $query->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.blogs.index', compact('blogs', 'categories'));
    }

    /**
     * Show the form for creating a new blog.
     */
    public function create()
    {
        return view('admin.blogs.create');
    }

    /**
     * Store a newly created blog.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_sticky' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'blog_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'blog_meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Remove file fields from validated data — they are not database columns
            $blogData = collect($validated)->except(['blog_thumbnail', 'blog_meta_image', 'categories', 'tags'])->toArray();

            $blogData['slug'] = Str::slug($blogData['title']);

            // Ensure unique slug
            $originalSlug = $blogData['slug'];
            $count = 1;
            while (Blog::where('slug', $blogData['slug'])->exists()) {
                $blogData['slug'] = $originalSlug . '-' . $count;
                $count++;
            }

            $blogData['is_featured'] = $request->has('is_featured') ? 1 : 0;
            $blogData['is_sticky'] = $request->has('is_sticky') ? 1 : 0;
            $blogData['status'] = $request->has('status') ? 1 : 0;
            $blogData['created_by_id'] = auth()->id();

            // Upload images BEFORE creating the blog so IDs are available
            Log::info('Blog store: checking files', [
                'has_thumbnail' => $request->hasFile('blog_thumbnail'),
                'has_meta_image' => $request->hasFile('blog_meta_image'),
                'all_files' => array_keys($request->allFiles()),
                'input_keys' => array_keys($request->all()),
            ]);

            if ($request->hasFile('blog_thumbnail')) {
                $attachment = $this->uploadImage($request->file('blog_thumbnail'), 'blogs');
                $blogData['blog_thumbnail_id'] = $attachment->id;
                Log::info('Blog thumbnail uploaded', ['attachment_id' => $attachment->id, 'url' => $attachment->image_url]);
            }

            if ($request->hasFile('blog_meta_image')) {
                $attachment = $this->uploadImage($request->file('blog_meta_image'), 'blogs');
                $blogData['blog_meta_image_id'] = $attachment->id;
                Log::info('Blog meta image uploaded', ['attachment_id' => $attachment->id, 'url' => $attachment->image_url]);
            }

            Log::info('Blog data before create', ['blogData_keys' => array_keys($blogData), 'has_thumbnail_id' => isset($blogData['blog_thumbnail_id'])]);

            $blog = Blog::create($blogData);

            // Sync categories
            if ($request->has('categories')) {
                $blog->categories()->sync($request->categories);
            }

            // Sync tags
            if ($request->has('tags')) {
                $blog->tags()->sync($request->tags);
            }

            DB::commit();

            return redirect()->route('admin.blogs.index')
                ->with('success', 'Blog created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create blog', ['error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Failed to create blog: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified blog.
     */
    public function edit($id)
    {
        $blog = Blog::with(['categories', 'tags', 'blog_thumbnail', 'blog_meta_image'])->findOrFail($id);
        return view('admin.blogs.edit', compact('blog'));
    }

    /**
     * Update the specified blog.
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_sticky' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'blog_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'blog_meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Remove file fields from validated data — they are not database columns
            $blogData = collect($validated)->except(['blog_thumbnail', 'blog_meta_image', 'categories', 'tags'])->toArray();

            $blogData['is_featured'] = $request->has('is_featured') ? 1 : 0;
            $blogData['is_sticky'] = $request->has('is_sticky') ? 1 : 0;
            $blogData['status'] = $request->has('status') ? 1 : 0;

            // Upload images before updating
            Log::info('Blog update: checking files', [
                'blog_id' => $id,
                'has_thumbnail' => $request->hasFile('blog_thumbnail'),
                'has_meta_image' => $request->hasFile('blog_meta_image'),
                'all_files' => array_keys($request->allFiles()),
            ]);

            if ($request->hasFile('blog_thumbnail')) {
                $attachment = $this->uploadImage($request->file('blog_thumbnail'), 'blogs');
                $blogData['blog_thumbnail_id'] = $attachment->id;
                Log::info('Blog thumbnail uploaded', ['attachment_id' => $attachment->id]);
            }

            if ($request->hasFile('blog_meta_image')) {
                $attachment = $this->uploadImage($request->file('blog_meta_image'), 'blogs');
                $blogData['blog_meta_image_id'] = $attachment->id;
                Log::info('Blog meta image uploaded', ['attachment_id' => $attachment->id]);
            }

            $blog->update($blogData);

            // Sync categories
            $blog->categories()->sync($request->categories ?? []);

            // Sync tags
            $blog->tags()->sync($request->tags ?? []);

            DB::commit();

            return redirect()->route('admin.blogs.index')
                ->with('success', 'Blog updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update blog', ['error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Failed to update blog: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified blog.
     */
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog deleted successfully.');
    }

    /**
     * Toggle the status (0/1) of the specified blog.
     */
    public function toggleStatus($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->status = $blog->status ? 0 : 1;
        $blog->save();

        return redirect()->back()
            ->with('success', 'Blog status updated successfully.');
    }

    /**
     * Return categories for AJAX loading in blog create/edit forms.
     * Tries cache first, falls back to direct DB query.
     */
    public function getCategories(Request $request)
    {
        // Try cached tree first
        $tree = cache('categories_hierarchical_tree');
        if ($tree && !empty($tree)) {
            return response()->json($tree);
        }

        // Fallback: query DB directly
        $categories = Category::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id'])
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'parent_id' => $cat->parent_id,
                    'path' => $cat->name,
                    'level' => $cat->parent_id ? 1 : 0,
                ];
            });

        return response()->json($categories);
    }

    /**
     * Return tags for AJAX loading in blog create/edit forms.
     * Tries cache first, falls back to direct DB query.
     */
    public function getTags(Request $request)
    {
        try {
            $tags = getCachedActiveTags();
            if ($tags && $tags->count() > 0) {
                return response()->json($tags);
            }
        } catch (\Exception $e) {
            Log::warning('getCachedActiveTags failed, falling back to DB', ['error' => $e->getMessage()]);
        }

        // Fallback: query DB directly
        $tags = Tag::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($tags);
    }

    /**
     * Upload an image using direct file storage (same pattern as auctions).
     */
    private function uploadImage($file, $collection = 'blogs')
    {
        // Store file directly to public disk (same as auctions)
        $path = $file->store('blog-images', 'public');

        // Construct URL via the streaming route (same as auctions)
        $url = rtrim(config('app.url'), '/') . '/api/auction-files/' . $path;

        $uuid = (string) Str::uuid();

        // Attachment model has $incrementing = false, so we must set the id explicitly.
        // Use max(id) + 1 to avoid collisions.
        $nextId = (int) Attachment::max('id') + 1;

        $attachment = Attachment::create([
            'id'              => $nextId,
            'uuid'            => $uuid,
            'name'            => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name'       => basename($path),
            'mime_type'       => $file->getMimeType(),
            'disk'            => 'public',
            'collection_name' => $collection,
            'size'            => $file->getSize(),
            'original_url'    => $url,
            'image_url'       => $url,
            'model_type'      => 'App\\Models\\Blog',
        ]);

        return $attachment;
    }
}
