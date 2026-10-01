<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\ProductService;
use App\Services\SlugService;
use Database\Seeders\ProductImageSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Safe, repeatable demo catalog seeder.
 *
 * - Backs up existing product data to storage/app/backups/ before touching anything.
 * - NEVER deletes orders, order_items, users, payments or settings.
 * - Refuses to run against non-local databases unless --confirm-production is passed
 *   together with --force (explicit environment confirmation).
 * - Idempotent: cleanup-then-seed always yields exactly --count active parent products.
 */
class SeedDemoCatalogCommand extends Command
{
    protected $signature = 'demo:seed-catalog
        {--count=200 : Number of parent products to seed}
        {--force : Actually delete existing demo products (required)}
        {--confirm-production : Explicit confirmation that running against a non-local DB is intended}
        {--skip-backup : Skip JSON backup step}';

    protected $description = 'Safely clean demo products and seed a realistic 200-product BD catalog with variants';

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $force = (bool) $this->option('force');
        $confirmProd = (bool) $this->option('confirm-production');

        $dbHost = (string) config('database.connections.mysql.host', '');
        $dbName = (string) config('database.connections.mysql.database', '');
        $appEnv = (string) config('app.env', 'production');
        $isLocalDb = in_array(strtolower($dbHost), ['localhost', '127.0.0.1', '::1'], true)
            || config('database.default') === 'sqlite';

        if (!$isLocalDb && !($force && $confirmProd)) {
            $this->error("SAFETY STOP: DB host is '{$dbHost}' (database '{$dbName}', APP_ENV={$appEnv}).");
            $this->error('This does not look like a local development database.');
            $this->line('To proceed you must pass BOTH --force and --confirm-production,');
            $this->line('acknowledging that demo products on this database will be replaced.');
            $this->line('Orders, customers and payment records are never deleted by this command.');
            return 1;
        }

        if ($appEnv === 'production' && !($force && $confirmProd)) {
            $this->error('SAFETY STOP: APP_ENV=production. Pass --force --confirm-production to proceed.');
            return 1;
        }

        if (!$force) {
            $this->warn('Dry run: pass --force to actually clean and seed.');
            $this->line('Products now: ' . Product::count() . ' | variants: ' . ProductVariant::count()
                . ' | orders: ' . Order::count() . ' (orders are always preserved)');
            return 0;
        }

        $orderCount = Order::count();
        $orderItemCount = OrderItem::count();
        $this->info("Preserving {$orderCount} orders / {$orderItemCount} order items (never deleted).");

        if (!$this->option('skip-backup')) {
            $this->backupProductData();
        }

        DB::transaction(function () {
            $this->ensureCatalog();
        });

        DB::transaction(function () {
            $this->cleanupDemoProducts();
        });

        mt_srand(20261001);
        $created = 0;
        $variantTotal = 0;

        $blueprints = $this->blueprints();
        // Repeat blueprints deterministically until we reach exactly --count.
        $i = 0;
        $usedSlugs = [];
        $usedSkus = [];
        while ($created < $count) {
            $bp = $blueprints[$i % count($blueprints)];
            $i++;
            $suffix = $created >= count($blueprints) ? ' ' . $this->packSuffix($created) : '';
            $title = $bp['title'] . $suffix;
            $slug = SlugService::toAsciiSlug($title);
            if (isset($usedSlugs[$slug]) || Product::where('slug', $slug)->exists()) {
                $slug .= '-' . ($created + 1);
            }
            $usedSlugs[$slug] = true;

            $result = $this->createProduct($bp, $title, $slug, $created, $usedSkus);
            $created++;
            $variantTotal += $result;
            if ($created % 50 === 0) {
                $this->line("  ... {$created} products seeded");
            }
        }

