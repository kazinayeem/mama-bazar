<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_script_tags_and_event_handlers(): void
    {
        $dirty = '<p>Hello<script>alert(1)</script></p><img src=x onerror="alert(1)">';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('<script', strtolower($clean ?? ''));
        $this->assertStringNotContainsString('onerror', strtolower($clean ?? ''));
        $this->assertStringContainsString('Hello', $clean ?? '');
    }

    public function test_strips_iframe_and_javascript_urls(): void
    {
        $dirty = '<p><a href="javascript:alert(1)">Click</a></p><iframe src="https://evil.test"></iframe>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('iframe', strtolower($clean ?? ''));
        $this->assertStringNotContainsString('javascript:', strtolower($clean ?? ''));
    }

    public function test_rejects_data_and_vbscript_urls(): void
    {
        $dirty = '<p><a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>'
            .'<a href="vbscript:msgbox(1)">y</a></p>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('data:', strtolower($clean ?? ''));
        $this->assertStringNotContainsString('vbscript:', strtolower($clean ?? ''));
    }

    public function test_allows_safe_formatting_and_local_images(): void
    {
        $html = '<h2>Title</h2><p><strong>Bold</strong> and <em>italic</em></p>'
            .'<ul><li>One</li></ul>'
            .'<a href="https://example.com" target="_blank">Link</a>'
            .'<img src="/storage/products/descriptions/demo.webp" alt="Demo">'
            .'<table><tr><th>A</th></tr><tr><td>1</td></tr></table>';

        $clean = HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<h2>', $clean ?? '');
        $this->assertStringContainsString('<strong>', $clean ?? '');
        $this->assertStringContainsString('/storage/products/descriptions/demo.webp', $clean ?? '');
        $this->assertStringContainsString('alt="Demo"', $clean ?? '');
        $this->assertStringContainsString('noopener', $clean ?? '');
        $this->assertStringContainsString('<table>', $clean ?? '');
    }

    public function test_strips_external_and_data_images(): void
    {
        $dirty = '<p><img src="https://evil.test/x.png" alt="x">'
            .'<img src="data:image/png;base64,abc" alt="y">'
            .'<img src="/storage/ok.png" alt="ok"></p>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('evil.test', $clean ?? '');
        $this->assertStringNotContainsString('data:image', strtolower($clean ?? ''));
        $this->assertStringContainsString('/storage/ok.png', $clean ?? '');
    }

    public function test_wraps_plain_text_without_treating_as_html(): void
    {
        $plain = "Line one\nLine two <script>alert(1)</script>";
        $clean = HtmlSanitizer::clean($plain);

        $this->assertStringStartsWith('<p>', $clean ?? '');
        $this->assertStringContainsString('&lt;script&gt;', $clean ?? '');
        $this->assertStringNotContainsString('<script>', $clean ?? '');
        $this->assertStringContainsString('<br', $clean ?? '');
    }

    public function test_for_display_sanitizes_xss(): void
    {
        $out = HtmlSanitizer::forDisplay('<p onclick="alert(1)">Hi</p><svg/onload=alert(1)>');
        $this->assertStringNotContainsString('onclick', strtolower($out));
        $this->assertStringNotContainsString('<svg', strtolower($out));
        $this->assertStringContainsString('Hi', $out);
    }
}
