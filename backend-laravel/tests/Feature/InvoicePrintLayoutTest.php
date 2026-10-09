<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Order $order;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();

        PaymentMethod::ensureDefaults();
        $ship = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'charge' => 60,
            'status' => 'active',
            'cod_available' => true,
        ]);

        $cat = Category::create([
            'name' => 'Lighting & Decor',
            'slug' => 'lighting-decor',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'title' => 'ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন',
            'slug' => 'vintage-led-camping-lantern',
            'price' => 800,
            'sale_price' => 650,
            'stock' => 25,
            'sku' => 'VNT-LED-001',
            'category_id' => $cat->id,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        $this->order = Order::create([
            'order_id' => 'MB-ORD-999',
            'invoice_number' => 'INV-2026-000999',
            'customer_name' => 'Tariq Al-Mansoor',
            'phone' => '01712345678',
            'address' => 'House 12, Road 4, Dhanmondi',
            'district' => 'Dhaka',
            'shipping_method_name' => 'Standard Delivery',
            'shipping_cost' => 60,
            'subtotal' => 650,
            'total_price' => 710,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'quantity' => 1,
            'price' => 650,
        ]);
    }

    public function test_invoice_page_renders_with_a4_portrait_print_styles_and_zero_margins(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.invoice', $this->order->id));

        $response->assertStatus(200);

        // Verify page content
        $response->assertSee('Tariq Al-Mansoor');
        $response->assertSee('INV-2026-000999');
        $response->assertSee('ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন');
        $response->assertSee('VNT-LED-001');
        $response->assertSee('৳710');

        $content = $response->getContent();

        // 1. Verify @page sets A4 portrait with balanced 10mm margins
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 10mm; }', $content);

        // 2. Verify print CSS sets width 100%, zero outer margin/padding
        $this->assertStringContainsString('html, body {', $content);
        $this->assertStringContainsString('width: 100% !important;', $content);
        $this->assertStringContainsString('margin: 0 !important;', $content);
        $this->assertStringContainsString('padding: 0 !important;', $content);

        // 3. Verify .sheet and .sheet-inner occupy full width without restrictions in print
        $this->assertStringContainsString('.sheet {', $content);
        $this->assertStringContainsString('.sheet-inner {', $content);
        $this->assertStringContainsString('box-sizing: border-box !important;', $content);

        // 4. Verify table and break-inside rules
        $this->assertStringContainsString('thead {', $content);
        $this->assertStringContainsString('display: table-header-group !important;', $content);
        $this->assertStringContainsString('break-inside: avoid !important;', $content);

        // 5. Verify mobile responsive query is scoped to screen so it never applies to print
        $this->assertStringContainsString('@media screen and (max-width: 700px)', $content);
        $this->assertStringNotContainsString('@media (max-width: 700px)', $content);

        // 6. Verify auto-print handler for ?print=1
        $this->assertStringContainsString("urlParams.get('print')", $content);
        $this->assertStringContainsString('window.print()', $content);
    }

    public function test_order_show_page_print_invoice_link_triggers_print_parameter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->id));

        $response->assertStatus(200);

        // Verify the Print Invoice link has ?print=1 and no inline window.open print blocker
        $expectedUrl = route('admin.orders.invoice', $this->order->id).'?print=1';
        $response->assertSee($expectedUrl, false);
        $response->assertDontSee("window.open(this.href,'_blank').print()", false);
    }

    public function test_pdf_download_remains_functional_and_accurate(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.invoice.download', $this->order->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
