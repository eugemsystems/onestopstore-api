<?php

namespace Database\Seeders;

use App\Models\HomePage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomePageSeeder extends Seeder
{
    protected $baseURL;
    protected $theme;

    public function __construct()
    {
        $this->baseURL = config('app.url');
        $this->theme = config('app.theme');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $contents = [
            'paris' => [
                'content' => [
                    'home_banner' => [
                        'status' => true,
                        'main_banner' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/1.0.png',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_1' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/2.0.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],
                        'sub_banner_2' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/2.1.jpg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ]
                    ],

                    'featured_banners' => [
                        'status' => true,
                        'banners' => [
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/3.0.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ],
                            [
                                'status'    => true,
                                'image_url' =>$this->baseURL.'/frontend/images/themes/'.$this->theme.'/3.1.png',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/3.2.png',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            [
                                'status'    => true,
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/3.3.png',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ]
                        ],
                    ],

                    'main_content' => [
                        'status'    => true,
                        'sidebar' => [
                            'status' => true,
                            'categories_icon_list' => [
                                'title' => 'Categories',
                                'category_ids' => [],
                                'status'    => true,
                            ],
                            'left_side_banners' => [
                                'status' => true,
                                'banner_1' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/4.jpg',
                                    'redirect_link' => [
                                        'link' =>'',
                                        'link_type' => 'collection',
                                    ]
                                ],
                                'banner_2' => [
                                    'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/5.jpg',
                                    'redirect_link' => [
                                        'link' =>'',
                                        'link_type' => 'collection',
                                    ]
                                ]
                            ],
                            'sidebar_products' => [
                                'title' => 'Trending Products',
                                'status' => true,
                                'product_ids' => []
                            ]
                        ],

                        'section1_products' => [
                            'title' => 'Top Save Today',
                            'description' => "Don't miss this opportunity at a special discount just for this week.",
                           'product_ids' => [],
                           'status' => true
                        ],

                        // Same shape as section1/4/7_products — read by AdminHomePagesController's
                        // product_ids merge loop alongside those three.
                        'home_appliances' => [
                            'title' => 'Best Selling Appliances',
                            'description' => null,
                            'product_ids' => [],
                            'status' => true
                        ],

                        'section2_categories_list' => [
                            'title' => 'Bowse By Categories',
                            'description' => 'Uncover Hidden Gems and Culinary Delights',
                            'status' => true,
                            'image_url' =>  null,
                            'category_ids' => []
                        ],

                        'section3_two_column_banners' => [
                            'status' => true,
                            'banner_1' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/6.0.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ],
                            'banner_2' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/6.1.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ],
                            ]
                        ],

                        'section4_products' => [
                            'title' => 'Fresh Veggies and Fruits',
                            'description' => "Unlocking the Pantry: A Journey into Essential Food Cupboard Staples",
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section5_coupons' => [
                            'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/7.0.png',
                            'status' => true,
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],

                        'section6_two_column_banners' => [
                            'status' => true,
                            'banner_1' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/8.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                ]
                            ],
                            'banner_2' => [
                                'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/9.jpg',
                                'redirect_link' => [
                                    'link' =>'',
                                    'link_type' => 'collection',
                                    'product_ids' => null
                                ]
                            ]
                        ],

                        'section7_products' => [
                            'title' => 'Our Best Seller',
                            'description' => "A virtual assistant collects the products from your list.",
                            'status' => true,
                            'product_ids' => [],
                        ],

                        'section8_full_width_banner' => [
                            'status' => true,
                            'image_url' => $this->baseURL.'/frontend/images/themes/'.$this->theme.'/10.jpeg',
                            'redirect_link' => [
                                'link' =>'',
                                'link_type' => 'collection',
                            ]
                        ],

                        'section9_featured_blogs' => [
                            'title' => 'Featured Blog',
                            'description' => 'A virtual assistant collects the products from your list',
                            'status' => false,
                            'blog_ids' => [],
                        ]
                    ],

                    'news_letter' => [
                        'title' => 'Join Our Newsletter And Get...',
                        'sub_title' => '$20 discount for your first order',
                        'image_url' => $this->baseURL.'/frontend/images/data/newsletter.jpg',
                        'status' => false,
                    ],

                    'products_ids' => [],

                    // Fixed keys — AdminHomePagesController::update() hardcodes zambia/south_africa/other
                    // when parsing popup_images from the request, so these three must always be present.
                    'popup_images' => [
                        'zambia' => [],
                        'south_africa' => [],
                        'other' => [],
                    ],
                ]
            ]
        ];

        foreach($contents as $slug => $data) {
            HomePage::updateOrCreate([
                'slug' => $slug,
                'content' => $data['content'],
            ]);
        }

        DB::table('seeders')->updateOrInsert([
            'name' => 'HomePageSeeder',
            'is_completed' => true
        ]);
    }
}
