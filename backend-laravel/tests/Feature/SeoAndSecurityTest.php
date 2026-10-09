<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_requests(): void
    {
        $response = $this->withServerVariables(['HTTPS' => 'on'])->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }

    public function test_custom_404_page_renders_html_for_web_routes(): void
    {
        $response = $this->get('/non-existent-page-xyz-'.uniqid());

        $response->assertStatus(404);
        $response->assertSee('Page Not Found');
        $response->assertSee('Search products, groceries, electronics…');
        $response->assertSee('Back to Homepage');
        $response->assertSee('Browse Shop');
    }

    public function test_api_404_returns_json_response(): void
    {
        $response = $this->getJson('/api/non-existent-endpoint-xyz');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_homepage_contains_h1_and_critical_responsive_media_queries(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('@media (min-width: 640px)', $html);
        $this->assertStringContainsString('@media (min-width: 1024px)', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
    }
}