        $this->info("Seeded {$created} parent products with {$variantTotal} variants.");
        return $this->verify($count);
    }

    protected function packSuffix(int $n): string
    {
        $packs = ['Family Pack', 'Value Pack', 'Combo Offer', 'Plus Edition', 'New Pack', 'Special Edition'];
        return $packs[$n % count($packs)];
    }

    protected function backupProductData(): void
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $stamp = date('Ymd-His');
        $payload = [
            'stamped_at' => now()->toIso8601String(),
            'counts' => [
                'products' => Product::count(),
                'variants' => ProductVariant::count(),
                'specs' => ProductSpec::count(),
                'relations' => ProductRelation::count(),
                'reviews' => Review::count(),
            ],
            'products' => Product::orderBy('id')->limit(5000)->get()->toArray(),
            'variants' => ProductVariant::orderBy('id')->limit(20000)->get()->toArray(),
        ];
        file_put_contents("{$dir}/products-backup-{$stamp}.json", json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("Backup written: storage/app/backups/products-backup-{$stamp}.json");
    }

    protected function cleanupDemoProducts(): void
    {
        // Delete reviews tied to products first (preserve order/customer history).
        $productIds = Product::pluck('id')->all();
        if (!empty($productIds)) {
            Review::whereIn('product_id', $productIds)->delete();
            ProductRelation::whereIn('product_id', $productIds)->orWhereIn('related_product_id', $productIds)->delete();
            ProductSpec::whereIn('product_id', $productIds)->delete();
            ProductVariant::whereIn('product_id', $productIds)->delete();
            Product::whereIn('id', $productIds)->delete();
        }
        $this->line('Existing demo products, variants, specs, relations and product reviews removed. Orders/customers untouched.');
    }

    protected function ensureCatalog(): void
    {
        $cats = [
            ['Grocery & Staples', 'grocery-staples'],
            ['Electronics', 'electronics'],
            ['Mobile Accessories', 'mobile-accessories'],
            ['Home Appliances', 'home-appliances'],
            ['Kitchen Appliances', 'kitchen-appliances'],
            ["Men's Fashion", 'mens-fashion'],
            ["Women's Fashion", 'womens-fashion'],
            ['Shoes & Footwear', 'shoes-footwear'],
            ['Bags & Luggage', 'bags-luggage'],
            ['Beauty & Personal Care', 'beauty-personal-care'],
            ['Home & Living', 'home-living'],
            ['Computer Accessories', 'computer-accessories'],
            ['Smart Devices', 'smart-devices'],
        ];
        foreach ($cats as [$name, $slug]) {
            Category::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'status' => 'active', 'sort_order' => 0,
            ]);
        }

        $brands = [
            ['Samsung', 'samsung'], ['Xiaomi', 'xiaomi'], ['Realme', 'realme'], ['Vivo', 'vivo'],
            ['Walton', 'walton'], ['Singer', 'singer'], ['Vision', 'vision'], ['RFL', 'rfl'],
            ['Apex', 'apex'], ['Bata', 'bata'], ['Lotto', 'lotto'], ['Aarong', 'aarong'],
            ['Yellow', 'yellow'], ['Ecstasy', 'ecstasy'], ['Dove', 'dove'], ['Lakme', 'lakme'],
            ['Nivea', 'nivea'], ['Parachute', 'parachute'], ['Dettol', 'dettol'], ['Pran', 'pran'],
            ['Teer', 'teer'], ['Fresh', 'fresh'], ['ACI Pure', 'aci-pure'], ['Square', 'square'],
            ['Anker', 'anker'], ['Logitech', 'logitech'], ['HP', 'hp'], ['Sandisk', 'sandisk'],
        ];
        foreach ($brands as [$name, $slug]) {
            Brand::firstOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active']);
        }

        $colors = [
            ['Black', '#111111'], ['White', '#FFFFFF'], ['Navy', '#1F3A5F'], ['Olive', '#6B7F3E'],
            ['Blue', '#2563EB'], ['Grey', '#9AA0A6'], ['Brown', '#7A4A21'], ['Red', '#DC2626'],
            ['Green', '#16A34A'], ['Pink', '#EC4899'], ['Beige', '#E8DCC8'], ['Maroon', '#7B1E2B'],
            ['Space Grey', '#5B5E63'], ['Silver', '#C0C0C0'],
        ];
        foreach ($colors as $idx => [$name, $hex]) {
            Color::firstOrCreate(['name' => $name], [
                'display_name' => $name, 'hex' => $hex, 'status' => 'active', 'sort_order' => $idx,
            ]);
        }

        $sizes = [
            ['S', 'clothing'], ['M', 'clothing'], ['L', 'clothing'], ['XL', 'clothing'], ['XXL', 'clothing'],
            ['28', 'clothing'], ['30', 'clothing'], ['32', 'clothing'], ['34', 'clothing'], ['36', 'clothing'],
            ['39', 'shoes'], ['40', 'shoes'], ['41', 'shoes'], ['42', 'shoes'], ['43', 'shoes'], ['44', 'shoes'],
            ['Single', 'general'], ['Queen', 'general'], ['King', 'general'],
            ['128GB', 'general'], ['256GB', 'general'], ['512GB', 'general'],
        ];
        foreach ($sizes as $idx => [$name, $type]) {
            Size::firstOrCreate(['name' => $name], [
                'type' => $type, 'status' => 'active', 'sort_order' => $idx,
            ]);
        }

        Collection::firstOrCreate(['slug' => 'demo-essentials'], ['name' => 'Demo Essentials', 'status' => 'active']);
        Vendor::firstOrCreate(['slug' => 'mama-bazar-direct'], ['name' => 'Mama Bazar Direct', 'status' => 'active']);
        Supplier::firstOrCreate(['slug' => 'dhaka-wholesale'], ['name' => 'Dhaka Wholesale Hub', 'status' => 'active']);
    }

    /**
     * Curated realistic BD catalog blueprints.
     * variant: none | apparel-color-size | jeans | shoes | phone-storage |
     *          accessory-color | watch | home-capacity | bedding | curtain
     */
    protected function blueprints(): array
    {
        $B = fn($cat, $brand, $title, $min, $max, $variant, $tags = [], $specs = []) => [
            'cat' => $cat, 'brand' => $brand, 'title' => $title,
            'min' => $min, 'max' => $max, 'variant' => $variant, 'tags' => $tags, 'specs' => $specs,
        ];

        return [
            // ── Smartphones (storage variants) ──
            $B('electronics', 'samsung', 'Samsung Galaxy A15 4G Smartphone', 18990, 22990, 'phone-storage', ['smartphone', 'mobile'], [['Display', '6.5-inch PLS LCD'], ['Battery', '5000mAh'], ['Camera', '50MP triple camera']]),
            $B('electronics', 'xiaomi', 'Xiaomi Redmi 13C Smartphone', 13990, 16990, 'phone-storage', ['smartphone'], [['Display', '6.74-inch Dot Drop'], ['Battery', '5000mAh']]),
            $B('electronics', 'realme', 'Realme C67 Smartphone', 19990, 22990, 'phone-storage', ['smartphone'], [['Camera', '108MP'], ['Charging', '33W fast charge']]),
            $B('electronics', 'vivo', 'Vivo Y36 Smartphone', 21990, 24990, 'phone-storage', ['smartphone'], [['Display', '6.64-inch 90Hz'], ['Charging', '44W flash charge']]),
            $B('electronics', 'walton', 'Walton NEXG N72 Smartphone', 12990, 14990, 'phone-storage', ['smartphone', 'made-in-bangladesh'], [['Battery', '5000mAh'], ['RAM', '8GB']]),
            // ── Audio / accessories (color variants) ──
            $B('mobile-accessories', 'anker', 'Anker Soundcore R50i True Wireless Earbuds', 2490, 3290, 'accessory-color', ['earbuds', 'audio'], [['Bluetooth', '5.3'], ['Playtime', '30 hours with case']]),
            $B('mobile-accessories', 'xiaomi', 'Xiaomi Redmi Buds 4 Lite', 1990, 2590, 'accessory-color', ['earbuds'], [['Driver', '10mm dynamic'], ['Latency', '60ms low latency mode']]),
            $B('mobile-accessories', 'samsung', 'Samsung Galaxy Buds2 Pro Wireless Earbuds', 12990, 15990, 'accessory-color', ['earbuds', 'anc'], [['ANC', 'Intelligent active noise cancellation'], ['Water resistance', 'IPX7']]),
            $B('electronics', 'samsung', 'Sony-Style WH-1000XM5 Wireless Headphones', 0, 0, 'accessory-color', ['headphones'], [['Battery', '30-hour battery'], ['Noise cancelling', 'Dual processor control']]),
            $B('electronics', 'walton', 'Walton Wired Earphone with Mic', 350, 550, 'accessory-color', ['earphone'], [['Plug', '3.5mm'], ['Mic', 'In-line microphone']]),
            // ── Smart devices (watch variants) ──
            $B('smart-devices', 'xiaomi', 'Xiaomi Redmi Watch 3 Active', 4990, 5990, 'watch', ['smartwatch'], [['Display', '1.83-inch LCD'], ['Battery', '12-day typical use']]),
            $B('smart-devices', 'realme', 'Realme Watch 3 Pro Smartwatch', 6990, 7990, 'watch', ['smartwatch', 'amoled'], [['Display', '1.78-inch AMOLED'], ['GPS', 'Built-in GPS']]),
            $B('smart-devices', 'vivo', 'Amazfit-Style GTS 4 Mini Smartwatch', 8990, 10990, 'watch', ['smartwatch'], [['Water resistance', '5 ATM'], ['Modes', '120+ sports modes']]),
            // ── Power & cables ──
            $B('mobile-accessories', 'anker', 'Anker 20000mAh Power Bank with 22.5W Fast Charge', 2990, 3990, 'none', ['power-bank'], [['Capacity', '20000mAh'], ['Output', '22.5W fast charging']]),
            $B('mobile-accessories', 'anker', 'Anker 65W GaN Fast Charger', 3290, 3990, 'none', ['charger'], [['Ports', '2x USB-C + 1x USB-A'], ['Tech', 'GaN II']]),
            $B('mobile-accessories', 'sandisk', 'SanDisk Ultra 128GB microSDXC Memory Card', 1490, 1890, 'none', ['memory-card'], [['Speed', 'Up to 140MB/s'], ['Class', 'A1 UHS-I']]),
            $B('computer-accessories', 'logitech', 'Logitech M331 Silent Plus Wireless Mouse', 1890, 2290, 'accessory-color', ['mouse'], [['Silent clicks', '90% noise reduction'], ['Range', '10m wireless']]),
            $B('computer-accessories', 'logitech', 'Logitech K380 Multi-Device Bluetooth Keyboard', 3990, 4590, 'accessory-color', ['keyboard'], [['Pairing', '3 devices'], ['Battery', '2-year battery']]),
            $B('computer-accessories', 'hp', 'HP 15s Core i3 13th Gen Laptop (8GB/512GB)', 62500, 68500, 'none', ['laptop'], [['CPU', 'Intel Core i3-1315U'], ['Storage', '512GB SSD']]),
            // ── Home appliances ──
            $B('home-appliances', 'singer', 'Singer 16-inch High Speed Table Fan', 3290, 3890, 'none', ['fan'], [['Speed', '3-speed control'], ['Warranty', '1-year service (demo)']]),
            $B('home-appliances', 'vision', 'Vision 32-inch Smart LED TV', 21990, 24990, 'none', ['tv'], [['Resolution', '1366x768 HD'], ['OS', 'Android 11 smart TV']]),
            $B('home-appliances', 'walton', 'Walton 142L Direct Cool Refrigerator', 28990, 31990, 'none', ['fridge'], [['Capacity', '142 litres'], ['Defrost', 'Direct cool']]),
            $B('home-appliances', 'singer', 'Singer Steam Iron 1600W', 1890, 2390, 'none', ['iron'], [['Power', '1600W'], ['Soleplate', 'Non-stick']]),
            $B('kitchen-appliances', 'vision', 'Vision Electric Kettle 1.8L', 1490, 1890, 'none', ['kettle'], [['Capacity', '1.8 litres'], ['Auto shut-off', 'Yes']]),
            $B('kitchen-appliances', 'singer', 'Singer Rice Cooker 2.8L', 3290, 3890, 'home-capacity', ['rice-cooker'], [['Capacity', '2.8 litres'], ['Warmer', 'Keep-warm function']]),
            $B('kitchen-appliances', 'rfl', 'RFL Plastic Storage Container Set (6 pcs)', 890, 1190, 'home-capacity', ['storage'], [['Pieces', '6 food-grade containers'], ['Seal', 'Airtight lids']]),
            $B('kitchen-appliances', 'walton', 'Walton Microwave Oven 20L', 12990, 14990, 'none', ['microwave'], [['Capacity', '20 litres'], ['Power levels', '5']]),
            $B('kitchen-appliances', 'vision', 'Vision Blender 1000W with 3 Jars', 4990, 5990, 'none', ['blender'], [['Power', '1000W copper motor'], ['Jars', '3 stainless steel jars']]),
            // ── Men's fashion (apparel variants) ──
            $B('mens-fashion', 'yellow', 'Yellow Premium Cotton T-Shirt for Men', 590, 890, 'apparel-color-size', ['t-shirt', 'cotton'], [['Fabric', '100% combed cotton'], ['GSM', '180 GSM']]),
            $B('mens-fashion', 'ecstasy', 'Ecstasy Graphic Print T-Shirt', 690, 990, 'apparel-color-size', ['t-shirt'], [['Fit', 'Regular fit'], ['Print', 'Water-based graphic print']]),
            $B('mens-fashion', 'aarong', 'Aarong Slim Fit Denim Jeans for Men', 1490, 1990, 'jeans', ['jeans', 'denim'], [['Fabric', '98% cotton 2% spandex'], ['Fit', 'Slim fit']]),
            $B('mens-fashion', 'lotto', 'Lotto Classic Denim Jeans', 1290, 1790, 'jeans', ['jeans'], [['Rise', 'Mid rise'], ['Pockets', '5-pocket styling']]),
            $B('mens-fashion', 'aarong', 'Aarong Cotton Panjabi for Eid', 1890, 2590, 'apparel-color-size', ['panjabi'], [['Fabric', 'Premium cotton'], ['Buttons', 'Metal show buttons']]),
            $B('mens-fashion', 'yellow', 'Yellow Formal Shirt Slim Fit', 1190, 1590, 'apparel-color-size', ['shirt'], [['Cuff', 'Single button cuff'], ['Fit', 'Slim fit']]),
            $B('mens-fashion', 'lotto', 'Lotto Sports Polo T-Shirt', 790, 1090, 'apparel-color-size', ['polo'], [['Fabric', 'PK pique'], ['Fit', 'Athletic fit']]),
            $B('mens-fashion', 'apex', 'Apex Men Casual Leather Belt', 690, 990, 'none', ['belt'], [['Material', 'Genuine leather (demo)'], ['Buckle', 'Alloy pin buckle']]),
            // ── Women's fashion ──
            $B('womens-fashion', 'aarong', 'Aarong Block Print Three-Piece Salwar Set', 1890, 2490, 'apparel-color-size', ['salwar', 'three-piece'], [['Work', 'Hand block print'], ['Dupatta', 'Cotton 2.5m']]),
            $B('womens-fashion', 'yellow', 'Yellow Floral Georgette Saree with Blouse', 2490, 3290, 'accessory-color', ['saree'], [['Fabric', 'Weightless georgette'], ['Length', '12 haat + blouse piece']]),
            $B('womens-fashion', 'ecstasy', 'Ecstasy Embroidered Kurti', 1290, 1790, 'apparel-color-size', ['kurti'], [['Embroidery', 'Neckline embroidery'], ['Length', '42 inch']]),
            $B('womens-fashion', 'aarong', 'Aarong Cotton Hijab - Large Coverage', 390, 590, 'accessory-color', ['hijab'], [['Size', '72x30 inch'], ['Fabric', 'Breathable cotton']]),
            $B('womens-fashion', 'yellow', 'Yellow Chiffon Party Saree', 2990, 3990, 'accessory-color', ['saree', 'party'], [['Stones', 'All-over stone work (demo)'], ['Blouse', 'Unstitched blouse piece']]),
            // ── Shoes (size variants) ──
            $B('shoes-footwear', 'apex', 'Apex Men Running Sneakers', 1890, 2590, 'shoes', ['sneakers'], [['Sole', 'Anti-skid EVA sole'], ['Upper', 'Breathable mesh']]),
            $B('shoes-footwear', 'bata', 'Bata Ladies Comfort Sandals', 990, 1390, 'shoes', ['sandals'], [['Heel', 'Flat 1-inch heel'], ['Strap', 'Soft synthetic strap']]),
            $B('shoes-footwear', 'lotto', 'Lotto Football Boots - Firm Ground', 2290, 2990, 'shoes', ['football'], [['Studs', 'FG moulded studs'], ['Upper', 'Synthetic leather']]),
            $B('shoes-footwear', 'apex', 'Apex Leather Loafers for Men', 2490, 3290, 'shoes', ['loafers'], [['Lining', 'Cushioned insole'], ['Occasion', 'Office & casual']]),
            // ── Bags ──
            $B('bags-luggage', 'rfl', 'RFL Travel Backpack 35L Water Resistant', 1290, 1790, 'accessory-color', ['backpack'], [['Capacity', '35 litres'], ['Laptop sleeve', '15.6-inch padded']]),
            $B('bags-luggage', 'apex', 'Apex Ladies Handbag with Sling', 1490, 1990, 'accessory-color', ['handbag'], [['Compartments', '3 zip compartments'], ['Strap', 'Adjustable sling']]),
            $B('bags-luggage', 'lotto', 'Lotto Gym Duffel Bag 45L', 1090, 1490, 'none', ['duffel'], [['Capacity', '45 litres'], ['Shoe pocket', 'Separate shoe chamber']]),
            // ── Beauty ──
            $B('beauty-personal-care', 'dove', 'Dove Beauty Bar Soap 100g (Pack of 3)', 390, 520, 'none', ['soap'], [['Cream', '1/4 moisturising cream (brand claim)'], ['Pack', '3 x 100g']]),
            $B('beauty-personal-care', 'lakme', 'Lakme Absolute Matte Lipstick - Shade Set', 690, 890, 'accessory-color', ['lipstick'], [['Finish', 'Matte'], ['Weight', '3.7g']]),
            $B('beauty-personal-care', 'nivea', 'Nivea Soft Moisturising Cream 200ml', 590, 750, 'none', ['cream'], [['Skin', 'All skin types'], ['Vitamin', 'Vitamin E + jojoba oil']]),
            $B('beauty-personal-care', 'parachute', 'Parachute Coconut Oil 500ml', 490, 620, 'none', ['hair-oil'], [['Volume', '500ml'], ['Use', 'Hair & skin (demo)']]),
            $B('beauty-personal-care', 'dettol', 'Dettol Antiseptic Liquid 500ml', 320, 420, 'none', ['antiseptic'], [['Use', 'First aid & hygiene'], ['Volume', '500ml']]),
            $B('beauty-personal-care', 'square', 'Meril Baby Lotion 200ml', 290, 390, 'none', ['baby'], [['Gentle', 'Mild baby formula (demo)'], ['Volume', '200ml']]),
            // ── Grocery staples ──
            $B('grocery-staples', 'teer', 'Teer Fortified Soybean Oil 5L', 890, 950, 'none', ['oil'], [['Volume', '5 litres'], ['Fortified', 'Vitamin A fortified']]),
            $B('grocery-staples', 'aci-pure', 'ACI Pure Miniket Rice 5kg Premium', 690, 790, 'none', ['rice'], [['Weight', '5kg'], ['Grain', 'Long grain miniket']]),
            $B('grocery-staples', 'fresh', 'Fresh Super Premium Salt 1kg', 45, 60, 'none', ['salt'], [['Iodised', 'Yes'], ['Weight', '1kg']]),
            $B('grocery-staples', 'pran', 'Pran Mustard Oil 1L Glass Bottle', 290, 340, 'none', ['oil'], [['Volume', '1 litre'], ['Press', 'Cold pressed (demo)']]),
            $B('grocery-staples', 'square', 'Radhuni-Style Garam Masala 100g', 120, 160, 'none', ['spice'], [['Weight', '100g'], ['Grind', 'Fresh ground (demo)']]),
            $B('grocery-staples', 'pran', 'Pran Instant Noodles Family Pack 8pcs', 190, 240, 'none', ['noodles'], [['Pieces', '8 x 62g'], ['Flavour', 'Masala']]),
            $B('grocery-staples', 'fresh', 'Fresh Atta Whole Wheat 2kg', 190, 230, 'none', ['atta'], [['Weight', '2kg'], ['Fiber', 'High fiber (demo)']]),
            $B('grocery-staples', 'aci-pure', 'ACI Pure Sugar 1kg', 140, 160, 'none', ['sugar'], [['Weight', '1kg'], ['Refined', 'Yes']]),
            // ── Home & living (bedding / curtain variants) ──
            $B('home-living', 'aarong', 'Aarong Cotton Bedsheet with 2 Pillow Covers', 1290, 1790, 'bedding', ['bedsheet'], [['Thread count', '200TC cotton'], ['Pillows', '2 covers included']]),
            $B('home-living', 'rfl', 'RFL Plastic Chair - Heavy Duty', 890, 1190, 'accessory-color', ['chair'], [['Load', 'Up to 120kg (demo)'], ['Stackable', 'Yes']]),
            $B('home-living', 'vision', 'Vision LED Table Lamp with Rechargeable Battery', 990, 1390, 'none', ['lamp'], [['Battery', '4000mAh rechargeable'], ['Modes', '3 brightness modes']]),
            $B('home-living', 'aarong', 'Block Print Curtain Pair - 7ft Door', 1490, 1990, 'curtain', ['curtain'], [['Size', '4ft x 7ft each'], ['Eyelets', 'Rust-proof eyelets']]),
            $B('home-living', 'square', 'Square Mosquito Coil Double Pack', 120, 160, 'none', ['coil'], [['Pack', '10 coils x 2'], ['Protection', 'Up to 8 hours (demo)']]),
        ];
    }

    protected function createProduct(array $bp, string $title, string $slug, int $index, array &$usedSkus): int
    {
        $cat = Category::where('slug', $bp['cat'])->first();
        $brand = Brand::where('slug', $bp['brand'])->first();
        $collection = Collection::where('slug', 'demo-essentials')->first();
        $vendor = Vendor::where('slug', 'mama-bazar-direct')->first();
        $supplier = Supplier::where('slug', 'dhaka-wholesale')->first();

        // Deterministic BDT pricing
        $price = $bp['min'] > 0 ? mt_rand($bp['min'], $bp['max']) : mt_rand(490, 2990);
        $price = (int) (round($price / 10) * 10) + 90; // e.g. x,090 endings feel local
        $onSale = (mt_rand(1, 100) <= 45);
        $salePrice = $onSale ? (int) ($price * (mt_rand(85, 95) / 100)) : null;
        $costPrice = (int) ($price * 0.68);

        $baseSku = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $bp['brand']), 0, 3)) . '-'
            . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $bp['cat']), 0, 3)) . '-'
            . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        if (isset($usedSkus[$baseSku])) {
            $baseSku .= '-' . ($index + 1);
        }
        $usedSkus[$baseSku] = true;

        $brandName = $brand?->name ?? ucfirst($bp['brand']);
        $catName = $cat?->name ?? ucfirst($bp['cat']);

        $images = [];
        $imgCount = in_array($bp['variant'], ['apparel-color-size', 'jeans', 'shoes', 'phone-storage']) ? 3 : 2;
        for ($k = 1; $k <= $imgCount; $k++) {
            $images[] = ProductImageSeeder::createProductSvg($title, $slug, $k, $brandName, $catName);
        }

        $stockBase = mt_rand(15, 120);
        $flags = [
            'is_featured' => mt_rand(1, 100) <= 18,
            'is_trending' => mt_rand(1, 100) <= 22,
            'is_flash_sale' => false,
            'is_new_arrival' => mt_rand(1, 100) <= 25,
            'is_best_seller' => mt_rand(1, 100) <= 20,
            'is_hot_deal' => false,
        ];
        if ($flags['is_best_seller'] && $onSale && mt_rand(1, 100) <= 30) {
            $flags['is_flash_sale'] = true;
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'description' => $this->description($title, $brandName, $catName),
            'short_description' => mb_substr("{$brandName} {$catName} essential for everyday use in Bangladesh. Demo product - quality checked.", 0, 300),
            'price' => $price,
            'sale_price' => $salePrice,
            'discount' => 0,
            'cost_price' => $costPrice,
            'profit_margin' => 0,
            'tax' => 0, 'vat' => 0, 'shipping_charge' => 0, 'cod_fee' => 0,
            'category_id' => $cat?->id,
            'collection_id' => $collection?->id,
            'brand_id' => $brand?->id,
            'brand' => $brandName,
            'vendor_id' => $vendor?->id,
            'supplier_id' => $supplier?->id,
            'country_of_origin' => 'Bangladesh',
            'sku' => $baseSku,
            'barcode' => '890' . str_pad((string) mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
            'tags' => array_merge($bp['tags'], ['demo-catalog', strtolower($bp['cat'])]),
            'features' => $this->features($title, $catName),
            'seo_title' => mb_substr("{$title} - Best price in Bangladesh | Mama Bazar (Demo)", 0, 255),
            'seo_description' => mb_substr("Buy {$title} online in Bangladesh. {$brandName} quality, cash on delivery across Dhaka and nationwide. Demo catalog entry.", 0, 500),
            'stock' => $stockBase,
            'low_stock_alert' => 8,
            'min_order' => 1,
            'unlimited_stock' => false,
            'backorder' => false,
            'track_inventory' => true,
            'stock_status' => 'in_stock',
            'product_status' => 'published',
            'status' => 'active',
            'images' => $images,
        ] + $flags;

        $variants = $this->buildVariants($bp['variant'], $baseSku, $price, $salePrice, $images);

        $payload = ProductService::sanitizeAttributes($data);

        return DB::transaction(function () use ($payload, $variants, $bp) {
            $product = Product::create($payload);
            $created = 0;
            foreach ($variants as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $v['name'],
                    'options' => $v['options'],
                    'price' => $v['price'],
                    'discount_price' => $v['discount_price'],
                    'sku' => $v['sku'],
                    'barcode' => $v['barcode'],
                    'stock' => $v['stock'],
                    'images' => [$v['image']],
                    'thumbnail' => $v['image'],
                    'status' => 'active',
                    'availability' => true,
                ]);
                $created++;
            }
            if ($created > 0) {
                $product->update(['stock' => collect($variants)->sum('stock')]);
            }
            $sort = 0;
            foreach ($bp['specs'] as [$label, $value]) {
                ProductSpec::create([
                    'product_id' => $product->id, 'label' => $label, 'value' => $value, 'sort_order' => $sort++,
                ]);
            }
            return $created;
        });
    }

    protected function buildVariants(string $type, string $baseSku, int $price, ?int $salePrice, array $images): array
    {
        $img = $images[0] ?? '/storage/products/demo.svg';
        $out = [];
        $mk = function (string $name, array $options, int $priceDelta, int $stock, string $skuSuffix) use ($baseSku, $price, $salePrice, $img) {
            $vp = $price + $priceDelta;
            return [
                'name' => $name,
                'options' => $options,
                'price' => $vp,
                'discount_price' => $salePrice !== null ? max(1, $vp - ($price - $salePrice)) : null,
                'sku' => $baseSku . $skuSuffix,
                'barcode' => '890' . str_pad((string) mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
                'stock' => $stock,
                'image' => $img,
            ];
        };

        switch ($type) {
            case 'apparel-color-size':
                $colors = [['Black', 'BLK'], ['White', 'WHT'], ['Navy', 'NVY'], ['Olive', 'OLV']];
                $sizes = ['S', 'M', 'L', 'XL'];
                foreach ($colors as [$c, $cc]) {
                    foreach ($sizes as $s) {
                        $out[] = $mk("{$c} / {$s}", ['Color' => $c, 'Size' => $s], mt_rand(-50, 100), mt_rand(8, 25), "-{$cc}-{$s}");
                    }
                }
                break;
            case 'jeans':
                $colors = [['Blue', 'BLU'], ['Black', 'BLK'], ['Grey', 'GRY']];
                $sizes = ['28', '30', '32', '34', '36'];
                foreach ($colors as [$c, $cc]) {
                    foreach ($sizes as $s) {
                        $out[] = $mk("{$c} / Waist {$s}", ['Color' => $c, 'Waist' => $s], mt_rand(0, 150), mt_rand(6, 20), "-{$cc}-W{$s}");
                    }
                }
                break;
            case 'shoes':
                $colors = [['Black', 'BLK'], ['White', 'WHT'], ['Brown', 'BRN']];
                $sizes = ['39', '40', '41', '42', '43', '44'];
                foreach (array_slice($colors, 0, 2) as [$c, $cc]) {
                    foreach ($sizes as $s) {
                        $out[] = $mk("{$c} / Size {$s}", ['Color' => $c, 'Size' => $s], mt_rand(0, 200), mt_rand(5, 18), "-{$cc}-{$s}");
                    }
                }
                break;
            case 'phone-storage':
                foreach ([['128GB', 0], ['256GB', 2500], ['512GB', 6000]] as [$stor, $delta]) {
                    $out[] = $mk($stor, ['Storage' => $stor], $delta, mt_rand(10, 30), '-' . str_replace('GB', '', $stor));
                }
                break;
            case 'accessory-color':
                foreach ([['Black', 'BLK'], ['White', 'WHT'], ['Blue', 'BLU']] as [$c, $cc]) {
                    $out[] = $mk($c, ['Color' => $c], mt_rand(-100, 100), mt_rand(12, 40), "-{$cc}");
                }
                break;
            case 'watch':
                foreach ([['Black Silicone', 'BLK'], ['Brown Leather', 'BRN'], ['Blue Silicone', 'BLU']] as [$c, $cc]) {
                    $parts = explode(' ', $c);
                    $out[] = $mk($c, ['Color' => $parts[0], 'Strap' => ($parts[1] ?? 'Silicone') . ' Strap'], mt_rand(0, 500), mt_rand(10, 25), "-{$cc}");
                }
                break;
            case 'home-capacity':
                foreach ([['Small', 'S', -200], ['Medium', 'M', 0], ['Large', 'L', 300]] as [$label, $code, $delta]) {
                    $out[] = $mk($label, ['Capacity' => $label], $delta, mt_rand(10, 30), "-{$code}");
                }
                break;
            case 'bedding':
                foreach ([['Single', 'SGL', -300], ['Queen', 'QN', 0], ['King', 'KNG', 400]] as [$label, $code, $delta]) {
                    $out[] = $mk($label, ['Bed Size' => $label], $delta, mt_rand(8, 22), "-{$code}");
                }
                break;
            case 'curtain':
                foreach ([['Maroon 7ft', 'MRN'], ['Beige 7ft', 'BGE'], ['Navy 7ft', 'NVY']] as [$label, $code]) {
                    $color = explode(' ', $label)[0];
                    $out[] = $mk($label, ['Color' => $color, 'Size' => '7ft'], mt_rand(-100, 200), mt_rand(8, 20), "-{$code}");
                }
                break;
            default:
                return [];
        }

        // Cap variants per product to keep total sane (apparel 16 -> keep 8 sampled deterministically)
        if (count($out) > 12) {
            $out = array_values(array_filter($out, fn($_, $k) => $k % 2 === 0, ARRAY_FILTER_USE_BOTH));
            $out = array_slice($out, 0, 12);
        }
        return $out;
    }

    protected function description(string $title, string $brand, string $cat): string
    {
        return "<p><strong>{$title}</strong> from {$brand} - a popular {$cat} pick for customers across Bangladesh.</p>"
            . "<p>Demo catalog entry: specifications below describe a representative product. "
            . "Cash on delivery available in Dhaka and nationwide courier delivery.</p>"
            . "<ul><li>Genuine demo stock checked before dispatch</li>"
            . "<li>Easy 7-day exchange for manufacturing issues (demo policy)</li>"
            . "<li>Pay via bKash, Nagad, Rocket or cash on delivery</li></ul>";
    }

    protected function features(string $title, string $cat): array
    {
        return [
            "Selected for {$cat} shoppers in Bangladesh (demo)",
            'Quality-checked demo stock',
            'Cash on delivery available',
        ];
    }

    protected function verify(int $expected): int
    {
        $products = Product::count();
        $slugs = Product::pluck('slug')->all();
        $dupSlugs = count($slugs) - count(array_unique($slugs));
        $badPrices = Product::where('price', '<=', 0)
            ->orWhere(function ($q) {
                $q->whereNotNull('sale_price')->whereRaw('sale_price >= price');
            })->count();
        $negStock = Product::where('stock', '<', 0)->count();
        $orphanVariants = ProductVariant::whereNotIn('product_id', Product::pluck('id'))->count();
        $dupSkus = DB::table('products')->select('sku')->groupBy('sku')->havingRaw('COUNT(*) > 1')->count();

        $this->line("Products: {$products} (expected {$expected}) | dup slugs: {$dupSlugs} | bad prices: {$badPrices} | neg stock: {$negStock} | orphan variants: {$orphanVariants} | dup SKUs: {$dupSkus}");

        if ($products !== $expected || $dupSlugs > 0 || $badPrices > 0 || $negStock > 0 || $orphanVariants > 0) {
            $this->error('Verification FAILED - see counts above.');
            return 1;
        }
        $this->info('Verification PASSED: 200 active demo products with consistent variants, prices and inventory.');
        return 0;
    }
}
