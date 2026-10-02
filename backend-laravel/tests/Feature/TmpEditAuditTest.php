<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmpEditAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_edit_controls(): void
    {
        $admin = User::create(['name' => 'Admin', 'phone' => '01000000009', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
        $pages = [
            '/admin/categories', '/admin/products', '/admin/brands', '/admin/coupons',
            '/admin/marketing', '/admin/members', '/admin/shipping', '/admin/payment-methods',
            '/admin/checkout-notices', '/admin/orders', '/admin/customers', '/admin/banners',
        ];
        foreach ($pages as $p) {
            $res = $this->actingAs($admin)->get($p);
            $html = $res->getContent();
            $code = $res->getStatusCode();
            $edits = [];
            preg_match_all('/(?:>|")Edit(<|")/', $html, $m);
            $n = count($m[0]);
            // find edit hrefs / clicks
            preg_match_all('/<(?:a|button)[^>]*(?:href="#edit-[^"]*"|@click="[^"]*[Ee]dit[^"]*")[^>]*>/', $html, $m2);
            fwrite(STDERR, "\n{$p} => {$code} | 'Edit' occurrences: {$n} | edit-controls: ".count($m2[0])."\n");
            foreach (array_slice($m2[0], 0, 6) as $c) {
                fwrite(STDERR, '   '.substr($c, 0, 160)."\n");
            }
        }
        $this->assertTrue(true);
    }
}
