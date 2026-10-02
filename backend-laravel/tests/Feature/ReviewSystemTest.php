<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReviewService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('custom_role', 'SUPER_ADMIN')->first()
            ?? User::where('role', 'admin')->first();

        $this->customer = User::create([
            'name' => 'Rahim Uddin', 'phone' => '01790000001',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);

        $cat = Category::create(['name' => 'Grocery', 'slug' => 'grocery', 'status' => 'active']);
        $this->product = Product::create([
            'title' => 'Test Honey 500g', 'slug' => 'test-honey-500g', 'price' => 550,
            'category_id' => $cat->id, 'status' => 'active',
            'product_status' => 'published', 'stock' => 40,
        ]);
    }

    private function purchaseAs(User $user): void
    {
        PaymentMethod::ensureDefaults();
        $ship = ShippingMethod::firstOrCreate(
            ['name' => 'Test Delivery'],
            ['charge' => 60, 'status' => 'active', 'cod_available' => true]
        );
        $result = OrderService::createOrder([
            'customer_name' => $user->name, 'phone' => $user->phone,
            'district' => 'Dhaka', 'address' => 'House 1, Dhaka',
            'shipping_method_id' => $ship->id, 'payment_method' => 'cod',
            'userId' => $user->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);
        $order = Order::where('order_id', $result['order']['orderId'])->firstOrFail();
        // Simulate a guest-checkout order linked by phone instead of user id.
        if ($user->is($this->customer) && ! $order->user_id) {
            $order->update(['user_id' => $user->id]);
        }
    }

    public function test_guest_cannot_submit_review(): void
    {
        $response = $this->post("/products/{$this->product->slug}/reviews", [
            'rating' => 5, 'comment' => 'Great!',
        ]);
        $response->assertRedirect('/login');
        $this->assertEquals(0, Review::count());
    }

    public function test_customer_submit_creates_pending_hidden_review(): void
    {
        $response = $this->actingAs($this->customer)->post("/products/{$this->product->slug}/reviews", [
            'rating' => 5, 'title' => 'Excellent', 'comment' => 'Very good product and fast delivery.',
        ]);
        $response->assertRedirect();
        $review = Review::first();
        $this->assertEquals('pending', $review->status);

        // Pending must NOT appear publicly (verify logged out; the author
        // sees their own pending card with a "visible only to you" note).
        $this->post('/logout');
        $pdp = $this->get("/products/{$this->product->slug}");
        $pdp->assertStatus(200);
        $pdp->assertDontSee('Very good product and fast delivery.');
        $summary = ReviewService::summary($this->product->id);
        $this->assertEquals(0, $summary['count']);
    }

    public function test_approve_makes_review_visible_and_updates_summary(): void
    {
        $review = ReviewService::submit($this->product->id, $this->customer, 5, null, 'Loved it, genuine honey.');

        ReviewService::setStatus($review, 'approved', $this->admin);

        $pdp = $this->get("/products/{$this->product->slug}");
        $pdp->assertSee('Loved it, genuine honey.');
        $summary = ReviewService::summary($this->product->id);
        $this->assertEquals(1, $summary['count']);
        $this->assertEquals(5.0, $summary['average']);
        $this->assertEquals(1, $summary['breakdown'][5]);
    }

    public function test_rejected_review_stays_hidden(): void
    {
        $review = ReviewService::submit($this->product->id, $this->customer, 1, null, 'Spammy fake review text here.');
        ReviewService::setStatus($review, 'rejected', $this->admin, 'Spam');

        $this->get("/products/{$this->product->slug}")->assertDontSee('Spammy fake review text here.');
        $this->assertEquals(0, ReviewService::summary($this->product->id)['count']);
        $this->assertEquals('Spam', $review->fresh()->admin_note);
    }

    public function test_duplicate_review_blocked_for_same_customer(): void
    {
        ReviewService::submit($this->product->id, $this->customer, 5, null, 'First review.');

        // Web second attempt.
        $response = $this->actingAs($this->customer)->post("/products/{$this->product->slug}/reviews", [
            'rating' => 4, 'comment' => 'Second review attempt.',
        ]);
        $response->assertSessionHas('error');

        // Direct service call (API-style) also blocked.
        try {
            ReviewService::submit($this->product->id, $this->customer, 4, null, 'Direct attempt.');
            $this->fail('Expected duplicate exception.');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }

        $this->assertEquals(1, Review::where('product_id', $this->product->id)
            ->where('user_id', $this->customer->id)->count());
    }

    public function test_invalid_rating_and_empty_comment_rejected(): void
    {
        foreach ([0, 6, -1] as $bad) {
            try {
                ReviewService::submit($this->product->id, $this->customer, $bad, null, 'Text here.');
                $this->fail("Rating {$bad} should be rejected.");
            } catch (\Exception $e) {
                $this->assertEquals(422, $e->getCode());
            }
        }
        try {
            ReviewService::submit($this->product->id, $this->customer, 5, null, '   ');
            $this->fail('Empty comment should be rejected.');
        } catch (\Exception $e) {
            $this->assertEquals(422, $e->getCode());
        }
        $this->assertEquals(0, Review::count());
    }

    public function test_verified_purchase_detected_from_order_history(): void
    {
        $this->assertFalse(ReviewService::hasVerifiedPurchase($this->customer->id, $this->product->id));

        $this->purchaseAs($this->customer);

        $this->assertTrue(ReviewService::hasVerifiedPurchase($this->customer->id, $this->product->id));

        $review = ReviewService::submit($this->product->id, $this->customer, 5, null, 'Bought it, genuine verified buyer here.');
        $this->assertTrue((bool) $review->is_verified_purchase);
        // The verified flag is computed server-side from order history and is
        // not mass-assignable from request input (not in any validation rules).
    }

    public function test_customer_can_edit_own_review_and_it_returns_to_pending(): void
    {
        $review = ReviewService::submit($this->product->id, $this->customer, 4, null, 'Good.');
        ReviewService::setStatus($review, 'approved', $this->admin);

        $response = $this->actingAs($this->customer)->put(
            "/products/{$this->product->slug}/reviews/{$review->id}",
            ['rating' => 5, 'comment' => 'Actually excellent after a week of use.']
        );
        $response->assertRedirect();

        $fresh = $review->fresh();
        $this->assertEquals(5, $fresh->rating);
        $this->assertEquals('pending', $fresh->status);
        auth()->logout();
        $this->app['auth']->forgetGuards();
        $this->get("/products/{$this->product->slug}")->assertDontSee('Actually excellent after a week');
    }

    public function test_customer_cannot_edit_someone_elses_review(): void
    {
        $other = User::create([
            'name' => 'Karim', 'phone' => '01790000002',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);
        $review = ReviewService::submit($this->product->id, $other, 5, null, 'Other user review text.');

        $response = $this->actingAs($this->customer)->put(
            "/products/{$this->product->slug}/reviews/{$review->id}",
            ['rating' => 1, 'comment' => 'Hijack attempt.']
        );
        $response->assertSessionHas('error');
        $this->assertEquals(5, $review->fresh()->rating);
    }

    public function test_admin_review_pages_require_auth_and_support_filters(): void
    {
        $this->get('/admin/reviews')->assertRedirect('/login');

        ReviewService::submit($this->product->id, $this->customer, 5, 'Great honey', 'Loved this honey jar.');
        $other = User::create([
            'name' => 'Sakib', 'phone' => '01790000003',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);
        $r2 = ReviewService::submit($this->product->id, $other, 2, null, 'Average quality product.');
        ReviewService::setStatus($r2, 'approved', $this->admin);

        $this->actingAs($this->admin)->get('/admin/reviews')->assertStatus(200)->assertSee('Loved this honey jar.');
        $this->actingAs($this->admin)->get('/admin/reviews?status=pending')->assertStatus(200)->assertSee('Loved this honey jar.');
        $this->actingAs($this->admin)->get('/admin/reviews?status=approved')->assertStatus(200)->assertDontSee('Loved this honey jar.');
        $this->actingAs($this->admin)->get('/admin/reviews?search=honey+jar')->assertStatus(200)->assertSee('Loved this honey jar.');
        $this->actingAs($this->admin)->get('/admin/reviews/1')->assertStatus(200);
    }

    public function test_admin_approve_reject_feature_delete_workflow(): void
    {
        $review = ReviewService::submit($this->product->id, $this->customer, 5, null, 'Workflow review text.');

        $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/status", ['status' => 'approved'])
            ->assertRedirect();
        $this->assertEquals('approved', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->approved_at);

        $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/featured", ['featured' => '1']);
        $this->assertTrue($review->fresh()->is_featured);

        $this->actingAs($this->admin)->post("/admin/reviews/{$review->id}/status", ['status' => 'rejected', 'admin_note' => 'Inappropriate'])
            ->assertRedirect();
        $this->assertEquals('rejected', $review->fresh()->status);
        // Featured + rejected must never surface publicly.
        $this->assertEmpty(ReviewService::featuredForHomepage(8)->where('id', $review->id));

        $this->actingAs($this->admin)->delete("/admin/reviews/{$review->id}")->assertRedirect();
        $this->assertNull(Review::find($review->id));
    }

    public function test_homepage_only_shows_approved_and_prefers_featured(): void
    {
        $pending = ReviewService::submit($this->product->id, $this->customer, 5, null, 'Pending homepage text.');
        $other = User::create([
            'name' => 'Nusrat', 'phone' => '01790000004',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);
        $approved = ReviewService::submit($this->product->id, $other, 5, null, 'Approved homepage text.');
        ReviewService::setStatus($approved, 'approved', $this->admin);
        ReviewService::setFeatured($approved, true);

        $items = ReviewService::featuredForHomepage(8);
        $comments = $items->pluck('comment')->all();
        $this->assertContains('Approved homepage text.', $comments);
        $this->assertNotContains('Pending homepage text.', $comments);
        $this->assertTrue($items->firstWhere('id', $approved->id)->is_featured);
    }

    public function test_review_comment_is_escaped_on_product_page(): void
    {
        $review = ReviewService::submit(
            $this->product->id, $this->customer, 5, null, 'Nice product <script>alert(1)</script> works well.'
        );
        ReviewService::setStatus($review, 'approved', $this->admin);

        $html = $this->get("/products/{$this->product->slug}")->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('Nice product', $html);
    }

    public function test_rating_summary_ignores_non_approved(): void
    {
        $u2 = User::create([
            'name' => 'Hasan', 'phone' => '01790000005',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);
        $u3 = User::create([
            'name' => 'Mim', 'phone' => '01790000006',
            'password' => bcrypt('secret'), 'role' => 'user', 'status' => 'active',
        ]);
        $r1 = ReviewService::submit($this->product->id, $this->customer, 5, null, 'Five stars here.');
        $r2 = ReviewService::submit($this->product->id, $u2, 4, null, 'Four stars here.');
        $r3 = ReviewService::submit($this->product->id, $u3, 1, null, 'One star pending here.');
        ReviewService::setStatus($r1, 'approved', $this->admin);
        ReviewService::setStatus($r2, 'approved', $this->admin);

        $summary = ReviewService::summary($this->product->id);
        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(4.5, $summary['average']);
        $this->assertEquals(1, $summary['breakdown'][5]);
        $this->assertEquals(1, $summary['breakdown'][4]);
        $this->assertEquals(0, $summary['breakdown'][1]);
    }
}
