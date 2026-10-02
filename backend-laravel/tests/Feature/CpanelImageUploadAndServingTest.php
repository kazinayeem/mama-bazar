<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CpanelImageUploadAndServingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_fallback_route_serves_images_when_symlink_is_bypassed(): void
    {
        Storage::fake('public');

        // Put a fake image in storage/app/public/products/sample.jpg
        Storage::disk('public')->put('products/sample.jpg', 'fake-image-bytes');

        $response = $this->get('/storage/products/sample.jpg');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    public function test_storage_fallback_returns_404_for_missing_file(): void
    {
        $response = $this->get('/storage/products/non-existent-image-12345.jpg');
        $response->assertStatus(404);
    }

    public function test_storage_fallback_returns_304_for_matching_etag(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/etag-sample.jpg', 'sample-content');

        $fullPath = Storage::disk('public')->path('products/etag-sample.jpg');
        $etag = sprintf('"%x-%x"', filemtime($fullPath), filesize($fullPath));

        $response = $this->withHeaders(['If-None-Match' => $etag])->get('/storage/products/etag-sample.jpg');
        $response->assertStatus(304);
    }

    public function test_public_payment_proof_upload_works_without_auth(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.png', 400, 400);

        $response = $this->postJson('/api/uploads/payment-proof', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'provider' => 'local',
        ]);
        $this->assertNotEmpty($response->json('url'));
    }

    public function test_authenticated_upload_and_delete_works(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin@test.com',
            'phone' => '01711111111',
            'password' => bcrypt('Secret123!'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->image('banner.webp', 1200, 400);

        $response = $this->actingAs($user)->postJson('/api/uploads', [
            'file' => $file,
            'folder' => 'banners',
        ]);

        $response->assertStatus(201);
        $url = $response->json('url');
        $path = $response->json('path');

        $this->assertStringStartsWith('/storage/banners/', $url);

        // Delete test
        $delResponse = $this->actingAs($user)->deleteJson("/api/uploads/{$path}");
        $delResponse->assertStatus(200);
        $delResponse->assertJson(['success' => true, 'deleted' => true]);
    }
}
