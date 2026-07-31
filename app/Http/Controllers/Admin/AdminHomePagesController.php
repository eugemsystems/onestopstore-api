<?php

namespace App\Http\Controllers\Admin;

use App\Models\HomePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminHomePagesController extends BaseAdminController
{
    protected string $permissionPrefix = 'home-pages';

    public function index()
    {
        $this->checkPermission('index');

        $homePages = HomePage::orderBy('slug')->get();

        return view('admin.home-pages.index', compact('homePages'));
    }

    public function edit($id)
    {
        $this->checkPermission('edit');

        $homePage = HomePage::findOrFail($id);

        return view('admin.home-pages.edit', compact('homePage'));
    }

    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $request->validate([
            'content' => 'required|json',
            'slug' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $homePage = HomePage::findOrFail($id);

            $homePage->update([
                'content' => json_decode($request->input('content'), true),
                'slug' => $request->slug
            ]);

            DB::commit();

            // Clear cache
            Cache::forget('home_page_' . $homePage->slug);

            return redirect()->route('admin.home-pages.index')
                ->with('success', 'Home Page updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to update home page: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function create()
    {
        $this->checkPermission('create');

        return view('admin.home-pages.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->validate([
            'content' => 'required|json',
            'slug' => 'required|string|max:255|unique:home_pages,slug'
        ]);

        DB::beginTransaction();
        try {
            HomePage::create([
                'content' => json_decode($request->input('content'), true),
                'slug' => $request->slug
            ]);

            DB::commit();

            return redirect()->route('admin.home-pages.index')
                ->with('success', 'Home Page created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to create home page: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $this->checkPermission('delete');

        try {
            $homePage = HomePage::findOrFail($id);
            $slug = $homePage->slug;
            $homePage->delete();

            // Clear cache
            Cache::forget('home_page_' . $slug);

            return redirect()->route('admin.home-pages.index')
                ->with('success', 'Home Page deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete home page: ' . $e->getMessage());
        }
    }

    public function builder($id)
    {
        $this->checkPermission('edit');

        $homePage = HomePage::findOrFail($id);

        return view('admin.home-pages.builder', compact('homePage'));
    }

    public function updateBuilder(Request $request, $id)
    {
        $this->checkPermission('edit');

        $request->validate([
            'slug' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $homePage = HomePage::findOrFail($id);

            // Build content from form data
            $content = [];

            // Home Banner
            if ($request->has('home_banner')) {
                $content['home_banner'] = [
                    'status' => $request->boolean('home_banner.status'),
                    'main_banner' => [
                        'image_url' => $request->input('home_banner.main_banner.image_url'),
                        'redirect_link' => [
                            'link' => $request->input('home_banner.main_banner.redirect_link.link'),
                            'link_type' => $request->input('home_banner.main_banner.redirect_link.link_type'),
                            'product_ids' => null
                        ]
                    ],
                    'sub_banner_1' => [
                        'image_url' => $request->input('home_banner.sub_banner_1.image_url'),
                        'redirect_link' => [
                            'link' => $request->input('home_banner.sub_banner_1.redirect_link.link'),
                            'link_type' => $request->input('home_banner.sub_banner_1.redirect_link.link_type')
                        ]
                    ],
                    'sub_banner_2' => [
                        'image_url' => $request->input('home_banner.sub_banner_2.image_url'),
                        'redirect_link' => [
                            'link' => $request->input('home_banner.sub_banner_2.redirect_link.link'),
                            'link_type' => $request->input('home_banner.sub_banner_2.redirect_link.link_type')
                        ]
                    ]
                ];
            }

            // Featured Banners
            if ($request->has('featured_banners')) {
                $banners = [];
                $bannerInputs = $request->input('featured_banners.banners', []);

                foreach ($bannerInputs as $banner) {
                    $banners[] = [
                        'status'        => isset($banner['status']) && $banner['status'] == '1',
                        'image_url'     => $banner['image_url'] ?? '',
                        'zambia_only'   => isset($banner['zambia_only']) && $banner['zambia_only'] == '1',
                        'zimbabwe_only' => isset($banner['zimbabwe_only']) && $banner['zimbabwe_only'] == '1',
                        'redirect_link' => [
                            'link'        => $banner['redirect_link']['link'] ?? null,
                            'link_type'   => $banner['redirect_link']['link_type'] ?? 'collection',
                            'product_ids' => null
                        ]
                    ];
                }

                $content['featured_banners'] = [
                    'status' => $request->boolean('featured_banners.status'),
                    'banners' => $banners
                ];
            }

            // Main Content
            if ($request->has('main_content')) {
                $content['main_content'] = [
                    'status' => $request->boolean('main_content.status'),
                    'section1_products' => [
                        'title' => $request->input('main_content.section1_products.title'),
                        'status' => $request->boolean('main_content.section1_products.status'),
                        'description' => null,
                        'product_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.section1_products.product_ids', ''))))
                    ],
                    'section4_products' => [
                        'title' => $request->input('main_content.section4_products.title'),
                        'status' => $request->boolean('main_content.section4_products.status'),
                        'description' => null,
                        'product_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.section4_products.product_ids', ''))))
                    ],
                    'section7_products' => [
                        'title' => $request->input('main_content.section7_products.title'),
                        'status' => $request->boolean('main_content.section7_products.status'),
                        'description' => null,
                        'product_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.section7_products.product_ids', ''))))
                    ],
                    'home_appliances' => [
                        'title' => $request->input('main_content.home_appliances.title'),
                        'status' => $request->boolean('main_content.home_appliances.status'),
                        'description' => null,
                        'product_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.home_appliances.product_ids', ''))))
                    ],
                    'section2_categories_list' => [
                        'title' => $request->input('main_content.section2_categories_list.title'),
                        'status' => $request->boolean('main_content.section2_categories_list.status'),
                        'image_url' => null,
                        'description' => null,
                        'category_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.section2_categories_list.category_ids', ''))))
                    ],
                    'sidebar' => [
                        'status' => $request->boolean('main_content.sidebar.status'),
                        'sidebar_products' => [
                            'title' => $request->input('main_content.sidebar.sidebar_products.title'),
                            'status' => true,
                            'product_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.sidebar.sidebar_products.product_ids', ''))))
                        ],
                        'left_side_banners' => $homePage->content['main_content']['sidebar']['left_side_banners'] ?? [],
                        'categories_icon_list' => [
                            'title' => 'Categories',
                            'status' => true,
                            'category_ids' => array_filter(array_map('intval', explode(',', $request->input('main_content.sidebar.categories_icon_list.category_ids', ''))))
                        ]
                    ],
                    'section5_coupons' => $homePage->content['main_content']['section5_coupons'] ?? [],
                    'section8_full_width_banner' => $homePage->content['main_content']['section8_full_width_banner'] ?? [],
                    'section3_two_column_banners' => $homePage->content['main_content']['section3_two_column_banners'] ?? [],
                    'section6_two_column_banners' => $homePage->content['main_content']['section6_two_column_banners'] ?? [],
                    'section9_featured_blogs' => $homePage->content['main_content']['section9_featured_blogs'] ?? []
                ];
            }

            // Newsletter
            if ($request->has('news_letter')) {
                $content['news_letter'] = [
                    'title' => $request->input('news_letter.title'),
                    'status' => $request->boolean('news_letter.status'),
                    'image_url' => $request->input('news_letter.image_url'),
                    'sub_title' => $request->input('news_letter.sub_title')
                ];
            }

            // Products IDs (collect from all sections)
            $productIds = [];
            if (isset($content['main_content'])) {
                foreach (['section1_products', 'section4_products', 'section7_products', 'home_appliances'] as $section) {
                    if (isset($content['main_content'][$section]['product_ids'])) {
                        $productIds = array_merge($productIds, $content['main_content'][$section]['product_ids']);
                    }
                }
                if (isset($content['main_content']['sidebar']['sidebar_products']['product_ids'])) {
                    $productIds = array_merge($productIds, $content['main_content']['sidebar']['sidebar_products']['product_ids']);
                }
            }
            $content['products_ids'] = array_unique(array_filter($productIds));

            // Popup Images — save as [{image_url, link}] pairs
            $parsePopupGroup = function (array $rows): array {
                $result = [];
                foreach ($rows as $row) {
                    $url = trim($row['image_url'] ?? '');
                    if ($url === '') continue;       // skip empty rows
                    $result[] = [
                        'image_url' => $url,
                        'link'      => trim($row['link'] ?? ''),
                    ];
                }
                return $result;
            };
            $content['popup_images'] = [
                'zambia'       => $parsePopupGroup($request->input('popup_images.zambia', [])),
                'south_africa' => $parsePopupGroup($request->input('popup_images.south_africa', [])),
                'other'        => $parsePopupGroup($request->input('popup_images.other',  [])),
            ];


            $homePage->update([
                'content' => $content,
                'slug' => $request->slug
            ]);

            DB::commit();

            // Clear cache
            Cache::forget('home_page_' . $homePage->slug);

            return redirect()->route('admin.home-pages.builder', $homePage->id)
                ->with('success', 'Home Page updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to update home page: ' . $e->getMessage())
                ->withInput();
        }
    }
}

