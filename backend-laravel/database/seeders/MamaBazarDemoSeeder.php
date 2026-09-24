<?php

namespace Database\Seeders;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CheckoutNotice;
use App\Models\Coupon;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\RolePermission;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\HomepageService;
use App\Services\RbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class MamaBazarDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== Starting Mama Bazar Production Demo Seeding ===');

        // 1. Prepare public storage folders
        $this->ensureStorageDirectories();

        // 2. RBAC & Users
        $this->seedUsersAndRbac();

        // 3. Payment Methods & Shipping Methods
        $this->seedPaymentAndShippingMethods();

        // 4. Brands
        $brands = $this->seedBrands();

        // 5. Exactly 10 Categories
        $categories = $this->seedCategories();

        // 6. Exactly 100 Products with Variants, Specs, and Relations
        $products = $this->seedProducts($categories, $brands);

        // 7. Banners
        $this->seedBanners();

        // 8. Site Settings & Homepage Configuration
        $this->seedSiteSettings();

        // 9. Coupons & Checkout Notices
        $this->seedCouponsAndNotices();

        // 10. Reviews
        $this->seedReviews($products);

        $this->command->info('=== Mama Bazar Demo Seeding Complete! ===');
    }

    /**
     * Ensure local storage directories exist for categories, products, banners, and brands.
     */
    protected function ensureStorageDirectories(): void
    {
        $dirs = [
            storage_path('app/public/categories'),
            storage_path('app/public/products'),
            storage_path('app/public/banners'),
            storage_path('app/public/brands'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        // If public/storage is a real directory (not symlink), ensure sync subfolders
        $pubStorage = public_path('storage');
        if (is_dir($pubStorage) && ! is_link($pubStorage)) {
            foreach (['categories', 'products', 'banners', 'brands'] as $sub) {
                $p = "{$pubStorage}/{$sub}";
                if (! is_dir($p)) {
                    @mkdir($p, 0755, true);
                }
            }
        }
    }

    /**
     * Seed RBAC roles, permissions, admin user, and demo customer.
     */
    protected function seedUsersAndRbac(): void
    {
        $this->command->info('-> Seeding RBAC & Users...');

        // Permissions
        foreach (RbacService::ALL_PERMISSIONS as $perm) {
            AdminPermission::updateOrCreate(
                ['code' => $perm['code']],
                [
                    'module'      => $perm['module'],
                    'label'       => $perm['label'],
                    'description' => $perm['description'],
                ]
            );
        }

        // Roles
        foreach (RbacService::getRolePresets() as $roleName => $preset) {
            AdminRole::updateOrCreate(
                ['name' => $roleName],
                [
                    'display_name' => $preset['displayName'],
                    'description'  => $preset['description'],
                    'is_system'    => true,
                ]
            );

            foreach ($preset['permissions'] as $permCode) {
                RolePermission::firstOrCreate([
                    'role_name'       => $roleName,
                    'permission_code' => $permCode,
                ]);
            }
        }

        // Super Admin account (mamabazar@gmail.com)
        $superAdmin = User::where('email', 'mamabazar@gmail.com')
            ->orWhere('phone', '01711111111')
            ->first();

        if (!$superAdmin) {
            $superAdmin = new User();
        }

        $superAdmin->name             = 'Super Admin';
        $superAdmin->email            = 'mamabazar@gmail.com';
        $superAdmin->phone            = '01711111111';
        $superAdmin->password         = Hash::make('mamabazar@12345');
        $superAdmin->role             = 'admin';
        $superAdmin->custom_role      = 'SUPER_ADMIN';
        $superAdmin->permissions_json = json_encode(['*']);
        $superAdmin->status           = 'active';
        $superAdmin->save();

        UserPermission::updateOrCreate(
            ['user_id' => $superAdmin->id, 'permission_code' => '*'],
            ['granted' => true]
        );

        // Demo customer account
        User::updateOrCreate(
            ['phone' => '01811112233'],
            [
                'name'             => 'Rahim Chowdhury',
                'email'            => 'customer@example.com',
                'password'         => Hash::make('Secret123!'),
                'role'             => 'user',
                'shipping_area'    => 'inside_dhaka',
                'shipping_address' => 'House 14, Road 5, Dhanmondi, Dhaka',
                'status'           => 'active',
            ]
        );
    }

    /**
     * Seed Payment Methods and Shipping Methods.
     */
    protected function seedPaymentAndShippingMethods(): void
    {
        $this->command->info('-> Seeding Payment & Shipping Methods...');

        foreach (PaymentMethod::defaultSeedRows() as $row) {
            PaymentMethod::updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }

        $shippingMethods = [
            [
                'name'                    => 'Standard Delivery (Inside Dhaka)',
                'charge'                  => 60.00,
                'estimated_delivery'      => '24-48 Hours',
                'description'             => 'Regular home delivery across all areas in Dhaka city.',
                'priority'                => 1,
                'free_shipping_min_amount'=> 1500.00,
                'cod_available'           => true,
                'status'                  => 'active',
            ],
            [
                'name'                    => 'Express Delivery (Inside Dhaka)',
                'charge'                  => 120.00,
                'estimated_delivery'      => 'Same Day (Within 6 Hours)',
                'description'             => 'Urgent same-day grocery delivery for orders placed before 3 PM.',
                'priority'                => 2,
                'free_shipping_min_amount'=> 3000.00,
                'cod_available'           => true,
                'status'                  => 'active',
            ],
            [
                'name'                    => 'Outside Dhaka Courier',
                'charge'                  => 130.00,
                'estimated_delivery'      => '2-4 Days',
                'description'             => 'Nationwide courier delivery via Steadfast / RedX / SA Paribahan.',
                'priority'                => 3,
                'free_shipping_min_amount'=> 2500.00,
                'cod_available'           => true,
                'status'                  => 'active',
            ],
        ];

        foreach ($shippingMethods as $sm) {
            ShippingMethod::updateOrCreate(
                ['name' => $sm['name']],
                $sm
            );
        }
    }

    /**
     * Seed 15 top Bangladeshi consumer brands with local SVGs.
     */
    protected function seedBrands(): array
    {
        $this->command->info('-> Seeding Brands...');

        $brandList = [
            ['name' => 'Teer', 'slug' => 'teer', 'origin' => 'Bangladesh', 'desc' => 'City Group consumer staples and cooking oils.'],
            ['name' => 'Radhuni', 'slug' => 'radhuni', 'origin' => 'Bangladesh', 'desc' => 'Square Consumer Products authentic spices and pure oils.'],
            ['name' => 'Aarong Dairy', 'slug' => 'aarong-dairy', 'origin' => 'Bangladesh', 'desc' => 'BRAC enterprise pure fresh milk, butter, and sweets.'],
            ['name' => 'Pran', 'slug' => 'pran', 'origin' => 'Bangladesh', 'desc' => 'PRAN-RFL Group snacks, confectionery, juices, and culinary items.'],
            ['name' => 'Bashundhara', 'slug' => 'bashundhara', 'origin' => 'Bangladesh', 'desc' => 'Bashundhara Group premium rice, flour, oil, and paper tissues.'],
            ['name' => 'Fresh', 'slug' => 'fresh', 'origin' => 'Bangladesh', 'desc' => 'Meghna Group of Industries food and beverage essentials.'],
            ['name' => 'Ispahani', 'slug' => 'ispahani', 'origin' => 'Bangladesh', 'desc' => 'MM Ispahani premium tea leaves and snacks.'],
            ['name' => 'Dettol', 'slug' => 'dettol', 'origin' => 'United Kingdom', 'desc' => 'Reckitt trusted antiseptic handwash, soaps, and hygiene.'],
            ['name' => 'Unilever', 'slug' => 'unilever', 'origin' => 'International', 'desc' => 'Unilever Bangladesh leading personal care and detergents.'],
            ['name' => 'Nestlé', 'slug' => 'nestle', 'origin' => 'Switzerland', 'desc' => 'Nutrition, baby foods, cereals, coffee, and confectioneries.'],
            ['name' => 'ACI Pure', 'slug' => 'aci-pure', 'origin' => 'Bangladesh', 'desc' => 'ACI Limited iodized salt, spices, and pest control.'],
            ['name' => 'Square', 'slug' => 'square', 'origin' => 'Bangladesh', 'desc' => 'Square Toiletries personal care and hygiene essentials.'],
            ['name' => 'Kazi Farms', 'slug' => 'kazi-farms', 'origin' => 'Bangladesh', 'desc' => 'Kazi Farms Kitchen organic poultry, fresh chicken, and eggs.'],
            ['name' => 'Milk Vita', 'slug' => 'milk-vita', 'origin' => 'Bangladesh', 'desc' => 'Cooperative dairy pure pasteurized milk, ghee, and curd.'],
            ['name' => 'Parachute', 'slug' => 'parachute', 'origin' => 'India', 'desc' => 'Marico pure coconut hair oil and nourishing skin care.'],
        ];

        $savedBrands = [];
        foreach ($brandList as $i => $b) {
            $logoUrl = $this->createBrandSvg($b['name'], $b['slug']);

            $brand = Brand::updateOrCreate(
                ['slug' => $b['slug']],
                [
                    'name'                => $b['name'],
                    'logo'                => $logoUrl,
                    'description'         => $b['desc'],
                    'country_of_origin'   => $b['origin'],
                    'featured'            => true,
                    'homepage_visibility' => true,
                    'sort_order'          => $i + 1,
                    'status'              => 'active',
                ]
            );
            $savedBrands[$b['slug']] = $brand;
        }

        return $savedBrands;
    }

    /**
     * Seed exactly 10 Categories with local SVGs.
     */
    protected function seedCategories(): array
    {
        $this->command->info('-> Seeding Exactly 10 Categories...');

        $categoryDefs = [
            [
                'name'        => 'Grocery & Staples',
                'slug'        => 'grocery-staples',
                'icon_symbol' => '🌾',
                'desc'        => 'Premium rice, pulses, cooking oils, sugar, flour, spices, and everyday kitchen staples.',
                'order'       => 1,
            ],
            [
                'name'        => 'Fresh Fruits & Vegetables',
                'slug'        => 'fruits-vegetables',
                'icon_symbol' => '🍎',
                'desc'        => 'Farm-fresh organic fruits, seasonal vegetables, roots, and culinary greens delivered fresh.',
                'order'       => 2,
            ],
            [
                'name'        => 'Meat & Fish',
                'slug'        => 'meat-fish',
                'icon_symbol' => '🥩',
                'desc'        => '100% Halal fresh chicken, beef, mutton, and river fish sourced daily with hygienic processing.',
                'order'       => 3,
            ],
            [
                'name'        => 'Dairy & Eggs',
                'slug'        => 'dairy-eggs',
                'icon_symbol' => '🥛',
                'desc'        => 'Pasteurized milk, butter, farm-fresh eggs, pure ghee, yogurt, and artisanal cheese.',
                'order'       => 4,
            ],
            [
                'name'        => 'Snacks & Confectionery',
                'slug'        => 'snacks-confectionery',
                'icon_symbol' => '🍪',
                'desc'        => 'Crispy chanachur, potato chips, cookies, wafers, chocolates, and afternoon tea snacks.',
                'order'       => 5,
            ],
            [
                'name'        => 'Beverages',
                'slug'        => 'beverages',
                'icon_symbol' => '☕',
                'desc'        => 'Premium black tea, instant coffee, fruit juices, soft drinks, syrups, and pure mineral water.',
                'order'       => 6,
            ],
            [
                'name'        => 'Bakery & Breakfast',
                'slug'        => 'bakery-breakfast',
                'icon_symbol' => '🍞',
                'desc'        => 'Fresh sliced bread, buns, morning cereals, oats, fruit jams, spreads, and muffins.',
                'order'       => 7,
            ],
            [
                'name'        => 'Personal Care & Hygiene',
                'slug'        => 'personal-care-hygiene',
                'icon_symbol' => '🧴',
                'desc'        => 'Soaps, shampoos, hair oils, body lotions, dental care, shaving, and skincare products.',
                'order'       => 8,
            ],
            [
                'name'        => 'Household & Cleaning',
                'slug'        => 'household-cleaning',
                'icon_symbol' => '🧹',
                'desc'        => 'Detergents, dishwashing liquids, floor cleaners, paper napkins, tissues, and waste bags.',
                'order'       => 9,
            ],
            [
                'name'        => 'Baby Care & Maternity',
                'slug'        => 'baby-care-maternity',
                'icon_symbol' => '🍼',
                'desc'        => 'Diapers, gentle wet wipes, infant cereals, baby shampoos, powders, and feeding accessories.',
                'order'       => 10,
            ],
        ];

        $savedCategories = [];
        foreach ($categoryDefs as $c) {
            $imgUrl = $this->createCategorySvg($c['name'], $c['slug'], $c['icon_symbol']);

            $cat = Category::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name'                => $c['name'],
                    'parent_id'           => null,
                    'image'               => $imgUrl,
                    'icon'                => $imgUrl,
                    'banner'              => $imgUrl,
                    'thumbnail'           => $imgUrl,
                    'description'         => $c['desc'],
                    'featured'            => true,
                    'sort_order'          => $c['order'],
                    'homepage_visibility' => true,
                    'seo_title'           => "{$c['name']} - Best Prices Online in Bangladesh | Mama Bazar",
                    'seo_description'     => "Buy {$c['name']} online at the lowest prices in Bangladesh with fast home delivery.",
                    'seo_keywords'        => "{$c['slug']}, grocery bd, mama bazar online",
                    'status'              => 'active',
                ]
            );
            $savedCategories[$c['slug']] = $cat;
        }

        return $savedCategories;
    }

    /**
     * Seed exactly 100 Products (10 per category).
     */
    protected function seedProducts(array $categories, array $brands): array
    {
        $this->command->info('-> Seeding Exactly 100 Products (10 per Category)...');

        $catalog = $this->getProductCatalogDefinitions();
        $savedProducts = [];

        foreach ($catalog as $item) {
            $cat = $categories[$item['category_slug']] ?? null;
            $brandObj = $brands[$item['brand_slug']] ?? null;

            if (! $cat) {
                continue;
            }

            // Generate 2 local SVG images per product
            $img1 = $this->createProductSvg($item['title'], $item['slug'], 1, $item['brand_name'], $cat->name);
            $img2 = $this->createProductSvg($item['title'], $item['slug'], 2, $item['brand_name'], $cat->name);

            $price = (float) $item['price'];
            $salePrice = isset($item['sale_price']) ? (float) $item['sale_price'] : $price;
            $discount = round($price - $salePrice, 2);
            $costPrice = round($price * 0.78, 2);

            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title'               => $item['title'],
                    'category_id'         => $cat->id,
                    'brand_id'            => $brandObj?->id,
                    'brand'               => $item['brand_name'],
                    'country_of_origin'   => $item['country_of_origin'] ?? 'Bangladesh',
                    'sku'                 => $item['sku'],
                    'barcode'             => '894' . str_pad((string) abs(crc32($item['sku'])), 9, '0', STR_PAD_LEFT),
                    'price'               => $price,
                    'sale_price'          => $salePrice < $price ? $salePrice : null,
                    'discount'            => $discount > 0 ? $discount : 0,
                    'cost_price'          => $costPrice,
                    'profit_margin'       => round($price - $costPrice, 2),
                    'short_description'   => $item['short_desc'],
                    'description'         => "<p>{$item['short_desc']}</p><p>{$item['long_desc']}</p>",
                    'tags'                => $item['tags'],
                    'features'            => $item['features'],
                    'images'              => [$img1, $img2],
                    'stock'               => $item['stock'] ?? 100,
                    'low_stock_alert'     => 10,
                    'min_order'           => 1,
                    'max_order'           => 20,
                    'stock_status'        => 'in_stock',
                    'product_status'      => 'published',
                    'status'              => 'active',
                    'is_featured'         => $item['is_featured'] ?? false,
                    'is_trending'         => $item['is_trending'] ?? false,
                    'is_best_seller'      => $item['is_best_seller'] ?? false,
                    'is_hot_deal'         => $item['is_hot_deal'] ?? false,
                    'is_new_arrival'      => $item['is_new_arrival'] ?? true,
                    'seo_title'           => "{$item['title']} - Price in Bangladesh | Mama Bazar",
                    'seo_description'     => "Buy original {$item['title']} at best price in BD. Fast home delivery and cash on delivery available.",
                    'seo_keywords'        => implode(', ', $item['tags']),
                ]
            );

            // Seed 1-2 Variants
            $this->seedVariantsForProduct($product, $item, [$img1, $img2]);

            // Seed Product Specs
            $this->seedSpecsForProduct($product, $item);

            $savedProducts[] = $product;
        }

        // Seed cross-sell product relations between pairs
        for ($i = 0; $i < count($savedProducts) - 1; $i += 2) {
            ProductRelation::firstOrCreate([
                'product_id'         => $savedProducts[$i]->id,
                'related_product_id' => $savedProducts[$i + 1]->id,
                'type'               => 'frequently_bought_together',
            ]);
        }

        return $savedProducts;
    }

    /**
     * Seed realistic variants for a product.
     */
    protected function seedVariantsForProduct(Product $product, array $item, array $images): void
    {
        $packOption = $item['pack_size'] ?? 'Standard Pack';

        ProductVariant::updateOrCreate(
            [
                'product_id' => $product->id,
                'sku'        => $product->sku . '-V1',
            ],
            [
                'name'           => "{$product->title} - {$packOption}",
                'options'        => ['Option' => $packOption],
                'price'          => $product->price,
                'discount_price' => $product->sale_price,
                'barcode'        => $product->barcode,
                'stock'          => $product->stock,
                'thumbnail'      => $images[0],
                'images'         => $images,
                'status'         => 'active',
                'availability'   => true,
            ]
        );
    }

    /**
     * Seed specifications for a product.
     */
    protected function seedSpecsForProduct(Product $product, array $item): void
    {
        $specs = [
            ['label' => 'Brand', 'value' => $item['brand_name'], 'sort_order' => 1],
            ['label' => 'Pack Size / Volume', 'value' => $item['pack_size'] ?? 'Standard', 'sort_order' => 2],
            ['label' => 'Country of Origin', 'value' => $item['country_of_origin'] ?? 'Bangladesh', 'sort_order' => 3],
            ['label' => 'Quality Guarantee', 'value' => '100% Genuine & Fresh Stock', 'sort_order' => 4],
        ];

        foreach ($specs as $spec) {
            ProductSpec::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'label'      => $spec['label'],
                ],
                $spec
            );
        }
    }

    /**
     * Seed Banners with local SVGs.
     */
    protected function seedBanners(): void
    {
        $this->command->info('-> Seeding Banners...');

        $banners = [
            [
                'title'       => 'Daily Grocery Mega Sale',
                'subtitle'    => 'Up to 25% Off on Cooking Oils, Rice & Fresh Staples',
                'slug'        => 'hero-grocery-mega-sale',
                'link'        => '/shop?category=grocery-staples',
                'position'    => 'hero',
                'button_text' => 'Shop Groceries',
                'priority'    => 10,
                'color1'      => '#047857',
                'color2'      => '#064e3b',
            ],
            [
                'title'       => 'Farm Fresh Fruits & Vegetables',
                'subtitle'    => 'Handpicked Morning Harvest Delivered to Your Doorstep',
                'slug'        => 'hero-fresh-produce',
                'link'        => '/shop?category=fruits-vegetables',
                'position'    => 'hero',
                'button_text' => 'Order Fresh',
                'priority'    => 9,
                'color1'      => '#b45309',
                'color2'      => '#78350f',
            ],
            [
                'title'       => 'Personal Care & Hygiene Essentials',
                'subtitle'    => 'Trusted Brands Unilever, Dettol, Parachute & More',
                'slug'        => 'hero-personal-care',
                'link'        => '/shop?category=personal-care-hygiene',
                'position'    => 'hero',
                'button_text' => 'Explore Care',
                'priority'    => 8,
                'color1'      => '#1d4ed8',
                'color2'      => '#1e3a8a',
            ],
        ];

        foreach ($banners as $b) {
            $bannerImg = $this->createBannerSvg($b['title'], $b['subtitle'], $b['slug'], $b['color1'], $b['color2']);

            Banner::updateOrCreate(
                ['title' => $b['title']],
                [
                    'subtitle'     => $b['subtitle'],
                    'image'        => $bannerImg,
                    'image_mobile' => $bannerImg,
                    'image_tablet' => $bannerImg,
                    'link'         => $b['link'],
                    'position'     => $b['position'],
                    'button_text'  => $b['button_text'],
                    'priority'     => $b['priority'],
                    'status'       => 'active',
                ]
            );
        }
    }

    /**
     * Seed Site Settings and Homepage Config.
     */
    protected function seedSiteSettings(): void
    {
        $this->command->info('-> Seeding Site Settings...');

        $settings = [
            'store_name'      => 'Mama Bazar',
            'primary_phone'   => '01943124216',
            'email'           => 'support@mamabazar.com',
            'contact_address' => 'House 42, Road 11, Banani, Dhaka, Bangladesh',
            'tax_rate'        => '0',
        ];

        foreach ($settings as $key => $val) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $val]);
        }

        // Initialize default rich homepage configuration
        $config = HomepageService::getConfig();
        $config['announcement'] = [
            'enabled'         => true,
            'text'            => 'Free express delivery on orders over ৳1,500 — Shop today with Mama Bazar!',
            'backgroundColor' => '#059669',
            'textColor'       => '#ffffff',
        ];

        SiteSetting::updateOrCreate(
            ['key' => 'homepage_config'],
            ['value' => json_encode($config)]
        );
    }

    /**
     * Seed Coupons and Checkout Notices.
     */
    protected function seedCouponsAndNotices(): void
    {
        $this->command->info('-> Seeding Coupons & Checkout Notices...');

        $coupons = [
            [
                'code'             => 'WELCOME10',
                'discount_type'    => 'percentage',
                'discount_value'   => 10.00,
                'min_order_amount' => 500.00,
                'status'           => 'active',
            ],
            [
                'code'             => 'MAMASAVE50',
                'discount_type'    => 'fixed',
                'discount_value'   => 50.00,
                'min_order_amount' => 800.00,
                'status'           => 'active',
            ],
            [
                'code'             => 'FREESHIP',
                'discount_type'    => 'fixed',
                'discount_value'   => 60.00,
                'min_order_amount' => 1200.00,
                'status'           => 'active',
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::updateOrCreate(['code' => $c['code']], $c);
        }

        CheckoutNotice::updateOrCreate(
            ['priority' => 10],
            [
                'text'             => 'Enjoy standard 24-48h delivery inside Dhaka. Cash on delivery & bKash available at checkout.',
                'background_color' => '#ECFDF5',
                'text_color'       => '#065F46',
                'icon'             => 'shield-check',
                'status'           => 'active',
            ]
        );
    }

    /**
     * Seed 15 verified product customer reviews.
     */
    protected function seedReviews(array $products): void
    {
        $this->command->info('-> Seeding Customer Reviews...');

        $sampleReviews = [
            ['name' => 'Tanvir Ahmed', 'rating' => 5, 'title' => 'Fresh & Authentic Quality', 'comment' => 'Received the order within 24 hours. The packaging was top-notch and items were 100% fresh.'],
            ['name' => 'Farhana Kabir', 'rating' => 5, 'title' => 'Best Online Grocery in Dhaka', 'comment' => 'Saved me so much time. Prices are lower than local super shops and delivery was on time.'],
            ['name' => 'Shakil Mahmud', 'rating' => 4, 'title' => 'Prompt Delivery', 'comment' => 'Delivery rider was courteous. Products came in good condition with original seal.'],
            ['name' => 'Nusrat Jahan', 'rating' => 5, 'title' => 'Excellent service', 'comment' => 'Everything ordered matched the description perfectly. Will definitely buy again!'],
        ];

        foreach (array_slice($products, 0, 15) as $i => $product) {
            $rev = $sampleReviews[$i % count($sampleReviews)];
            Review::updateOrCreate(
                [
                    'product_id'    => $product->id,
                    'customer_name' => $rev['name'],
                ],
                [
                    'rating'  => $rev['rating'],
                    'title'   => $rev['title'],
                    'comment' => $rev['comment'],
                    'status'  => 'approved',
                ]
            );
        }
    }

    // =========================================================================
    // SVG IMAGE GENERATORS (Store to local storage/app/public/...)
    // =========================================================================

    protected function createCategorySvg(string $name, string $slug, string $icon): string
    {
        $path = storage_path("app/public/categories/{$slug}.svg");
        $hue = abs(crc32($slug)) % 360;
        $hue2 = ($hue + 40) % 360;
        $cleanName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" width="100%" height="100%">
  <defs>
    <linearGradient id="cat-grad-{$slug}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="hsl({$hue}, 70%, 42%)"/>
      <stop offset="100%" stop-color="hsl({$hue2}, 80%, 25%)"/>
    </linearGradient>
    <filter id="shadow-{$slug}" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="12" stdDeviation="16" flood-color="#000" flood-opacity="0.25"/>
    </filter>
  </defs>
  <rect width="600" height="600" rx="36" fill="url(#cat-grad-{$slug})"/>
  <circle cx="300" cy="240" r="115" fill="#ffffff" fill-opacity="0.15" filter="url(#shadow-{$slug})"/>
  <rect x="235" y="175" width="130" height="130" rx="28" fill="#ffffff" fill-opacity="0.95"/>
  <text x="300" y="260" font-size="64" text-anchor="middle">{$icon}</text>
  <text x="300" y="420" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="32" font-weight="800" fill="#ffffff" text-anchor="middle">
    {$cleanName}
  </text>
  <text x="300" y="465" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="18" font-weight="600" fill="#ffffff" fill-opacity="0.8" text-anchor="middle" letter-spacing="1">
    MAMA BAZAR VERIFIED
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);

        // Also sync to public/storage if directory exists
        $pubPath = public_path("storage/categories/{$slug}.svg");
        if (is_dir(public_path('storage/categories')) && ! is_link(public_path('storage'))) {
            @file_put_contents($pubPath, $svg);
        }

        return "/storage/categories/{$slug}.svg";
    }

    protected function createProductSvg(string $title, string $slug, int $index, string $brand, string $categoryName): string
    {
        $filename = "{$slug}-{$index}.svg";
        $path = storage_path("app/public/products/{$filename}");
        $hue = abs(crc32($categoryName)) % 360;
        $cleanTitle = htmlspecialchars(mb_strimwidth($title, 0, 36, '...'), ENT_QUOTES, 'UTF-8');
        $cleanBrand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
        $viewLabel = $index === 1 ? 'Primary View' : 'Detail View';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="pbg-{$slug}-{$index}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f8fafc"/>
      <stop offset="100%" stop-color="#e2e8f0"/>
    </linearGradient>
    <filter id="pshadow-{$slug}-{$index}" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="16" stdDeviation="24" flood-color="#0f172a" flood-opacity="0.12"/>
    </filter>
  </defs>
  <rect width="800" height="800" rx="32" fill="url(#pbg-{$slug}-{$index})"/>
  <rect x="70" y="70" width="660" height="660" rx="24" fill="#ffffff" filter="url(#pshadow-{$slug}-{$index})"/>
  
  <!-- Brand Tag -->
  <rect x="110" y="110" width="130" height="34" rx="17" fill="hsl({$hue}, 80%, 45%)" fill-opacity="0.12"/>
  <text x="175" y="133" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="hsl({$hue}, 80%, 35%)" text-anchor="middle">
    {$cleanBrand}
  </text>
  
  <!-- View Tag -->
  <rect x="580" y="110" width="110" height="30" rx="8" fill="#f1f5f9"/>
  <text x="635" y="130" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="12" font-weight="600" fill="#64748b" text-anchor="middle">
    {$viewLabel}
  </text>

  <!-- Illustration Box -->
  <circle cx="400" cy="380" r="170" fill="hsl({$hue}, 60%, 96%)" stroke="hsl({$hue}, 60%, 85%)" stroke-width="2"/>
  <rect x="310" y="290" width="180" height="180" rx="28" fill="hsl({$hue}, 65%, 45%)"/>
  <circle cx="400" cy="380" r="45" fill="#ffffff" fill-opacity="0.25"/>
  <path d="M 375 380 L 425 380 M 400 355 L 400 405" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>

  <!-- Product Title & Verified Tag -->
  <text x="400" y="615" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="24" font-weight="800" fill="#0f172a" text-anchor="middle">
    {$cleanTitle}
  </text>
  <text x="400" y="650" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="15" font-weight="600" fill="#059669" text-anchor="middle">
    ✓ 100% Genuine • Fast Delivery in Bangladesh
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);

        $pubPath = public_path("storage/products/{$filename}");
        if (is_dir(public_path('storage/products')) && ! is_link(public_path('storage'))) {
            @file_put_contents($pubPath, $svg);
        }

        return "/storage/products/{$filename}";
    }

    protected function createBrandSvg(string $name, string $slug): string
    {
        $path = storage_path("app/public/brands/{$slug}.svg");
        $hue = abs(crc32($slug)) % 360;
        $cleanName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200" width="100%" height="100%">
  <rect width="400" height="200" rx="16" fill="#ffffff" stroke="#e2e8f0" stroke-width="2"/>
  <circle cx="85" cy="100" r="42" fill="hsl({$hue}, 70%, 50%)" fill-opacity="0.12"/>
  <circle cx="85" cy="100" r="24" fill="hsl({$hue}, 70%, 45%)"/>
  <text x="155" y="106" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="26" font-weight="800" fill="#0f172a">
    {$cleanName}
  </text>
  <text x="157" y="130" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="11" font-weight="600" fill="#64748b" letter-spacing="1">
    OFFICIAL BRAND
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);

        $pubPath = public_path("storage/brands/{$slug}.svg");
        if (is_dir(public_path('storage/brands')) && ! is_link(public_path('storage'))) {
            @file_put_contents($pubPath, $svg);
        }

        return "/storage/brands/{$slug}.svg";
    }

    protected function createBannerSvg(string $title, string $subtitle, string $slug, string $color1, string $color2): string
    {
        $path = storage_path("app/public/banners/{$slug}.svg");
        $cleanTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $cleanSub = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 600" width="100%" height="100%">
  <defs>
    <linearGradient id="bgrad-{$slug}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$color1}"/>
      <stop offset="100%" stop-color="{$color2}"/>
    </linearGradient>
  </defs>
  <rect width="1920" height="600" fill="url(#bgrad-{$slug})"/>
  
  <circle cx="1600" cy="300" r="280" fill="#ffffff" fill-opacity="0.08"/>
  <circle cx="1400" cy="450" r="180" fill="#ffffff" fill-opacity="0.05"/>
  
  <!-- Text container -->
  <g transform="translate(140, 180)">
    <rect x="0" y="0" width="180" height="36" rx="18" fill="#ffffff" fill-opacity="0.2"/>
    <text x="90" y="24" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="#ffffff" text-anchor="middle" letter-spacing="1">
      ★ SPECIAL OFFER
    </text>
    
    <text x="0" y="90" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="56" font-weight="900" fill="#ffffff">
      {$cleanTitle}
    </text>
    
    <text x="0" y="145" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="24" font-weight="500" fill="#ffffff" fill-opacity="0.9">
      {$cleanSub}
    </text>
    
    <rect x="0" y="185" width="200" height="54" rx="27" fill="#ffffff"/>
    <text x="100" y="219" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="800" fill="{$color1}" text-anchor="middle">
      SHOP NOW →
    </text>
  </g>
</svg>
SVG;

        file_put_contents($path, $svg);

        $pubPath = public_path("storage/banners/{$slug}.svg");
        if (is_dir(public_path('storage/banners')) && ! is_link(public_path('storage'))) {
            @file_put_contents($pubPath, $svg);
        }

        return "/storage/banners/{$slug}.svg";
    }

    // =========================================================================
    // 100 PRODUCTS DATA DEFINITION (10 Per Category)
    // =========================================================================

    protected function getProductCatalogDefinitions(): array
    {
        return [
            // ==================== 1. Grocery & Staples (10 items) ====================
            [
                'title'             => 'Teer Fortified Soybean Oil 5L',
                'slug'              => 'teer-fortified-soybean-oil-5l',
                'sku'               => 'MB-GROC-001',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'teer',
                'brand_name'        => 'Teer',
                'pack_size'         => '5 Litre Jar',
                'price'             => 890,
                'sale_price'        => 850,
                'short_desc'        => '100% pure refined soybean oil enriched with Vitamin A & D for daily family cooking.',
                'long_desc'         => 'Teer Fortified Soybean Oil is processed using state-of-the-art European refining technology, ensuring healthy, cholesterol-free cooking.',
                'tags'              => ['oil', 'soybean', 'cooking oil', 'grocery', 'teer'],
                'features'          => ['Fortified with Vitamin A & D', 'Trans-fat free', 'Triple refined'],
                'stock'             => 150,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Aarong Miniket Rice Premium 5kg',
                'slug'              => 'aarong-miniket-rice-premium-5kg',
                'sku'               => 'MB-GROC-002',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'aarong-dairy',
                'brand_name'        => 'Aarong',
                'pack_size'         => '5 kg Bag',
                'price'             => 390,
                'sale_price'        => 375,
                'short_desc'        => 'Slender long-grain aromatic Miniket rice, thoroughly sorted and cleaned for daily meals.',
                'long_desc'         => 'Aarong Premium Miniket Rice cooks into fluffy, non-sticky grains with a natural fragrance. Perfect for everyday lunches and dinners.',
                'tags'              => ['rice', 'miniket', 'staple', 'aarong'],
                'features'          => ['Sortex cleaned', '100% non-sticky', 'Slender grain'],
                'stock'             => 120,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Bashundhara Nazirshail Rice 5kg',
                'slug'              => 'bashundhara-nazirshail-rice-5kg',
                'sku'               => 'MB-GROC-003',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'bashundhara',
                'brand_name'        => 'Bashundhara',
                'pack_size'         => '5 kg Bag',
                'price'             => 440,
                'sale_price'        => 420,
                'short_desc'        => 'Traditional premium polished Nazirshail rice with high nutritional value.',
                'long_desc'         => 'Selected from the fertile agricultural fields of North Bengal, Bashundhara Nazirshail Rice offers superior texture and authentic taste.',
                'tags'              => ['rice', 'nazirshail', 'bashundhara', 'staples'],
                'features'          => ['High dietary fiber', 'Hygienically packed', 'Traditional taste'],
                'stock'             => 110,
                'is_featured'       => false,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Fresh Deshi Masoor Dal (Lentils) 1kg',
                'slug'              => 'fresh-deshi-masoor-dal-1kg',
                'sku'               => 'MB-GROC-004',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Pack',
                'price'             => 145,
                'sale_price'        => 135,
                'short_desc'        => 'High-protein small grain red lentils, cleans and cooks quickly into hearty dal.',
                'long_desc'         => 'Fresh Deshi Masoor Dal is free from artificial colors and stone residues. Packed with essential iron, protein, and dietary fiber.',
                'tags'              => ['dal', 'lentils', 'masoor', 'fresh', 'pulses'],
                'features'          => ['Quick cooking', 'Machine sorted', 'Rich in natural protein'],
                'stock'             => 140,
                'is_featured'       => true,
                'is_trending'       => true,
            ],
            [
                'title'             => 'Radhuni Pure Mustard Oil 1L',
                'slug'              => 'radhuni-pure-mustard-oil-1l',
                'sku'               => 'MB-GROC-005',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'radhuni',
                'brand_name'        => 'Radhuni',
                'pack_size'         => '1 Litre Bottle',
                'price'             => 310,
                'sale_price'        => 295,
                'short_desc'        => 'Authentic cold-pressed pungent mustard oil for traditional curries, bhortas, and pickles.',
                'long_desc'         => 'Radhuni Mustard Oil is pressed from prime quality mustard seeds. It delivers that sharp, authentic pungent aroma essential to Bengali culinary art.',
                'tags'              => ['oil', 'mustard oil', 'radhuni', 'bhorta', 'cooking'],
                'features'          => ['Pungent aroma', 'Cold pressed purity', 'Traditional recipe'],
                'stock'             => 100,
                'is_featured'       => true,
                'is_hot_deal'       => true,
            ],
            [
                'title'             => 'Teer Refined White Sugar 1kg',
                'slug'              => 'teer-refined-white-sugar-1kg',
                'sku'               => 'MB-GROC-006',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'teer',
                'brand_name'        => 'Teer',
                'pack_size'         => '1 kg Pack',
                'price'             => 140,
                'sale_price'        => 135,
                'short_desc'        => 'Sparkling white refined cane sugar crystals, free-flowing and sweet.',
                'long_desc'         => 'Teer Refined Sugar provides uniform sweetness for your morning tea, baking, desserts, and everyday cooking needs.',
                'tags'              => ['sugar', 'teer', 'sweetener', 'staples'],
                'features'          => ['Pure cane sugar', 'Moisture protected', 'Sulphur-free process'],
                'stock'             => 200,
                'is_featured'       => false,
            ],
            [
                'title'             => 'ACI Pure Iodized Table Salt 1kg',
                'slug'              => 'aci-pure-iodized-table-salt-1kg',
                'sku'               => 'MB-GROC-007',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'aci-pure',
                'brand_name'        => 'ACI Pure',
                'pack_size'         => '1 kg Pack',
                'price'             => 40,
                'sale_price'        => 38,
                'short_desc'        => 'Vacuum evaporated iodized salt for balanced daily nutrition and thyroid health.',
                'long_desc'         => 'ACI Pure Salt is completely free from impurities. Vacuum evaporated technology ensures dry, free-flowing crystal purity.',
                'tags'              => ['salt', 'iodized', 'aci', 'seasoning'],
                'features'          => ['Vacuum refined', 'Adequate iodine guarantee', 'Free flowing'],
                'stock'             => 300,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Premium Atta (Whole Wheat) 2kg',
                'slug'              => 'fresh-premium-atta-2kg',
                'sku'               => 'MB-GROC-008',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '2 kg Pack',
                'price'             => 130,
                'sale_price'        => 122,
                'short_desc'        => '100% whole grain wheat flour for soft, nutritious rotis and chapatis.',
                'long_desc'         => 'Milled from selected sound grains of wheat, Fresh Atta preserves natural dietary fiber and bran nutrients.',
                'tags'              => ['atta', 'flour', 'roti', 'wheat', 'fresh'],
                'features'          => ['100% whole wheat', 'Soft rotis for hours', 'High fiber'],
                'stock'             => 130,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Radhuni Turmeric Powder (Haldi) 200g',
                'slug'              => 'radhuni-turmeric-powder-200g',
                'sku'               => 'MB-GROC-009',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'radhuni',
                'brand_name'        => 'Radhuni',
                'pack_size'         => '200g Box',
                'price'             => 85,
                'sale_price'        => 80,
                'short_desc'        => 'Golden aromatic turmeric powder processed from select natural rhizomes.',
                'long_desc'         => 'Radhuni Haldi brings authentic golden color and antiseptic culinary goodness to every meat, fish, and vegetable curry.',
                'tags'              => ['spices', 'turmeric', 'haldi', 'radhuni'],
                'features'          => ['High curcumin', 'No artificial colors', 'Aroma locked foil'],
                'stock'             => 160,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Premium Cow Ghee 400g',
                'slug'              => 'pran-premium-cow-ghee-400g',
                'sku'               => 'MB-GROC-010',
                'category_slug'     => 'grocery-staples',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '400g Glass Jar',
                'price'             => 580,
                'sale_price'        => 550,
                'short_desc'        => 'Pure clarified butter ghee with a rich granular texture and heavenly aroma.',
                'long_desc'         => 'Pran Premium Ghee is made from farm fresh cow milk butter. Irresistible aroma for polao, biryani, khichuri, and festive desserts.',
                'tags'              => ['ghee', 'dairy', 'pran', 'butter', 'polao'],
                'features'          => ['Granular Danedar texture', 'Pure cow milk', 'Rich aroma'],
                'stock'             => 90,
                'is_featured'       => true,
                'is_hot_deal'       => true,
            ],

            // ==================== 2. Fresh Fruits & Vegetables (10 items) ====================
            [
                'title'             => 'Fresh Green Crisp Apples 1kg',
                'slug'              => 'fresh-green-crisp-apples-1kg',
                'sku'               => 'MB-FRUIT-001',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg (approx 5-6 pcs)',
                'price'             => 280,
                'sale_price'        => 260,
                'short_desc'        => 'Crispy, tangy-sweet imported green apples packed with dietary fiber and antioxidants.',
                'long_desc'         => 'Perfect for healthy snacking, fruit salads, and green smoothies. Sourced directly and kept in temperature-controlled storage.',
                'tags'              => ['fruits', 'apple', 'green apple', 'fresh', 'healthy'],
                'features'          => ['Crisp texture', 'Rich in Vitamin C', 'Naturally sweet'],
                'stock'             => 80,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Sagor Kola (Ripe Sweet Bananas) 1 Dozen',
                'slug'              => 'sagor-kola-ripe-bananas-1-dozen',
                'sku'               => 'MB-FRUIT-002',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '12 Pieces',
                'price'             => 120,
                'sale_price'        => 110,
                'short_desc'        => 'Naturally ripened Sagor bananas, rich in potassium and quick daily energy.',
                'long_desc'         => 'Carbide-free and naturally sweet. A staple for family breakfast and children nutrition.',
                'tags'              => ['banana', 'kola', 'fruits', 'potassium'],
                'features'          => ['Naturally ripened', 'No chemical agents', 'Energy rich'],
                'stock'             => 100,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Rajshahi Himsagar Mangoes 2kg',
                'slug'              => 'rajshahi-himsagar-mangoes-2kg',
                'sku'               => 'MB-FRUIT-003',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '2 kg Box',
                'price'             => 360,
                'sale_price'        => 330,
                'short_desc'        => 'King of mangoes from Rajshahi, fibreless golden sweet flesh and heavenly fragrance.',
                'long_desc'         => 'Handpicked directly from trusted orchards in Rajshahi. 100% formalin-free premium dessert mangoes.',
                'tags'              => ['mango', 'himsagar', 'rajshahi', 'seasonal', 'fruits'],
                'features'          => ['Fibreless pulp', 'Formalin free', 'Direct orchard harvest'],
                'stock'             => 60,
                'is_featured'       => true,
                'is_hot_deal'       => true,
            ],
            [
                'title'             => 'Imported Sweet Navel Oranges 1kg',
                'slug'              => 'imported-sweet-navel-oranges-1kg',
                'sku'               => 'MB-FRUIT-004',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg (approx 4-5 pcs)',
                'price'             => 260,
                'sale_price'        => 240,
                'short_desc'        => 'Juicy seedless navel oranges bursting with natural citrus flavor and Vitamin C.',
                'long_desc'         => 'Bright orange, easy-peel citrus fruits. Excellent for morning freshly squeezed juice or refreshing slices.',
                'tags'              => ['orange', 'malta', 'citrus', 'fruits'],
                'features'          => ['Juicy & sweet', 'High Vitamin C', 'Seedless'],
                'stock'             => 75,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Munshiganj Premium Red Potatoes 2kg',
                'slug'              => 'munshiganj-premium-red-potatoes-2kg',
                'sku'               => 'MB-FRUIT-005',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '2 kg Net Bag',
                'price'             => 110,
                'sale_price'        => 100,
                'short_desc'        => 'Farm-fresh firm red potatoes from Munshiganj, ideal for aloo bhorta and curries.',
                'long_desc'         => 'Firm texture that does not become soggy. Carefully cleaned and sorted for household kitchens.',
                'tags'              => ['potatoes', 'aloo', 'vegetables', 'staples'],
                'features'          => ['Firm texture', 'Soil-free cleaned', 'Munshiganj origin'],
                'stock'             => 150,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Deshi Red Cooking Onions 1kg',
                'slug'              => 'deshi-red-cooking-onions-1kg',
                'sku'               => 'MB-FRUIT-006',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Bag',
                'price'             => 95,
                'sale_price'        => 88,
                'short_desc'        => 'Pungent local red onions with thin skin and intense culinary flavour for gravies.',
                'long_desc'         => 'Essential base for all Bengali curries and stir-fries. Hand-sorted dry onions with long storage life.',
                'tags'              => ['onion', 'peyaj', 'vegetables', 'cooking'],
                'features'          => ['Deshi variety', 'Intense aroma', 'Low moisture weight'],
                'stock'             => 200,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Fresh Organic Garlic Bulbs 500g',
                'slug'              => 'fresh-organic-garlic-bulbs-500g',
                'sku'               => 'MB-FRUIT-007',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '500g Mesh Bag',
                'price'             => 130,
                'sale_price'        => 120,
                'short_desc'        => 'Tight dry garlic cloves with robust pungency for seasoning and marinades.',
                'long_desc'         => 'Garlic provides immune-boosting allicin and deep aroma when sautéed in mustard or vegetable oil.',
                'tags'              => ['garlic', 'roshun', 'vegetables', 'spices'],
                'features'          => ['Dry firm cloves', 'Easy to peel', 'Strong aroma'],
                'stock'             => 110,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Deshi Root Ginger 250g',
                'slug'              => 'fresh-deshi-root-ginger-250g',
                'sku'               => 'MB-FRUIT-008',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '250g Pack',
                'price'             => 85,
                'sale_price'        => 80,
                'short_desc'        => 'Spicy aromatic ginger roots for everyday cooking and warm winter teas.',
                'long_desc'         => 'Cleaned of excess dirt, firm roots with zesty herbal warmth.',
                'tags'              => ['ginger', 'ada', 'vegetables', 'tea'],
                'features'          => ['Aromatic', 'Juicy roots', 'Digestive aid'],
                'stock'             => 95,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Vine-Ripened Fresh Red Tomatoes 1kg',
                'slug'              => 'vine-ripened-fresh-red-tomatoes-1kg',
                'sku'               => 'MB-FRUIT-009',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Bag',
                'price'             => 90,
                'sale_price'        => 80,
                'short_desc'        => 'Firm, juicy red tomatoes for fresh salads, gravies, and homemade sauces.',
                'long_desc'         => 'Plump tomatoes picked at peak ripeness. Adds rich red color and natural acidity to dishes.',
                'tags'              => ['tomatoes', 'vegetables', 'fresh', 'salad'],
                'features'          => ['Naturally ripened', 'Rich in lycopene', 'Juicy pulp'],
                'stock'             => 120,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Deshi Spicy Green Chillies 250g',
                'slug'              => 'deshi-spicy-green-chillies-250g',
                'sku'               => 'MB-FRUIT-010',
                'category_slug'     => 'fruits-vegetables',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '250g Pack',
                'price'             => 50,
                'sale_price'        => 45,
                'short_desc'        => 'Fiery hot green chillies with fresh stems for daily table seasoning and cooking.',
                'long_desc'         => 'Freshly harvested daily. Delivers crisp texture and fiery heat to bhortas, dals, and fish curries.',
                'tags'              => ['chillies', 'kacha morich', 'spicy', 'vegetables'],
                'features'          => ['Crisp green', 'High heat index', 'Daily fresh'],
                'stock'             => 140,
                'is_featured'       => false,
            ],

            // ==================== 3. Meat & Fish (10 items) ====================
            [
                'title'             => 'Kazi Farms Broiler Chicken (Skin Off) 1kg',
                'slug'              => 'kazi-farms-broiler-chicken-skin-off-1kg',
                'sku'               => 'MB-MEAT-001',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'kazi-farms',
                'brand_name'        => 'Kazi Farms',
                'pack_size'         => '1 kg Pack',
                'price'             => 220,
                'sale_price'        => 210,
                'short_desc'        => '100% Halal broiler chicken, cleaned, skin removed and cut into convenient curry pieces.',
                'long_desc'         => 'Raised on antibiotic-free vegetarian feed. Hygienically processed under cold chain conditions.',
                'tags'              => ['chicken', 'broiler', 'meat', 'halal', 'kazi farms'],
                'features'          => ['100% Halal certified', 'Antibiotic-free feed', 'Hygienic cut'],
                'stock'             => 80,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Kazi Farms Cleaned Deshi Chicken 800g',
                'slug'              => 'kazi-farms-cleaned-deshi-chicken-800g',
                'sku'               => 'MB-MEAT-002',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'kazi-farms',
                'brand_name'        => 'Kazi Farms',
                'pack_size'         => '800g Whole Cleaned',
                'price'             => 460,
                'sale_price'        => 440,
                'short_desc'        => 'Free-range Deshi chicken with firm lean meat and unmatched traditional rich flavor.',
                'long_desc'         => 'Authentic village-raised texture for traditional chicken roast or rich potato curry.',
                'tags'              => ['deshi chicken', 'poultry', 'halal', 'meat'],
                'features'          => ['Firm lean meat', 'Authentic deshi flavor', 'Skin-on dressed'],
                'stock'             => 50,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Fresh Beef Curry Cut Bone-In 1kg',
                'slug'              => 'fresh-beef-curry-cut-bone-in-1kg',
                'sku'               => 'MB-MEAT-003',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Pack',
                'price'             => 780,
                'sale_price'        => 750,
                'short_desc'        => 'Fresh halal beef curry cut with prime bone pieces for flavorful, rich broths.',
                'long_desc'         => 'Expertly butchered fresh local beef. Cleaned of excess fat and portioned into medium curry cuts.',
                'tags'              => ['beef', 'meat', 'halal', 'curry cut'],
                'features'          => ['Halal slaughtered', 'Tender cuts', 'Cleaned & weighed accurately'],
                'stock'             => 70,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Premium Boneless Beef Cubes 1kg',
                'slug'              => 'premium-boneless-beef-cubes-1kg',
                'sku'               => 'MB-MEAT-004',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Pack',
                'price'             => 950,
                'sale_price'        => 920,
                'short_desc'        => 'Lean, tender boneless beef cubes suitable for tehari, steaks, kala bhuna, or skewers.',
                'long_desc'         => 'Zero bone weight. Trimmed of excess fat, succulent cubes that cook tenderly.',
                'tags'              => ['beef', 'boneless', 'kala bhuna', 'meat'],
                'features'          => ['100% boneless', 'Trimmed lean', 'Great for Kala Bhuna'],
                'stock'             => 40,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Mutton Curry Cut Bone-In 1kg',
                'slug'              => 'fresh-mutton-curry-cut-bone-in-1kg',
                'sku'               => 'MB-MEAT-005',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Pack',
                'price'             => 1150,
                'sale_price'        => 1100,
                'short_desc'        => 'Fresh Deshi goat mutton cuts with ribs and shanks for mouthwatering kacchi or rezala.',
                'long_desc'         => 'Tender young goat meat processed according to strict Islamic dietary standards.',
                'tags'              => ['mutton', 'khasir mangsho', 'halal', 'meat'],
                'features'          => ['Deshi goat', 'Tender young cuts', 'Hygienic prep'],
                'stock'             => 35,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Padma River Fresh Hilsa Fish (Ilish) 1kg',
                'slug'              => 'padma-river-fresh-hilsa-fish-1kg',
                'sku'               => 'MB-MEAT-006',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 Piece (approx 1 kg)',
                'price'             => 1650,
                'sale_price'        => 1580,
                'short_desc'        => 'The pride of Bengal — silver Hilsa from the Padma river with rich oily texture.',
                'long_desc'         => 'Unmatched aroma and taste for Shorshe Ilish or Ilish Bhaja. Stored in crushed ice, never frozen stale.',
                'tags'              => ['fish', 'ilish', 'hilsa', 'padma', 'seafood'],
                'features'          => ['Padma River catch', 'Full oily richness', 'Delivered chilled on ice'],
                'stock'             => 30,
                'is_featured'       => true,
                'is_hot_deal'       => true,
            ],
            [
                'title'             => 'Fresh Rui Fish (Rohu) Cleaned 1.5kg',
                'slug'              => 'fresh-rui-fish-cleaned-1-5kg',
                'sku'               => 'MB-MEAT-007',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1.5 kg (Gutted & Descaled)',
                'price'             => 520,
                'sale_price'        => 490,
                'short_desc'        => 'Freshwater Rohu carp fish, descaled, gutted, and cut into uniform steaks.',
                'long_desc'         => 'Sweet freshwater flavor. Firm meat that holds together well in spicy tomato-onion gravies.',
                'tags'              => ['fish', 'rui', 'rohu', 'carp', 'freshwater'],
                'features'          => ['Descaled & gutted', 'Uniform steaks', 'Fresh catch guarantee'],
                'stock'             => 50,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Catla Fish Fresh Cut Pieces 1kg',
                'slug'              => 'catla-fish-fresh-cut-pieces-1kg',
                'sku'               => 'MB-MEAT-008',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg Pack',
                'price'             => 450,
                'sale_price'        => 420,
                'short_desc'        => 'Large river Catla fish cut pieces with thick fleshy steaks and savory belly portions.',
                'long_desc'         => 'Cleaned and ready to marinate with turmeric and chili powder. A family favorite for hearty fish curries.',
                'tags'              => ['fish', 'catla', 'katla', 'seafood'],
                'features'          => ['Thick steaks', 'Low pin bones', 'Daily fresh supply'],
                'stock'             => 45,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Tiger Prawns (Chingri) 500g',
                'slug'              => 'fresh-tiger-prawns-500g',
                'sku'               => 'MB-MEAT-009',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '500g Pack (approx 12-15 pcs)',
                'price'             => 680,
                'sale_price'        => 640,
                'short_desc'        => 'Sweet, juicy tiger prawns for Chingri Malaikari, stir-fries, and biryanis.',
                'long_desc'         => 'Head-on whole prawns with sweet ocean flavor and snappy texture when cooked in coconut cream.',
                'tags'              => ['prawns', 'chingri', 'shrimp', 'malaikari', 'seafood'],
                'features'          => ['Succulent texture', 'Chilled delivery', 'Ideal for Malaikari'],
                'stock'             => 40,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Fresh Tilapia Fish Whole Cleaned 1kg',
                'slug'              => 'fresh-tilapia-fish-whole-cleaned-1kg',
                'sku'               => 'MB-MEAT-010',
                'category_slug'     => 'meat-fish',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 kg (3-4 pcs)',
                'price'             => 240,
                'sale_price'        => 225,
                'short_desc'        => 'Mild, clean-tasting farm-raised Tilapia, scaled and gutted for pan frying.',
                'long_desc'         => 'Budget-friendly, high-protein white fish. Great for deep-frying with mustard paste and onion slices.',
                'tags'              => ['tilapia', 'fish', 'freshwater', 'budget'],
                'features'          => ['Descaled & cleaned', 'Mild taste', 'High protein value'],
                'stock'             => 65,
                'is_featured'       => false,
            ],

            // ==================== 4. Dairy & Eggs (10 items) ====================
            [
                'title'             => 'Aarong Pasteurized Liquid Cow Milk 1L',
                'slug'              => 'aarong-pasteurized-liquid-milk-1l',
                'sku'               => 'MB-DAIRY-001',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'aarong-dairy',
                'brand_name'        => 'Aarong Dairy',
                'pack_size'         => '1 Litre Poly Pack',
                'price'             => 95,
                'sale_price'        => 90,
                'short_desc'        => 'Pure, standardized pasteurized liquid milk from grass-fed cows with natural creaminess.',
                'long_desc'         => 'Microbiologically safe, ready to drink or boil for tea, coffee, puddings, and breakfast cereals.',
                'tags'              => ['milk', 'dairy', 'aarong', 'liquid milk'],
                'features'          => ['Standardized 3.5% fat', 'Pasteurized safety', 'Farm collection'],
                'stock'             => 120,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Milk Vita Pasteurized Fresh Milk 1L',
                'slug'              => 'milk-vita-pasteurized-fresh-milk-1l',
                'sku'               => 'MB-DAIRY-002',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'milk-vita',
                'brand_name'        => 'Milk Vita',
                'pack_size'         => '1 Litre Poly Pack',
                'price'             => 90,
                'sale_price'        => 88,
                'short_desc'        => 'Bangladesh cooperative dairy trusted liquid milk for family nutrition.',
                'long_desc'         => 'Directly sourced from village cooperative farmers. Homogenized and pasteurized for wholesome goodness.',
                'tags'              => ['milk', 'milk vita', 'dairy', 'liquid milk'],
                'features'          => ['Cooperative purity', 'Rich calcium', 'Wholesome taste'],
                'stock'             => 130,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Kazi Farms Fresh Brown Eggs (12 pcs)',
                'slug'              => 'kazi-farms-fresh-brown-eggs-12-pcs',
                'sku'               => 'MB-DAIRY-003',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'kazi-farms',
                'brand_name'        => 'Kazi Farms',
                'pack_size'         => 'Egg Tray (12 Eggs)',
                'price'             => 155,
                'sale_price'        => 148,
                'short_desc'        => 'Farm-fresh graded brown eggs rich in high-quality protein, choline, and Vitamin D.',
                'long_desc'         => 'Produced in modern bio-secure hen farms. Carefully graded and cushioned in safe egg cartons.',
                'tags'              => ['eggs', 'dim', 'kazi farms', 'protein', 'breakfast'],
                'features'          => ['Farm graded', 'Bio-secure poultry', 'High protein'],
                'stock'             => 180,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Aarong Salted Cream Table Butter 200g',
                'slug'              => 'aarong-salted-cream-table-butter-200g',
                'sku'               => 'MB-DAIRY-004',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'aarong-dairy',
                'brand_name'        => 'Aarong Dairy',
                'pack_size'         => '200g Block',
                'price'             => 230,
                'sale_price'        => 215,
                'short_desc'        => 'Creamy churned salted butter for morning toast, baking cookies, and culinary sauces.',
                'long_desc'         => 'Smooth melting texture with a balanced savory touch. Made from 100% pasteurized fresh cow cream.',
                'tags'              => ['butter', 'dairy', 'aarong', 'breakfast'],
                'features'          => ['Churned fresh cream', 'Smooth spread', 'No artificial colors'],
                'stock'             => 90,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Delicious Sweet Curd (Mishti Doi) 500g',
                'slug'              => 'pran-sweet-curd-mishti-doi-500g',
                'sku'               => 'MB-DAIRY-005',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '500g Tub',
                'price'             => 160,
                'sale_price'        => 150,
                'short_desc'        => 'Authentic caramelized sweet dessert yogurt made with caramelized milk and live cultures.',
                'long_desc'         => 'Thick, silky Mishti Doi that evokes the traditional heritage flavor of Bogra curd.',
                'tags'              => ['doi', 'mishti doi', 'yogurt', 'curd', 'pran'],
                'features'          => ['Caramelized sweetness', 'Probiotic cultures', 'Creamy texture'],
                'stock'             => 85,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Milk Vita Pure Deshi Ghee Glass Jar 400g',
                'slug'              => 'milk-vita-pure-deshi-ghee-400g',
                'sku'               => 'MB-DAIRY-006',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'milk-vita',
                'brand_name'        => 'Milk Vita',
                'pack_size'         => '400g Glass Jar',
                'price'             => 620,
                'sale_price'        => 590,
                'short_desc'        => 'Cooperative dairy deshi ghee with an irresistible sweet aroma and granular finish.',
                'long_desc'         => 'Simmered slowly from cultured cow milk butter. The premier choice for festive feasts and parathas.',
                'tags'              => ['ghee', 'milk vita', 'dairy', 'dessert'],
                'features'          => ['Granular Danedar', 'Pure cow milk', 'Long shelf life'],
                'stock'             => 75,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Dano Daily Pushti Full Cream Milk Powder 500g',
                'slug'              => 'dano-daily-pushti-milk-powder-500g',
                'sku'               => 'MB-DAIRY-007',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '500g Foil Pack',
                'price'             => 440,
                'sale_price'        => 425,
                'short_desc'        => 'Full cream instant milk powder fortified with calcium, iron, and essential vitamins.',
                'long_desc'         => 'Dissolves easily in warm water for creamy cups of tea and nutrient-dense shakes for kids.',
                'tags'              => ['milk powder', 'dano', 'dairy', 'tea'],
                'features'          => ['Fortified with Iron & Zinc', 'Quick dissolve', 'Rich creamy taste'],
                'stock'             => 110,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Aarong Fresh Paneer Blocks 200g',
                'slug'              => 'aarong-fresh-paneer-blocks-200g',
                'sku'               => 'MB-DAIRY-008',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'aarong-dairy',
                'brand_name'        => 'Aarong Dairy',
                'pack_size'         => '200g Vacuum Pack',
                'price'             => 190,
                'sale_price'        => 180,
                'short_desc'        => 'Firm, tender cottage cheese paneer blocks that hold their shape in palak paneer or grills.',
                'long_desc'         => 'Made from pasteurized whole milk. High vegetarian protein with zero preservatives.',
                'tags'              => ['paneer', 'cottage cheese', 'aarong', 'vegetarian'],
                'features'          => ['Non-crumbly firm texture', 'Rich in milk protein', 'Vacuum packed'],
                'stock'             => 60,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Processed Cheddar Cheese Slices 200g',
                'slug'              => 'pran-cheddar-cheese-slices-200g',
                'sku'               => 'MB-DAIRY-009',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '10 Individually Wrapped Slices',
                'price'             => 260,
                'sale_price'        => 245,
                'short_desc'        => 'Individually wrapped creamy cheddar slices, melts easily on burgers and toasted sandwiches.',
                'long_desc'         => 'Savory cheesy richness that gives fast-food flair to homemade breakfasts and sandwiches.',
                'tags'              => ['cheese', 'cheddar', 'pran', 'sandwich'],
                'features'          => ['Individually wrapped', 'Smooth melt', 'Kids favorite'],
                'stock'             => 70,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Danish Sweetened Condensed Milk Tin 397g',
                'slug'              => 'danish-sweetened-condensed-milk-397g',
                'sku'               => 'MB-DAIRY-010',
                'category_slug'     => 'dairy-eggs',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '397g Tin Can',
                'price'             => 165,
                'sale_price'        => 155,
                'short_desc'        => 'Thick, sweet condensed milk for coffee, iced teas, shemai, and celebratory desserts.',
                'long_desc'         => 'Velvety sweetness and creamy texture. An indispensable baking and dessert ingredient.',
                'tags'              => ['condensed milk', 'danish', 'dessert', 'sweet'],
                'features'          => ['Thick consistency', 'Pure caramelized sugar', 'Versatile dessert base'],
                'stock'             => 105,
                'is_featured'       => false,
            ],

            // ==================== 5. Snacks & Confectionery (10 items) ====================
            [
                'title'             => 'Pran Bombay Mix Spicy Chanachur 300g',
                'slug'              => 'pran-bombay-mix-spicy-chanachur-300g',
                'sku'               => 'MB-SNACK-001',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '300g Foil Pack',
                'price'             => 85,
                'sale_price'        => 78,
                'short_desc'        => 'Crunchy spicy savory mix with peanuts, fried lentils, and tangy spice blend.',
                'long_desc'         => 'The quintessential evening tea snack in Bangladesh. Crispy bite with an addictive spicy chili kick.',
                'tags'              => ['chanachur', 'snack', 'pran', 'spicy', 'tea time'],
                'features'          => ['Crispy crunch', 'Authentic spices', 'Foil sealed freshness'],
                'stock'             => 140,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Radhuni Special Hot & Sour Chanachur 150g',
                'slug'              => 'radhuni-special-hot-sour-chanachur-150g',
                'sku'               => 'MB-SNACK-002',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'radhuni',
                'brand_name'        => 'Radhuni',
                'pack_size'         => '150g Pack',
                'price'             => 50,
                'sale_price'        => 45,
                'short_desc'        => 'Tangy and hot crispy chanachur seasoned with dried mango powder and black salt.',
                'long_desc'         => 'A tantalizing burst of chatpata flavors that elevates any social gathering or roadside tea break.',
                'tags'              => ['chanachur', 'radhuni', 'hot sour', 'snacks'],
                'features'          => ['Tangy chatpata flavor', 'Roasted nuts', 'No artificial colors'],
                'stock'             => 110,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Potato Crackers Family Pack 100g',
                'slug'              => 'pran-potato-crackers-family-pack-100g',
                'sku'               => 'MB-SNACK-003',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '100g Pack',
                'price'             => 40,
                'sale_price'        => 35,
                'short_desc'        => 'Light, airy, crisp potato crackers with a savory, melt-in-mouth crunch.',
                'long_desc'         => 'Beloved across generations. Made with potato starches and seasoned with a mild peppery touch.',
                'tags'              => ['crackers', 'chips', 'potato', 'pran', 'kids'],
                'features'          => ['Light & crunchy', 'Melt in mouth', 'Convenient family pack'],
                'stock'             => 160,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Lays Classic Salted Potato Chips 50g',
                'slug'              => 'lays-classic-salted-potato-chips-50g',
                'sku'               => 'MB-SNACK-004',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '50g Bag',
                'price'             => 50,
                'sale_price'        => 48,
                'short_desc'        => 'Crispy golden potato chips seasoned with fine sea salt for classic crunch.',
                'long_desc'         => 'Made from farm-grown potatoes, sliced thinly and crisped to golden perfection.',
                'tags'              => ['chips', 'lays', 'potato chips', 'snacks'],
                'features'          => ['Thin cut', 'Pure sea salt', 'Golden crunch'],
                'stock'             => 130,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Oreo Original Vanilla Creme Biscuits 120g',
                'slug'              => 'oreo-original-vanilla-creme-biscuits-120g',
                'sku'               => 'MB-SNACK-005',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '120g Roll Pack',
                'price'             => 65,
                'sale_price'        => 60,
                'short_desc'        => 'Rich cocoa sandwich cookies filled with smooth vanilla creme center.',
                'long_desc'         => 'Twist, lick, dunk! The world favourite cookie that pairs impeccably with cold fresh milk.',
                'tags'              => ['oreo', 'biscuits', 'cookies', 'chocolate', 'vanilla'],
                'features'          => ['Rich dark cocoa', 'Smooth cream filling', 'Perfect with milk'],
                'stock'             => 150,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Bashundhara Crispy Toast Biscuits 350g',
                'slug'              => 'bashundhara-crispy-toast-biscuits-350g',
                'sku'               => 'MB-SNACK-006',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'bashundhara',
                'brand_name'        => 'Bashundhara',
                'pack_size'         => '350g Family Pack',
                'price'             => 90,
                'sale_price'        => 85,
                'short_desc'        => 'Double-baked crispy tea toast biscuits with fragrant fennel seed aroma.',
                'long_desc'         => 'The legendary dipping biscuit for hot milky chai. Golden baked crunch that stays firm.',
                'tags'              => ['toast', 'biscuits', 'bashundhara', 'tea time'],
                'features'          => ['Double baked', 'Fennel seed aroma', 'Crispy tea companion'],
                'stock'             => 120,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Crunchy Dry Cake Rusk 300g',
                'slug'              => 'pran-crunchy-dry-cake-rusk-300g',
                'sku'               => 'MB-SNACK-007',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '300g Pack',
                'price'             => 110,
                'sale_price'        => 100,
                'short_desc'        => 'Golden crispy dry cake slices with rich buttery taste and cake sweetness.',
                'long_desc'         => 'Baked twice to crispy perfection. Combines soft cake flavors with satisfying rusk crunch.',
                'tags'              => ['dry cake', 'rusk', 'pran', 'snacks'],
                'features'          => ['Buttery flavor', 'Satisfying crunch', 'Traditional bakery taste'],
                'stock'             => 95,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Roasted Salted Peanuts 200g',
                'slug'              => 'fresh-roasted-salted-peanuts-200g',
                'sku'               => 'MB-SNACK-008',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '200g Resealable Pack',
                'price'             => 95,
                'sale_price'        => 88,
                'short_desc'        => 'Evenly dry-roasted crunchy peanuts seasoned with rock salt for smart snacking.',
                'long_desc'         => 'High protein, healthy plant fats, and zero cholesterol. Packed in a nitrogen-flushed pouch for long crispness.',
                'tags'              => ['nuts', 'peanuts', 'snack', 'healthy', 'protein'],
                'features'          => ['Dry roasted', 'Lightly salted', 'Resealable freshness pouch'],
                'stock'             => 110,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Nestlé KitKat 4-Finger Milk Chocolate 38g',
                'slug'              => 'nestle-kitkat-4-finger-milk-chocolate-38g',
                'sku'               => 'MB-SNACK-009',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '38g Bar',
                'price'             => 75,
                'sale_price'        => 70,
                'short_desc'        => 'Crisp wafer fingers coated in smooth, creamy milk chocolate. Have a break, have a KitKat.',
                'long_desc'         => 'Deliciously balanced milk chocolate sweetness and light wafer crunch.',
                'tags'              => ['chocolate', 'kitkat', 'nestle', 'snack', 'sweet'],
                'features'          => ['Smooth milk chocolate', 'Light crisp wafer', 'Iconic snap'],
                'stock'             => 160,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Kurkure Masala Munch Crispy Snacks 65g',
                'slug'              => 'kurkure-masala-munch-crispy-snacks-65g',
                'sku'               => 'MB-SNACK-010',
                'category_slug'     => 'snacks-confectionery',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '65g Pouch',
                'price'             => 30,
                'sale_price'        => 28,
                'short_desc'        => 'Tedha par mera crunchy corn puffs coated in fiery Indian masala spice mix.',
                'long_desc'         => 'Crunchy twisted corn and gram puffs with spicy tangy seasonings.',
                'tags'              => ['kurkure', 'masala', 'snacks', 'namkeen'],
                'features'          => ['Zesty masala blend', 'Extra crunchy bite', 'Spicy kick'],
                'stock'             => 150,
                'is_featured'       => false,
            ],

            // ==================== 6. Beverages (10 items) ====================
            [
                'title'             => 'Ispahani Mirzapore Best Leaf Tea 400g',
                'slug'              => 'ispahani-mirzapore-best-leaf-tea-400g',
                'sku'               => 'MB-BEV-001',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'ispahani',
                'brand_name'        => 'Ispahani',
                'pack_size'         => '400g Box',
                'price'             => 235,
                'sale_price'        => 220,
                'short_desc'        => 'Bangladesh finest Ceylon blend black tea leaves with a rich amber liquor and aroma.',
                'long_desc'         => 'Selected from the premier tea estates of Sylhet and Chittagong. Brews a deeply comforting, strong cup of milk tea.',
                'tags'              => ['tea', 'cha', 'ispahani', 'mirzapore', 'beverages'],
                'features'          => ['Strong liquor', 'Natural aroma', 'Finest black tea leaves'],
                'stock'             => 140,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Taaza Black Tea Poly Pack 400g',
                'slug'              => 'taaza-black-tea-poly-pack-400g',
                'sku'               => 'MB-BEV-002',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '400g Pouch',
                'price'             => 220,
                'sale_price'        => 210,
                'short_desc'        => 'Clear brisk black tea packed with tea leaf vitality to refresh your mind and spirit.',
                'long_desc'         => 'Unilever Taaza tea delivers quick brewing strength and invigorating brisk flavor for daily tea lovers.',
                'tags'              => ['tea', 'taaza', 'unilever', 'beverage'],
                'features'          => ['Quick brew strength', 'Refreshing aroma', 'Value poly pack'],
                'stock'             => 110,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Nescafé Classic Instant Coffee Jar 100g',
                'slug'              => 'nescafe-classic-instant-coffee-jar-100g',
                'sku'               => 'MB-BEV-003',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '100g Glass Jar',
                'price'             => 420,
                'sale_price'        => 395,
                'short_desc'        => '100% pure soluble coffee granules made from medium-dark roasted Robusta coffee beans.',
                'long_desc'         => 'Start your mornings with the distinct bold aroma of Nescafé Classic. Great for rich hot lattes or iced cold coffee.',
                'tags'              => ['coffee', 'nescafe', 'instant coffee', 'beverages'],
                'features'          => ['100% pure coffee', 'Rich bold aroma', 'Glass jar freshness seal'],
                'stock'             => 95,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Pran Frooto Mango Fruit Drink 1L',
                'slug'              => 'pran-frooto-mango-fruit-drink-1l',
                'sku'               => 'MB-BEV-004',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '1 Litre PET Bottle',
                'price'             => 95,
                'sale_price'        => 90,
                'short_desc'        => 'Refreshing mango fruit juice drink made from sweet Rajshahi and Chapai mango pulp.',
                'long_desc'         => 'Quench your thirst with sweet fruity mango goodness. Serve chilled on hot tropical afternoons.',
                'tags'              => ['juice', 'mango', 'frooto', 'pran', 'drink'],
                'features'          => ['Real mango pulp', 'Thirst quencher', 'Serve chilled'],
                'stock'             => 130,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Coca-Cola Carbonated Soft Drink 1.5L',
                'slug'              => 'coca-cola-carbonated-soft-drink-1-5l',
                'sku'               => 'MB-BEV-005',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1.5 Litre Bottle',
                'price'             => 110,
                'sale_price'        => 105,
                'short_desc'        => 'The classic sparkling carbonated cola that pairs universally with spicy biryanis and meals.',
                'long_desc'         => 'Crisp effervescence and world-famous cola taste. Best enjoyed icy cold with friends and family.',
                'tags'              => ['coke', 'coca cola', 'soda', 'soft drink', 'beverages'],
                'features'          => ['Crisp carbonation', 'Classic formula', 'Party size bottle'],
                'stock'             => 160,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Sprite Lemon-Lime Soft Drink 1.5L',
                'slug'              => 'sprite-lemon-lime-soft-drink-1-5l',
                'sku'               => 'MB-BEV-006',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1.5 Litre Bottle',
                'price'             => 110,
                'sale_price'        => 105,
                'short_desc'        => 'Clear, crisp lemon and lime flavored carbonated beverage with zero caffeine.',
                'long_desc'         => 'Clean citrus thirst relief with sharp bubbly fizz. Clean taste with no artificial colorings.',
                'tags'              => ['sprite', 'soda', 'lemon', 'beverage', 'drink'],
                'features'          => ['100% natural flavors', 'Caffeine free', 'Clear crisp fizz'],
                'stock'             => 140,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Hamdard Rooh Afza Herbal Syrup 750ml',
                'slug'              => 'hamdard-rooh-afza-herbal-syrup-750ml',
                'sku'               => 'MB-BEV-007',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '750ml Bottle',
                'price'             => 360,
                'sale_price'        => 340,
                'short_desc'        => 'The original rose-flavored cooling herbal syrup for milk sherbet, lassi, and iftar.',
                'long_desc'         => 'Formulated with floral distillates, fruit extracts, and cooling herbs. Legendary refresher during Ramadan.',
                'tags'              => ['rooh afza', 'syrup', 'sherbet', 'ramadan', 'iftar'],
                'features'          => ['Cooling herbal formula', 'Natural floral essences', 'Great with milk or water'],
                'stock'             => 90,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Tang Orange Instant Drink Powder 500g',
                'slug'              => 'tang-orange-instant-drink-powder-500g',
                'sku'               => 'MB-BEV-008',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '500g Pouch',
                'price'             => 380,
                'sale_price'        => 355,
                'short_desc'        => 'Instant orange flavored beverage mix enriched with Vitamin C, A, and B.',
                'long_desc'         => 'Just add water and stir for an instant refreshing glass of zesty citrus juice.',
                'tags'              => ['tang', 'orange', 'instant drink', 'vitamin c'],
                'features'          => ['Loaded with Vitamin C', 'Quick mixing', 'Refreshing fruit taste'],
                'stock'             => 85,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Fresh Drinking Mineral Water Bottle 5L',
                'slug'              => 'fresh-drinking-mineral-water-bottle-5l',
                'sku'               => 'MB-BEV-009',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '5 Litre Container',
                'price'             => 85,
                'sale_price'        => 80,
                'short_desc'        => 'Pure laboratory-tested drinking water purified with multi-stage reverse osmosis and ozonation.',
                'long_desc'         => 'Safe, clean drinking water for your home and office. Balanced TDS level with healthy essential minerals.',
                'tags'              => ['water', 'pani', 'fresh', 'drinking water', 'mineral water'],
                'features'          => ['Reverse Osmosis purified', 'Ozonated safety', 'Easy carry handle'],
                'stock'             => 200,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Speed Carbonated Energy Drink 250ml Can',
                'slug'              => 'speed-carbonated-energy-drink-250ml',
                'sku'               => 'MB-BEV-010',
                'category_slug'     => 'beverages',
                'brand_slug'        => 'akij',
                'brand_name'        => 'Akij',
                'pack_size'         => '250ml Can',
                'price'             => 40,
                'sale_price'        => 38,
                'short_desc'        => 'Sparkling energy beverage formulated with taurine, caffeine, and B-vitamins for instant focus.',
                'long_desc'         => 'Delivers quick stimulation for students, drivers, and athletes. Drink cold for maximum invigorating effect.',
                'tags'              => ['energy drink', 'speed', 'caffeine', 'beverage'],
                'features'          => ['Taurine & Caffeine', 'B-Vitamins', 'Quick boost'],
                'stock'             => 140,
                'is_featured'       => false,
            ],

            // ==================== 7. Bakery & Breakfast (10 items) ====================
            [
                'title'             => 'Bake Delight Premium White Sliced Bread 400g',
                'slug'              => 'bake-delight-white-sliced-bread-400g',
                'sku'               => 'MB-BAKE-001',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '400g Loaf',
                'price'             => 65,
                'sale_price'        => 60,
                'short_desc'        => 'Pillow-soft white sandwich bread slices baked daily for morning breakfast toast.',
                'long_desc'         => 'Evenly cut slices with soft crust and pillowy crumb. Stays fresh and spongy.',
                'tags'              => ['bread', 'pauruti', 'bakery', 'breakfast', 'toast'],
                'features'          => ['Baked fresh daily', 'Soft texture', 'Enriched with milk'],
                'stock'             => 90,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Bake Delight Healthy Multi-Grain Brown Bread 400g',
                'slug'              => 'bake-delight-multi-grain-brown-bread-400g',
                'sku'               => 'MB-BAKE-002',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '400g Loaf',
                'price'             => 85,
                'sale_price'        => 80,
                'short_desc'        => 'Hearty whole wheat multi-grain bread with flaxseeds, oats, and sunflower seeds.',
                'long_desc'         => 'Low glycemic index bread with complex carbs and gut-friendly fiber for health-conscious mornings.',
                'tags'              => ['brown bread', 'multigrain', 'healthy', 'diet', 'bakery'],
                'features'          => ['High dietary fiber', 'Wholesome seeds', 'Low sugar'],
                'stock'             => 70,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Bake Delight Sweet Butter Buns (4 pcs)',
                'slug'              => 'bake-delight-sweet-butter-buns-4-pcs',
                'sku'               => 'MB-BAKE-003',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '4 Pieces Pack',
                'price'             => 60,
                'sale_price'        => 55,
                'short_desc'        => 'Light sweet dinner rolls with butter glaze, perfect for sliders or afternoon tea.',
                'long_desc'         => 'Golden baked sweet buns with a tender crumb. Heat with a dollop of butter for heavenly comfort.',
                'tags'              => ['buns', 'rolls', 'bakery', 'tea snack'],
                'features'          => ['Golden glazed', 'Fluffy interior', 'Pitted butter flavor'],
                'stock'             => 80,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Kelloggs Corn Flakes Original Box 475g',
                'slug'              => 'kelloggs-corn-flakes-original-box-475g',
                'sku'               => 'MB-BAKE-004',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '475g Carton',
                'price'             => 440,
                'sale_price'        => 415,
                'short_desc'        => 'Crispy golden toasted corn flakes enriched with iron and 8 essential vitamins.',
                'long_desc'         => 'The world classic wholesome breakfast cereal. Pour cold or warm milk and top with banana slices.',
                'tags'              => ['corn flakes', 'cereal', 'breakfast', 'kelloggs'],
                'features'          => ['Fat-free grains', 'Fortified with B-vitamins', 'Stays crispy in milk'],
                'stock'             => 85,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Quaker Rolled White Oats Jar 500g',
                'slug'              => 'quaker-rolled-white-oats-jar-500g',
                'sku'               => 'MB-BAKE-005',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '500g Canister',
                'price'             => 320,
                'sale_price'        => 295,
                'short_desc'        => '100% whole grain rolled oats that support heart health and long-lasting morning satiety.',
                'long_desc'         => 'Cooks into warm soothing porridge in 3 minutes. High beta-glucan soluble fiber to help lower cholesterol.',
                'tags'              => ['oats', 'quaker', 'breakfast', 'healthy', 'diet'],
                'features'          => ['100% whole grain', 'Helps lower cholesterol', 'Ready in 3 mins'],
                'stock'             => 95,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pran Real Strawberry Jam Glass Jar 500g',
                'slug'              => 'pran-strawberry-jam-glass-jar-500g',
                'sku'               => 'MB-BAKE-006',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '500g Glass Jar',
                'price'             => 210,
                'sale_price'        => 195,
                'short_desc'        => 'Luscious strawberry fruit spread with real fruit bits, spreads smoothly over toasted bread.',
                'long_desc'         => 'Packed with sweet berry flavour. A breakfast favorite for children and pastry bakers.',
                'tags'              => ['jam', 'strawberry', 'pran', 'breakfast', 'spread'],
                'features'          => ['Real fruit pulp', 'Smooth spreading', 'Glass jar freshness'],
                'stock'             => 90,
                'is_featured'       => false,
            ],
            [
                'title'             => 'American Harvest Creamy Peanut Butter 340g',
                'slug'              => 'american-harvest-creamy-peanut-butter-340g',
                'sku'               => 'MB-BAKE-007',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '340g Jar',
                'price'             => 390,
                'sale_price'        => 365,
                'short_desc'        => 'High-protein creamy peanut butter spread made from freshly roasted select peanuts.',
                'long_desc'         => 'Great for fitness enthusiasts and students. Delicious in oatmeal bowls, smoothies, and toast.',
                'tags'              => ['peanut butter', 'protein', 'fitness', 'breakfast'],
                'features'          => ['Creamy texture', '7g protein per serving', 'No trans fats'],
                'stock'             => 80,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Bake Delight Rich Chocolate Muffins (2 pcs)',
                'slug'              => 'bake-delight-chocolate-muffins-2-pcs',
                'sku'               => 'MB-BAKE-008',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '2 Pieces Pack',
                'price'             => 80,
                'sale_price'        => 75,
                'short_desc'        => 'Decadent chocolate sponge muffins studded with dark chocolate chips.',
                'long_desc'         => 'Moist, rich, and deeply chocolaty. A delightful sweet treat for tea breaks or school lunchboxes.',
                'tags'              => ['muffins', 'cake', 'chocolate', 'bakery'],
                'features'          => ['Dark chocolate chips', 'Moist sponge', 'Individually cased'],
                'stock'             => 75,
                'is_featured'       => false,
            ],
            [
                'title'             => 'All Time Butter Garlic Rusk Toast 300g',
                'slug'              => 'all-time-butter-garlic-rusk-toast-300g',
                'sku'               => 'MB-BAKE-009',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'pran',
                'brand_name'        => 'Pran',
                'pack_size'         => '300g Pack',
                'price'             => 95,
                'sale_price'        => 88,
                'short_desc'        => 'Crispy golden rusks seasoned with aromatic butter and toasted garlic flakes.',
                'long_desc'         => 'A savory twist on classic bakery rusks. Delicious alongside warm tomato soup or evening chai.',
                'tags'              => ['rusk', 'garlic toast', 'pran', 'all time', 'tea'],
                'features'          => ['Savory garlic flavor', 'Extra crunchy', 'Hygienic tray pack'],
                'stock'             => 90,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Betty Crocker Golden Pancake Mix 400g',
                'slug'              => 'betty-crocker-golden-pancake-mix-400g',
                'sku'               => 'MB-BAKE-010',
                'category_slug'     => 'bakery-breakfast',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '400g Box',
                'price'             => 330,
                'sale_price'        => 310,
                'short_desc'        => 'Instant pancake mix for fluffy golden American pancakes in just minutes.',
                'long_desc'         => 'Simply add water or milk and cook on a griddle. Drizzle with honey or maple syrup.',
                'tags'              => ['pancake', 'breakfast', 'baking', 'mix'],
                'features'          => ['Fluffy texture', 'Quick preparation', 'Consistent results'],
                'stock'             => 70,
                'is_featured'       => false,
            ],

            // ==================== 8. Personal Care & Hygiene (10 items) ====================
            [
                'title'             => 'Sunsilk Black Shine Hair Shampoo 375ml',
                'slug'              => 'sunsilk-black-shine-shampoo-375ml',
                'sku'               => 'MB-CARE-001',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '375ml Bottle',
                'price'             => 390,
                'sale_price'        => 365,
                'short_desc'        => 'Co-created with hair experts, enriched with Amla Pearl complex for lustrous black hair.',
                'long_desc'         => 'Revitalizes dull hair and protects against daily sun exposure and urban pollution.',
                'tags'              => ['shampoo', 'sunsilk', 'unilever', 'hair care', 'beauty'],
                'features'          => ['Amla pearl complex', 'Long-lasting shine', 'Deep cleansing'],
                'stock'             => 110,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Lifebuoy Total Germ Protection Soap 100g (Buy 3 Get 1)',
                'slug'              => 'lifebuoy-total-soap-4-pack',
                'sku'               => 'MB-CARE-002',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '4 x 100g Bar Pack',
                'price'             => 175,
                'sale_price'        => 165,
                'short_desc'        => 'Advanced Silver Shield formula provides 100% stronger germ protection for the entire family.',
                'long_desc'         => 'Fights against 99.9% illness-causing germs. Leaves your skin feeling invigorated and clean.',
                'tags'              => ['soap', 'lifebuoy', 'germ protection', 'hygiene'],
                'features'          => ['Active Silver Shield', 'Value 4-pack', 'Full family protection'],
                'stock'             => 160,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Dettol Original Liquid Handwash Pump 250ml',
                'slug'              => 'dettol-original-liquid-handwash-250ml',
                'sku'               => 'MB-CARE-003',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'dettol',
                'brand_name'        => 'Dettol',
                'pack_size'         => '250ml Pump Bottle',
                'price'             => 155,
                'sale_price'        => 145,
                'short_desc'        => 'Antiseptic germ-fighting liquid hand soap with pine fragrance for everyday hand hygiene.',
                'long_desc'         => 'Dettol trusted protection against bacteria and viruses. Moisture-rich formula protects hands from drying.',
                'tags'              => ['dettol', 'handwash', 'hygiene', 'antiseptic'],
                'features'          => ['Kills 99.9% germs', 'Pine fresh scent', 'Easy pump dispenser'],
                'stock'             => 130,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Close Up Everfresh Red Hot Toothpaste 150g',
                'slug'              => 'close-up-everfresh-red-hot-toothpaste-150g',
                'sku'               => 'MB-CARE-004',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '150g Tube',
                'price'             => 140,
                'sale_price'        => 130,
                'short_desc'        => 'Anti-bacterial zinc mouthwash formula with spicy clove and wintergreen for 12h fresh breath.',
                'long_desc'         => 'Cleans away dental plaque, polishes tooth enamel, and delivers intense freshness confidence.',
                'tags'              => ['toothpaste', 'closeup', 'oral care', 'unilever'],
                'features'          => ['12-hour fresh breath', 'Zinc mouthwash gel', 'Deep tooth cleaning'],
                'stock'             => 140,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Parachute Advansed Pure Coconut Hair Oil 300ml',
                'slug'              => 'parachute-pure-coconut-hair-oil-300ml',
                'sku'               => 'MB-CARE-005',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'parachute',
                'brand_name'        => 'Parachute',
                'pack_size'         => '300ml Bottle',
                'price'             => 250,
                'sale_price'        => 235,
                'short_desc'        => '100% pure coconut hair oil that penetrates 10 layers deep to nourish and strengthen roots.',
                'long_desc'         => 'Extracted from sun-dried copra coconuts. Reduces hair breakage, fights dandruff, and promotes growth.',
                'tags'              => ['hair oil', 'coconut oil', 'parachute', 'hair care'],
                'features'          => ['10 layers deep nourishment', 'Pure coconut extract', 'Tamper-proof seal'],
                'stock'             => 120,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Vaseline Intensive Care Deep Restore Body Lotion 400ml',
                'slug'              => 'vaseline-deep-restore-body-lotion-400ml',
                'sku'               => 'MB-CARE-006',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '400g Pump Bottle',
                'price'             => 490,
                'sale_price'        => 460,
                'short_desc'        => 'Clinically proven body moisturizer infused with micro-droplets of Vaseline petroleum jelly.',
                'long_desc'         => 'Heals dry winter skin from first application without sticky or greasy residue.',
                'tags'              => ['lotion', 'vaseline', 'skin care', 'moisturizer'],
                'features'          => ['Micro-droplets of jelly', 'Non-greasy absorption', '48h hydration'],
                'stock'             => 85,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Garnier Men Acno Fight Anti-Pimple Face Wash 100g',
                'slug'              => 'garnier-men-acno-fight-face-wash-100g',
                'sku'               => 'MB-CARE-007',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '100g Tube',
                'price'             => 290,
                'sale_price'        => 270,
                'short_desc'        => '6-in-1 anti-pimple cooling face wash with salicylic acid and natural HerbaRepair.',
                'long_desc'         => 'Eliminates excess oil, unclogs pores, clears pimples, and controls blackheads.',
                'tags'              => ['face wash', 'garnier', 'men care', 'acne fight'],
                'features'          => ['Fights 99.9% pimple germs', 'Micro-beads scrub', 'Oil control'],
                'stock'             => 95,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Gillette Fusion Shaving Foam Sensitive 200ml',
                'slug'              => 'gillette-fusion-shaving-foam-sensitive-200ml',
                'sku'               => 'MB-CARE-008',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '200ml Can',
                'price'             => 340,
                'sale_price'        => 320,
                'short_desc'        => 'Rich lubricating foam with aloe vera soothing agents for an ultra-smooth glide shave.',
                'long_desc'         => 'Shields sensitive facial skin from razor burn, cuts, and irritation.',
                'tags'              => ['shaving', 'gillette', 'foam', 'men grooming'],
                'features'          => ['Aloe soothing', 'Ultra-glide shield', 'Dermatologist tested'],
                'stock'             => 75,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Meril Splash Instant Antiseptic Hand Sanitizer 100ml',
                'slug'              => 'meril-splash-hand-sanitizer-100ml',
                'sku'               => 'MB-CARE-009',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'square',
                'brand_name'        => 'Square',
                'pack_size'         => '100ml Pocket Bottle',
                'price'             => 90,
                'sale_price'        => 80,
                'short_desc'        => '70% alcohol-based instant antiseptic hand rub with moisturizing aloe vera.',
                'long_desc'         => 'Rinse-free protection when traveling, commuting on public transport, or before meals.',
                'tags'              => ['sanitizer', 'meril', 'square', 'hygiene'],
                'features'          => ['70% Isopropyl alcohol', 'Kills germs in 15 seconds', 'Pocket friendly'],
                'stock'             => 140,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Nivea Men Fresh Active Deodorant Spray 150ml',
                'slug'              => 'nivea-men-fresh-active-deodorant-150ml',
                'sku'               => 'MB-CARE-010',
                'category_slug'     => 'personal-care-hygiene',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '150ml Aerosol Spray',
                'price'             => 380,
                'sale_price'        => 350,
                'short_desc'        => '48-hour ocean-fresh deodorant spray with invigorating marine extracts.',
                'long_desc'         => 'Provides reliable all-day odor protection and masculine freshness confidence.',
                'tags'              => ['deodorant', 'body spray', 'nivea', 'fragrance'],
                'features'          => ['48h odor protection', 'Ocean extracts', 'Zero aluminum chlorohydrate'],
                'stock'             => 80,
                'is_featured'       => false,
            ],

            // ==================== 9. Household & Cleaning (10 items) ====================
            [
                'title'             => 'Wheel 2in1 Washing Powder Lemon & Jasmine 1kg',
                'slug'              => 'wheel-2in1-washing-powder-lemon-1kg',
                'sku'               => 'MB-HOME-001',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '1 kg Poly Pack',
                'price'             => 140,
                'sale_price'        => 130,
                'short_desc'        => 'Power stain-removal detergent powder infused with natural lemon and jasmine freshness.',
                'long_desc'         => 'Penetrates tough fabric collar stains and mud marks, leaving clothes bright and fragrant.',
                'tags'              => ['detergent', 'wheel', 'washing powder', 'laundry'],
                'features'          => ['Brightening formula', 'Lemon grease cutter', 'Gentle on hands'],
                'stock'             => 180,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Vim Dishwash Liquid Lemon Concentrate 500ml',
                'slug'              => 'vim-dishwash-liquid-lemon-500ml',
                'sku'               => 'MB-HOME-002',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '500ml Squeeze Bottle',
                'price'             => 130,
                'sale_price'        => 120,
                'short_desc'        => 'One spoonful cuts through greasy oil on utensils, leaving squeaky clean shine.',
                'long_desc'         => 'Concentrated lemon juice gel. Removes stubborn burnt food residue without scratching pots.',
                'tags'              => ['vim', 'dishwash', 'cleaning', 'kitchen'],
                'features'          => ['Power of 100 lemons', 'Squeaky clean shine', 'Gentle formula'],
                'stock'             => 150,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Harpic Power Plus Disinfectant Toilet Cleaner 750ml',
                'slug'              => 'harpic-power-plus-toilet-cleaner-750ml',
                'sku'               => 'MB-HOME-003',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'dettol',
                'brand_name'        => 'Dettol',
                'pack_size'         => '750ml Angled Bottle',
                'price'             => 175,
                'sale_price'        => 165,
                'short_desc'        => 'Thick hydrochloric acid gel dissolves 100% limescale and kills 99.9% toilet germs.',
                'long_desc'         => 'Angled nozzle fits under toilet rim for maximum coverage and stain removal.',
                'tags'              => ['harpic', 'toilet cleaner', 'cleaning', 'disinfectant'],
                'features'          => ['10X better stain removal', 'Kills 99.9% germs', 'Angled nozzle neck'],
                'stock'             => 130,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Lysol Multi-Surface Floor Cleaner Citrus 500ml',
                'slug'              => 'lysol-floor-cleaner-citrus-500ml',
                'sku'               => 'MB-HOME-004',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'dettol',
                'brand_name'        => 'Dettol',
                'pack_size'         => '500ml Bottle',
                'price'             => 220,
                'sale_price'        => 205,
                'short_desc'        => 'Hospital-grade disinfectant surface cleaner for sparkling tile and marble floors.',
                'long_desc'         => 'Deodorizes and sanitizes home floors where babies crawl and pets walk.',
                'tags'              => ['lysol', 'floor cleaner', 'disinfectant', 'hygiene'],
                'features'          => ['Hospital grade disinfection', 'Citrus freshness', 'Safe on tiles and marble'],
                'stock'             => 90,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Hit Powerful Mosquito & Fly Aerosol Spray 400ml',
                'slug'              => 'hit-mosquito-fly-spray-400ml',
                'sku'               => 'MB-HOME-005',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'aci-pure',
                'brand_name'        => 'ACI Pure',
                'pack_size'         => '400ml Canister',
                'price'             => 310,
                'sale_price'        => 290,
                'short_desc'        => 'Instant knockdown aerosol insecticide to eradicate dangerous dengue and malaria mosquitoes.',
                'long_desc'         => 'Targeted spray nozzle reaches hidden corners behind curtains and under furniture.',
                'tags'              => ['hit', 'mosquito spray', 'pest control', 'insecticide'],
                'features'          => ['Instant knockdown', 'Pleasant fresh fragrance', 'Protects against Dengue'],
                'stock'             => 110,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Air Wick Automatic Room Air Freshener Lavender 250ml',
                'slug'              => 'air-wick-automatic-air-freshener-lavender-250ml',
                'sku'               => 'MB-HOME-006',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'dettol',
                'brand_name'        => 'Dettol',
                'pack_size'         => '250ml Refill Can',
                'price'             => 360,
                'sale_price'        => 340,
                'short_desc'        => 'Continuous soothing lavender blossom bursts that neutralize room odors up to 60 days.',
                'long_desc'         => 'Infused with natural essential oils to transform your living room and bathrooms into relaxing spaces.',
                'tags'              => ['air freshener', 'air wick', 'lavender', 'home fragrance'],
                'features'          => ['Up to 60 days fragrance', 'Essential oil blend', 'Odor neutralization'],
                'stock'             => 75,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Bashundhara Premium Facial Tissue Box (150 x 2 Ply)',
                'slug'              => 'bashundhara-facial-tissue-box-150-sheets',
                'sku'               => 'MB-HOME-007',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'bashundhara',
                'brand_name'        => 'Bashundhara',
                'pack_size'         => '150 Pulls (2 Ply)',
                'price'             => 85,
                'sale_price'        => 80,
                'short_desc'        => '100% virgin pulp ultra-soft absorbent facial tissues for everyday cosmetics and dining.',
                'long_desc'         => 'Hypoallergenic and chlorine-free bleached paper. Gentle on delicate skin.',
                'tags'              => ['tissue', 'facial tissue', 'bashundhara', 'paper'],
                'features'          => ['100% virgin wood pulp', '2-ply strength', 'Lint free'],
                'stock'             => 200,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Bashundhara Luxury Toilet Tissue Roll (4 Pack)',
                'slug'              => 'bashundhara-toilet-tissue-4-pack',
                'sku'               => 'MB-HOME-008',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'bashundhara',
                'brand_name'        => 'Bashundhara',
                'pack_size'         => '4 Rolls Pack',
                'price'             => 110,
                'sale_price'        => 100,
                'short_desc'        => 'Embossed soft quilted toilet rolls that flush easily without plumbing clogs.',
                'long_desc'         => 'Thick, absorbent sheets that provide maximum comfort and hygienic sanitation.',
                'tags'              => ['toilet tissue', 'tissue roll', 'bashundhara', 'bathroom'],
                'features'          => ['Septic safe flush', 'Quilted softness', 'Economical 4-pack'],
                'stock'             => 150,
                'is_featured'       => false,
            ],
            [
                'title'             => 'CleanGuard Heavy Duty Garbage Trash Bags (30 pcs)',
                'slug'              => 'cleanguard-heavy-duty-trash-bags-30-pcs',
                'sku'               => 'MB-HOME-009',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => 'Roll of 30 Bags (Medium 24x30 in)',
                'price'             => 180,
                'sale_price'        => 165,
                'short_desc'        => 'Tear-resistant leak-proof plastic garbage waste bags with tie-strings.',
                'long_desc'         => 'Heavy-duty puncture-resistant polymer material keeps your kitchen and office bins clean.',
                'tags'              => ['trash bags', 'garbage bags', 'cleaning', 'waste management'],
                'features'          => ['Leak proof bottom seal', 'Puncture resistant', 'Drawstring closure'],
                'stock'             => 85,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Scotch-Brite Heavy Duty Steel Wool Scrubber (3 Pack)',
                'slug'              => 'scotch-brite-steel-wool-scrubber-3-pack',
                'sku'               => 'MB-HOME-010',
                'category_slug'     => 'household-cleaning',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '3 Scrubbers Pack',
                'price'             => 75,
                'sale_price'        => 70,
                'short_desc'        => 'Stainless steel coils for scouring burnt pans, kadai grease, and cast iron cookware.',
                'long_desc'         => 'Rust-resistant metal coils that maintain scrubbing power without splintering into hands.',
                'tags'              => ['scrubber', 'steel wool', 'scotch brite', 'kitchen cleaning'],
                'features'          => ['Rust resistant stainless steel', 'Cuts burnt grease', 'Long lasting'],
                'stock'             => 110,
                'is_featured'       => false,
            ],

            // ==================== 10. Baby Care & Maternity (10 items) ====================
            [
                'title'             => 'Pampers Baby Dry Diaper Pants Large (54 pcs)',
                'slug'              => 'pampers-baby-dry-diaper-pants-large-54-pcs',
                'sku'               => 'MB-BABY-001',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => 'Jumbo Pack (54 Diapers, 9-14 kg)',
                'price'             => 1450,
                'sale_price'        => 1380,
                'short_desc'        => 'Up to 12 hours of overnight leakage protection with breathable air channels and magic gel.',
                'long_desc'         => 'Easy pull-up pants design with stretchy waistband. Keeps infant skin dry, cool, and rash-free all night.',
                'tags'              => ['pampers', 'diapers', 'baby care', 'overnight dry'],
                'features'          => ['12h leak protection', 'Magic gel core', 'Soft breathable cottony feel'],
                'stock'             => 80,
                'is_featured'       => true,
                'is_best_seller'    => true,
            ],
            [
                'title'             => 'Huggies Pure & Natural Wet Baby Wipes (80 Sheets)',
                'slug'              => 'huggies-pure-natural-baby-wipes-80-sheets',
                'sku'               => 'MB-BABY-002',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '80 Sheets Flip-Top Pack',
                'price'             => 220,
                'sale_price'        => 200,
                'short_desc'        => '99% pure water wipes with organic aloe extracts, alcohol-free and fragrance-free.',
                'long_desc'         => 'Dermatologist approved for newborn sensitive skin. Thick embossed cloth for quick diaper changes.',
                'tags'              => ['baby wipes', 'huggies', 'baby care', 'sensitive skin'],
                'features'          => ['99% purified water', 'Hypoallergenic', 'Flip-top moisture seal lid'],
                'stock'             => 120,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Nestlé Cerelac Wheat & Apple with Milk 400g',
                'slug'              => 'nestle-cerelac-wheat-apple-milk-400g',
                'sku'               => 'MB-BABY-003',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'nestle',
                'brand_name'        => 'Nestlé',
                'pack_size'         => '400g Metal Tin',
                'price'             => 410,
                'sale_price'        => 395,
                'short_desc'        => 'Fortified infant baby cereal with milk, real apple flakes, iron, and Bifidus BL probiotics.',
                'long_desc'         => 'Nutritious weaning food for infants aged 6 months and older. Delivers more than 50% daily iron in two servings.',
                'tags'              => ['cerelac', 'nestle', 'baby food', 'infant cereal'],
                'features'          => ['Iron fortified', 'Bifidus BL probiotics', 'Delicious natural apple'],
                'stock'             => 90,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Johnsons No More Tears Baby Shampoo 200ml',
                'slug'              => 'johnsons-no-more-tears-baby-shampoo-200ml',
                'sku'               => 'MB-BABY-004',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '200ml Bottle',
                'price'             => 360,
                'sale_price'        => 335,
                'short_desc'        => 'Hypoallergenic tear-free baby hair cleanser that leaves baby locks soft, shiny, and fresh.',
                'long_desc'         => 'As gentle to eyes as pure water. Free of parabens, phthalates, and sulfates.',
                'tags'              => ['baby shampoo', 'johnsons', 'baby bath', 'tear free'],
                'features'          => ['No More Tears formula', 'Pediatrician tested', 'Mild baby scent'],
                'stock'             => 95,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Johnsons Gentle Baby Bedtime Lotion 200ml',
                'slug'              => 'johnsons-gentle-baby-bedtime-lotion-200ml',
                'sku'               => 'MB-BABY-005',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '200ml Bottle',
                'price'             => 380,
                'sale_price'        => 355,
                'short_desc'        => 'Infused with NaturalCalm aromas to help soothe and calm baby before peaceful nighttime sleep.',
                'long_desc'         => 'Nourishing 24-hour hydration that locks moisture into delicate infant skin without stickiness.',
                'tags'              => ['baby lotion', 'bedtime', 'johnsons', 'skincare'],
                'features'          => ['NaturalCalm aroma', 'Clinically mildness proven', '24h moisture barrier'],
                'stock'             => 80,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Meril Baby Powder Soft & Gentle 100g',
                'slug'              => 'meril-baby-powder-soft-gentle-100g',
                'sku'               => 'MB-BABY-006',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'square',
                'brand_name'        => 'Square',
                'pack_size'         => '100g Sprinkler Bottle',
                'price'             => 130,
                'sale_price'        => 120,
                'short_desc'        => 'Sterilized gentle talcum powder enriched with zinc oxide to protect against prickly heat and chafing.',
                'long_desc'         => 'Keeps baby delicate folds dry, cool, and comfortable throughout hot and humid Bangladesh seasons.',
                'tags'              => ['baby powder', 'meril', 'square', 'prickly heat'],
                'features'          => ['Sterilized talc', 'Zinc oxide barrier', 'Gentle floral fragrance'],
                'stock'             => 110,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pigeon Anti-Colic Wide-Neck Baby Feeding Bottle 240ml',
                'slug'              => 'pigeon-anti-colic-baby-feeding-bottle-240ml',
                'sku'               => 'MB-BABY-007',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '240ml Bottle (0m+)',
                'price'             => 520,
                'sale_price'        => 490,
                'short_desc'        => 'BPA-free PPSU medical polymer feeding bottle with peristaltic anti-colic silicone teat.',
                'long_desc'         => 'Simulates natural breastfeeding latch to reduce gas ingestion and painful infant colic.',
                'tags'              => ['feeding bottle', 'baby bottle', 'pigeon', 'anti colic'],
                'features'          => ['BPA & BPS free', 'Anti-colic air valve', 'Peristaltic silicone nipple'],
                'stock'             => 50,
                'is_featured'       => true,
            ],
            [
                'title'             => 'Johnsons Pure Baby Oil with Aloe Vera 200ml',
                'slug'              => 'johnsons-pure-baby-oil-aloe-vera-200ml',
                'sku'               => 'MB-BABY-008',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'unilever',
                'brand_name'        => 'Unilever',
                'pack_size'         => '200ml Bottle',
                'price'             => 370,
                'sale_price'        => 345,
                'short_desc'        => 'Locks in up to 10 times more moisture on wet skin for nourishing massage time.',
                'long_desc'         => 'Gentle mineral oil infused with soothing aloe vera. Dermatologist approved for bonding baby massages.',
                'tags'              => ['baby oil', 'massage', 'johnsons', 'aloe vera'],
                'features'          => ['10X moisture lock', 'With soothing Aloe Vera', 'Ideal for baby massage'],
                'stock'             => 85,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Pigeon Silicone Soothing Teething Ring BPA Free',
                'slug'              => 'pigeon-silicone-soothing-teething-ring',
                'sku'               => 'MB-BABY-009',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'fresh',
                'brand_name'        => 'Fresh',
                'pack_size'         => '1 Teether (3m+)',
                'price'             => 260,
                'sale_price'        => 240,
                'short_desc'        => 'Textured food-grade silicone teether ring that massages swollen tender gums.',
                'long_desc'         => 'Easy-grip handle for tiny infant hands. Can be chilled in refrigerator for extra soothing gum comfort.',
                'tags'              => ['teether', 'baby toys', 'pigeon', 'teething'],
                'features'          => ['100% food grade silicone', 'Textured gum massage', 'Easy grip ring'],
                'stock'             => 60,
                'is_featured'       => false,
            ],
            [
                'title'             => 'Meril 100% Pure Organic Cotton Buds (100 Tips)',
                'slug'              => 'meril-pure-cotton-buds-100-tips',
                'sku'               => 'MB-BABY-010',
                'category_slug'     => 'baby-care-maternity',
                'brand_slug'        => 'square',
                'brand_name'        => 'Square',
                'pack_size'         => '100 Tips Container',
                'price'             => 65,
                'sale_price'        => 60,
                'short_desc'        => 'Micro-processed pure cotton tips with flexible paper stems for safe outer ear and navel hygiene.',
                'long_desc'         => 'Lint-free sterilized cotton tips. Safe and gentle for infant skin care and makeup touch-ups.',
                'tags'              => ['cotton buds', 'meril', 'baby hygiene', 'square'],
                'features'          => ['100% pure organic cotton', 'Flexible paper stems', 'Sterilized tips'],
                'stock'             => 170,
                'is_featured'       => false,
            ],
        ];
    }
}
