<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
use App\Models\Attachment;
use App\Models\ThemeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeOptionSeeder extends Seeder
{
  protected $baseURL;

  public function __construct()
  {
    $this->baseURL = config('app.url');
  }

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
      $favicon = optional(Attachment::find(1))->uuid;
      $logo_white = optional(Attachment::find(2))->uuid;
      $logo_dark = optional(Attachment::find(3))->uuid;
      $tiny_logo = optional(Attachment::find(4))->uuid;
      $maintainance = optional(Attachment::find(5))->uuid;

    $options = [
      'general' => [
        'site_title' => config('app.name'),
        'site_tagline' => "Online Shopping",
        'sticky_cart_enable' => true,
        'cart_style' => 'cart_sidebar',
        'back_to_top_enable' => true,
        'language_direction' => 'ltr',
        'primary_color' => '#3087d5',
        'mode' => 'light',
        'seller_register_url' => '',
      ],
      'logo' => [
        'header_logo_id' =>  $logo_white,
        'footer_logo_id' =>  $logo_dark,
        'favicon_icon_id' => $favicon,
      ],
      'header' => [
        'sticky_header_enable' => true,
        'header_options' => 'basic_header',
        'page_top_bar_enable' => true,
        'top_bar_content' => [
          [
              "content" => "<strong class=\"me-1\">Welcome to ".config('app.name')."!</strong>Wrap new offers/gift every single day on Weekends.<strong class=\"ms-1\">New Coupon Code: FAST50</strong>"
          ],
          [
              "content" =>  "Something you love is now on sale <strong>Buy Now!</strong>"
          ],
          [
              "content" =>  "Your must-have item is calling – <strong>Buy Now!</strong>"
          ]
        ],
        'page_top_bar_dark' => false,
        'support_number' => '', // TODO: this site's support phone number
        'today_deals' => [],
        'category_ids' => [], // TODO: pick real category IDs once seeded for this site
      ],
      'footer' => [
        'footer_style' => 'light_mode',
        'footer_copyright' => true,
        'copyright_content' => '©'.date('Y').' '.config('app.name').' All rights reserved',
        // TODO: replace with this site's real founding story/region/address
        "footer_about" => "Welcome to ".config('app.name').", your destination for quality goods at affordable rates.",
        "about_address" => "",
        'about_email' => 'admin@'.config('app.app_emails_domain'),
        "footer_categories"=> [], // TODO: pick real category IDs once seeded for this site
        "useful_link"=> [
          "home",
          "collections",
          "about-us",
          "search"
        ],
        'help_center' => [
            [
                'id'    => 1,
                'name'  => 'Shop',
                'value' => 'collections',
            ],
            [
                'id'    => 2,
                'name'  => 'My Dashboard',
                'value' => 'account/dashboard',
            ],
            [
                'id'    => 3,
                'name'  => 'My Orders',
                'value' => 'account/order',
            ],
            [
                'id'    => 4,
                'name'  => 'Wishlist',
                'value' => 'wishlist',
            ],
            [
                'id'    => 5,
                'name'  => 'Compare',
                'value' => 'compare',
            ],
            [
                'id'    => 6,
                'name'  => 'FAQ',
                'value' => 'faq',
            ],
            [
                'id'    => 7,
                'name'  => 'Contact Us',
                'value' => 'contact-us',
            ],
            [
                'id'    => 8,
                'name'  => 'Terms & Conditions',
                'value' => 'pages/terms-and-conditions',
            ],
            [
                'id'    => 9,
                'name'  => 'Privacy policy',
                'value' => 'pages/privacy-policy',
            ],
            [
                'id'    => 10,
                'name'  => 'Return policy',
                'value' => 'pages/return-policy',
            ],
            [
                'id'    => 11,
                'name'  => 'Shipping policy',
                'value' => 'pages/shipping-policy',
            ],
        ],
        "support_number" => "", // TODO: this site's support phone number
        'support_email' => 'admin@'.config('app.app_emails_domain'),
        'play_store_url' => 'https://google.com/',
        'app_store_url' => 'https://apple.com/',
        'social_media_enable' => true,
        'facebook' => '', // TODO: this site's own social media links
        'instagram' => '',
        'twitter' => '',
        'pinterest' => '',
        // TODO: replace with this site's actual store location(s)
        'addresses' => [
          [
              'email'    => 'admin@'.config('app.app_emails_domain'),
              'phones'   => [],
              'address'  => '',
              'location' => 'Main Branch',
          ],
        ],
        'footer_pages' => [],
      ],
      'collection' => [
        'collection_layout' => 'collection_category_slider',
        'collection_banner_image_url' => null,
      ],
      'product' => [
        'product_layout' => 'product_thumbnail',
        'is_trending_product' => true,
        'banner_enable' => true,
        'banner_image_url' => '',
        'safe_checkout' => true,
        'safe_checkout_image' => $this->baseURL.'/frontend/images/data/payments.png',
        'secure_checkout' => true,
        'secure_checkout_image' => $this->baseURL.'/frontend/images/data/secure_checkout.png',
        'encourage_order' => true,
        'encourage_max_order_count' => 50,
        'encourage_view' => true,
        'encourage_max_view_count' => 50,
        'sticky_checkout' => true,
        'sticky_product' => true,
        'social_share' => true,
        'shipping_and_return' => "<p>Shipping and Returns are integral parts of your shopping experience, and we aim to make them as smooth as possible. We prioritize efficient shipping, striving to deliver your orders promptly within the estimated delivery window, typically ranging from 5 to 7 days. We understand that sometimes your purchase may not meet your expectations, so we offer a straightforward return policy. If you find yourself unsatisfied with your order, eligible items can be returned within 30 days of purchase, ensuring you have ample time to make a decision. Our commitment is to ensure your satisfaction and convenience throughout your shopping journey with us, and we are here to assist you every step of the way.</p><p><strong>Our Shipping Commitment:</strong></p><ul><li>Timely and reliable delivery within 5-7 days.</li><li>Real-time tracking for your orders.</li><li>Exceptional packaging to ensure your items arrive in perfect condition.</li></ul><p>&nbsp;</p><p><strong>Our Hassle-Free Returns:</strong></p><ul><li>Eligible items can be returned within 30 days.</li><li>Easy return initiation through our website.</li><li>Prompt processing of returns for a hassle-free experience.</li></ul><p>&nbsp;</p><p>We understand that your shopping needs may vary, and we are here to accommodate them while providing exceptional service.</p>"
      ],
      'blog' => [
        'blog_style' => 'grid_view',
        'blog_sidebar_type' => 'left_sidebar',
        'blog_author_enable' => true,
        'read_more_enable' => true,
      ],
      'seller' => [
        'about' => [
          'status' => true,
          'title' => 'BECOME A SELLER ON '.strtoupper(config('app.name')).'..',
          "description" => "Ready to showcase your products to the world? Join our dynamic marketplace and become a seller at our thriving multipurpose store. With a diverse customer base and a wide range of categories including groceries, fashion, electronics, and more, you'll have the perfect platform to reach a vast audience.\n\nAs a seller, you'll benefit from our user-friendly interface, seamless payment processing, and dedicated support to ensure your products shine. Whether you're a local artisan or a growing brand, our store provides the visibility and tools you need to succeed.\n\nTap into our established customer traffic, set up your shop with ease, and let your products take center stage. Join us in creating a shopping experience that caters to every need and taste. Your journey to success starts here – become a seller at our multipurpose store today!",
          'image_url' => $this->baseURL.'/frontend/images/data/become-seller.png'
        ],
        'services' => [
          'status' => true,
          'title' => "WHY SELL ON ".strtoupper(config('app.name'))." ?",
          'service_1' => [
            'title' => 'Lowest Cost',
            'description' => "Unlock quality at the lowest cost, exceeding expectations.",
            'image_url' =>  $this->baseURL.'/frontend/images/data/services/1.png',
          ],
          'service_2' => [
            'title' => 'Lowest Cost',
            'description' => "Unlock quality at the lowest cost, exceeding expectations.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/2.png',
          ],
          'service_3' => [
            'title' => 'Dedicated Pickup',
            "description" => "Enjoy the convenience of dedicated pickup services for your orders.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/3.png',
          ],
          'service_4' => [
            'title' => 'Most Approachable',
            "description" => "We take pride in being the most approachable choice for your needs.",
            'image_url' => $this->baseURL.'/frontend/images/data/services/4.png',
          ]
        ],
        'steps' =>  [
          'status' => true,
          "title" => "Doing Business On ".config('app.name')." Is Really Easy",
          'step_1' => [
            'title' => "List Your Products & Get Support Service Provider",
            'description' => "Elevate your business by listing your products with us. Experience dedicated support services for your growth."
          ],
          'step_2' => [
            'title' => "Receive orders & Schedule a pickup",
            'description' => "Effortlessly receive orders and schedule pickups for ultimate convenience. Your business is simplified."
          ],
          'step_3' => [
            'title' => "Receive quick payment & grow your business",
            'description' => "Receive swift payments, fuel the growth of your business seamlessly, and watch your ventures thrive."
          ],
        ],
        'start_selling' => [
          'status' => true,
          'title' => "Start Selling",
          'description' => config('app.name')." marketplace makes it easy to sell online. Be it a manufacturer, vendor or supplier, simply sell your products online and become a top ecommerce player with minimum investment. Through a team of experts offering exclusive seller workshops, training and seller support, we focus on educating and empowering sellers. Selling with us is easy and absolutely free — all you need is to register, list your catalogue and start selling your products."
        ],
        "store_layout" =>  "basic_store",
        "store_details" => "basic_store_details"
      ],
      'contact_us' => [
        'contact_image_url' => $this->baseURL . '/frontend/images/data/contact-us.png',
        // TODO: this site's own phone/address details
        'detail_1' => [
          "label" => "Phone",
          "icon" =>  "ri-phone-line",
          "text" => ""
        ],
        'detail_2' => [
          "label" => "Email",
          "icon" =>  "ri-mail-line",
          "text" => 'admin@'.config('app.app_emails_domain')
        ],
        'detail_3' => [
          "label" => "Main Office",
          "icon" =>  "ri-map-pin-line",
          "text" => ""
        ],
      ],
      'about_us' => [
        "about" => [
          'status' => true,
          "content_left_image_url" =>  "$this->baseURL/frontend/images/data/about_banner.png",
          "content_right_image_url" =>  "$this->baseURL/frontend/images/data/about_banner.png",
          "sub_title" => "About Us",
          // TODO: replace with this site's real founding story/region
          "title" => "The Number One Online Store For Your Needs.",
          "description" => "Welcome to ".config('app.name').", your destination for high-quality goods at affordable rates.",
          "futures" => [
            [
              "icon" => "$this->baseURL/frontend/images/data/delivery.svg",
              "title" => "Free delivery for all orders"
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/leaf.svg",
              "title" => "Online Shopping"
            ],
          ]
        ],
        "clients" => [
          "status" => true,
          "sub_title" => "What We Do",
          "title" => "We Are Trusted By Clients",
          "content" => [
            [
              "icon" => "$this->baseURL/frontend/images/data/user.svg",
              "title" => "Happy Customers",
              "description" => "My goal for this coffee shop is to be able to get a coffee and get on with my day. It\'s a Thursday morning and I am rushing between meetings."
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/work.svg",
              "title" => "Business Years",
              "description" => "A coffee shop is a small business that sells coffee, pastries, and other morning goods. There are many different types of coffee shops around the world."
            ],
            [
              "icon" => "$this->baseURL/frontend/images/data/buy.svg",
              "title" => "Products Sales",
              "description" => "Some coffee shops have a seating area, while some just have a spot to order and then go somewhere else to sit down. The coffee shop that I am going to."
            ]
          ]
        ],
        "team" => [
          "status" => true,
          "sub_title" => "Our Creative Team",
          "title" => config('app.name')." Team Member",
          "members" => [
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Betty J. Turner",
              "designation" => "CEO, Company",
              "description" => "Fondue stinking bishop goat. Macaroni cheese croque monsieur cottage cheese.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"

            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Alfredo S. Rocha",
              "designation" => "Sr. Project Manager",
              "description" => "camembert de normandie. Bocconcini rubber cheese fromage frais port-salut.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Constance K. Whang",
              "designation" => "Jr. Project Manager",
              "description" => "camembert de normandie. Bocconcini rubber cheese fromage frais port-salut.",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
            [
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Gwen J. Geiger",
              "designation" => "Designer",
              "description" => "cheese on toast mozzarella bavarian bergkase smelly cheese cheesy feet",
              "instagram" => "https://instagram.com/",
              "twitter" => "https://twitter.com/",
              "pinterest" => "https://pinterest.com/",
              "facebook" =>  "https://www.facebook.com/"
            ],
          ]
        ],
        "testimonial" => [
          "status" => true,
          "sub_title" => "Latest Testimonials",
          "title" => "What People Say",
          "reviews" => [
            [
              "title" => "Disappointing Experience",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Ruth",
              "review" => "I recently bought the sauce that i have been looking for a very long time. I was so happy that my order arrived in less than the stipulated time frame. Thank you soo much Raines Africa team for your excellent service. Keep up the good work.",
              "designation" => "Client"
            ],
            [
              "title" => "Disappointing Experience",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Praise",
              "review" => "I recently bought a defy 4 plate electric stove and was thoroughly impressed! The quality was exceptional, and the features were exactly what I needed. What really stood out, though, was the outstanding customer support. The team was responsive, helpful, and made sure I got the most out of my purchase. The overall experience was seamless.",
              "designation" => "Client"
            ],
            [
              "title" => "Top Quality, Beautiful Location",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Constance",
              "review" => "Great service, very communicative and friendly. Extremely helpful. Would highly recommend their services to anyone looking to get genuine goods from SA for a reasonable price. They delivered to the house in Harare and helped unload both a large fridge and washing machine. Absolutely no complaints.",
              "designation" => "Client"
            ],
            [
              "title" => "Excellent Customer Service",
              "profile_image_url" => "$this->baseURL/frontend/images/data/user.png",
              "name" => "Maureen",
              "review" => "I recently purchased a Samsung QLED TV. I was really impressed with both the product and the customer service we received. I appreciate the efficient communication throughout the purchasing process, and prompt delivery of the order. I will definitely recommend your company to my friends and family in the future.",
              "designation" => "Client"
            ]
          ]
        ],
        "blog" => [
          "status" => true,
          "blog_ids" => []
        ]
      ],
      'error_page' => [
        "error_page_content" => "The page you are looking for could not be found. The link to this address may be outdated or we may have moved the since you last bookmarked it.",
        'back_button_enable' => true,
        'back_button_text' => "Back To Home",
      ],
      'seo' => [
        'meta_tags' => 'online store, affordable products, customers, shopping, '.config('app.name').', South Africa, Zimbabwe, Zambia',
        "meta_title" => "Online Marketplace, Vendor Collaboration, E-commerce Platform",
        "meta_description" => config('app.name').' is a one-stop online store on a mission to provide a wide variety of affordable products to our customers.',
        "og_title" => config('app.name').", Your One-Stop Online Store",
        "og_description" => config('app.name')." is a one-stop online store on a mission to provide a wide variety of affordable products to our customers. Shop with us today!",
        'og_image_id' => null
      ],
    ];

      DB::table('theme_options')->updateOrInsert(
          ['id' => 1], // Match condition
          ['options' => json_encode($options)] // Properly encode JSON
      );

    DB::table('seeders')->updateOrInsert([
      'name' => 'ThemeOptionSeeder',
      'is_completed' => true
    ]);
  }
}
