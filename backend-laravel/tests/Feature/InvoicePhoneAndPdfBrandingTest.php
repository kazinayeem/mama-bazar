<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePhoneAndPdfBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $cat = Category::firstOrCreate(['slug' => 't'], ['name' => 'T', 'status' => 'active']);
        return Product::firstOrCreate(
            ['slug' => 'tp'],
            ['title' => 'Test Product', 'price' => 500, 'category_id' => $cat->id,
             'status' => 'active', 'product_status' => 'published', 'stock' => 50, 'sku' => 'MB-101']
        );
    }

    private function makeOrder(array $over = []): Order
    {
        \App\Models\PaymentMethod::ensureDefaults();
        $ship = ShippingMethod::firstOrCreate(
            ['name' => 'Test Delivery'],
            ['charge' => 60, 'status' => 'active', 'cod_available' => true]
        );
        $p = $this->makeProduct();

        $result = OrderService::createOrder(array_merge([
            'customer_name' => 'Karim Uddin',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'address' => 'House 12, Road 5, Mirpur',
            'shipping_method_id' => $ship->id,
            'payment_method' => 'cod',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ], $over));

        return Order::where('order_id', $result['order']['orderId'])->firstOrFail();
    }

    public function test_alternative_phone_identical_to_phone_is_nulled_at_source(): void
    {
        $order = $this->makeOrder([
            'phone' => '01712345678',
            'alternative_phone' => '01712 345678', // same number, different formatting
        ]);

        $this->assertEquals('01712345678', $order->phone);
        $this->assertNull($order->alternative_phone);
        $this->assertNull($order->display_alternative_phone);
    }

    public function test_distinct_alternative_phone_is_preserved(): void
    {
        $order = $this->makeOrder([
            'phone' => '01712345678',
            'alternative_phone' => '01812345678',
        ]);

        $this->assertEquals('01812345678', $order->alternative_phone);
        $this->assertEquals('01812345678', $order->display_alternative_phone);
    }

    public function test_historical_duplicate_alt_phone_is_hidden_on_display(): void
    {
        // Legacy rows (saved before normalization) keep their data untouched,
        // but the display accessor suppresses the duplicate.
        $order = $this->makeOrder([]);
        $order->alternative_phone = $order->phone;
        $order->save();

        $this->assertEquals($order->phone, $order->fresh()->alternative_phone);
        $this->assertNull($order->fresh()->display_alternative_phone);
    }

    public function test_invoice_pdf_contains_single_phone_and_bornosoft_attribution(): void
    {
        $order = $this->makeOrder([
            'phone' => '01712345678',
            'alternative_phone' => '01712345678', // would previously render "X / X"
        ]);
        $admin = User::create([
            'name' => 'Admin', 'phone' => '01000000009',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
        ]);

        $res = $this->actingAs($admin)->get("/admin/orders/{$order->id}/invoice/download");
        $res->assertStatus(200);
        $content = $res->getContent();
        $this->assertStringStartsWith('%PDF', $content);
        // dompdf compresses page streams: inflate them and confirm the clickable
        // link annotation (/URI) for bornosoft.bd survived inside the PDF.
        $this->assertStringContainsString('bornosoft.bd', self::inflatePdfStreams($content));

        // dompdf compresses content streams, so attribution wiring is verified
        // against the rendered invoice-pdf HTML (same view the PDF is built from).
        $pdfHtml = view(
            'admin.orders.invoice-pdf',
            ['order' => $order->load(['items.product', 'items.variant']), 'store' => \App\Http\Controllers\Admin\AdminOrderWebController::storeInfo()]
        )->render();
        $this->assertStringContainsString('Software crafted by Bornosoft', $pdfHtml);
        $this->assertStringContainsString('https://bornosoft.bd/', $pdfHtml);
        $this->assertStringContainsString('bornosoft.bd', $pdfHtml);

        // HTML invoice billing block renders the phone exactly once
        // (the number also appears in the copy-to-clipboard JS helper).
        $htmlRes = $this->actingAs($admin)->get("/admin/orders/{$order->id}/invoice");
        $htmlRes->assertStatus(200);
        $html = $htmlRes->getContent();
        $this->assertEquals(1, substr_count($html, 'Phone: <strong>01712345678</strong>'));
        $this->assertStringNotContainsString('Alternative Phone:', $html);
        $this->assertStringContainsString('bornosoft.bd', $html);
    }

    public function test_global_footer_renders_once_with_working_links_and_attribution(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(\Database\Seeders\PolicyPageSeeder::class);
        foreach (['/', '/shop', '/cart', '/checkout', '/track', '/about', '/faq', '/contact'] as $path) {
            $html = $this->get($path)->getContent();
            $this->assertEquals(1, substr_count($html, '<footer'), "footer count on {$path}");
            $this->assertStringContainsString('Crafted by', $html);
            $this->assertStringContainsString('https://bornosoft.bd/', $html);
            $this->assertStringContainsString('target="_blank"', $html);
        }

        // Every footer policy link resolves (no 404s).
        foreach (['return-refund', 'shipping-policy', 'privacy-policy', 'terms'] as $slug) {
            $this->get("/pages/{$slug}")->assertStatus(200);
        }
        $this->get('/terms-and-conditions')->assertStatus(200);
        $this->get('/shipping-policy')->assertStatus(200);
    }

    /**
     * Decompress every FlateDecode stream in a PDF binary so text/annotations
     * can be asserted without a full PDF parser.
     */
    private static function inflatePdfStreams(string $pdf): string
    {
        $out = $pdf;
        if (preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $m)) {
            foreach ($m[1] as $raw) {
                $inflated = @gzinflate(substr($raw, 2));
                if (is_string($inflated)) {
                    $out .= "\n" . $inflated;
                } else {
                    $inflated = @gzuncompress($raw);
                    if (is_string($inflated)) {
                        $out .= "\n" . $inflated;
                    }
                }
            }
        }
        return $out;
    }
}
