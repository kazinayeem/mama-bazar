<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    /**
     * Allowed image extensions and max sizes.
     */
    protected static array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif', 'pdf'];
    protected static int $maxSizeBytes = 10 * 1024 * 1024; // 10MB

    /**
     * Normalize a stored path or /storage/... URL to a public disk-relative path.
     * Returns null for external/blob/data URLs that are not local files.
     */
    public static function toRelativePath(?string $pathOrUrl): ?string
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return null;
        }

        $path = trim($pathOrUrl);

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, 'blob:')
            || str_starts_with($path, 'data:')
        ) {
            // Only treat same-origin /storage/ absolute URLs as local
            if (preg_match('#/storage/(.+)$#', $path, $m)) {
                return ltrim($m[1], '/');
            }

            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return ltrim(substr($path, strlen('storage/')), '/');
        }

        if (str_starts_with($path, '/uploads/')) {
            return null; // handled separately in deleteFile
        }

        return ltrim($path, '/');
    }

    /**
     * Public URL for display (/storage/...). Keeps http(s) and legacy /storage paths intact.
     */
    public static function toPublicUrl(?string $pathOrUrl): ?string
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return null;
        }

        $path = trim($pathOrUrl);

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, 'blob:')
            || str_starts_with($path, 'data:')
            || str_starts_with($path, '/storage/')
            || str_starts_with($path, '/uploads/')
            || str_starts_with($path, '/brand')
        ) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/' . $path;
        }

        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Store via Laravel's UploadedFile::store() for safe unique filenames.
     *
     * @return array{url: string, path: string, filename: string, provider: string, publicId: string, size: int|null, mimeType: string|null}
     */
    public static function storeUploaded(UploadedFile $file, string $folder = 'products/variants'): array
    {
        $folder = trim(preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $folder) ?: 'products/variants', '/');
        $path = $file->store($folder, 'public');

        return [
            'url' => '/storage/' . $path,
            'path' => $path,
            'filename' => basename($path),
            'provider' => 'local',
            'publicId' => $path,
            'size' => $file->getSize(),
            'mimeType' => $file->getClientMimeType(),
        ];
    }

    /**
     * Upload an uploaded file or file from path to public storage disk.
     *
     * @param UploadedFile|string $file
     * @param string $folder e.g. 'products', 'categories', 'banners', 'users', 'media'
     * @return array
     */
    public static function uploadFile(UploadedFile|string $file, string $folder = 'general'): array
    {
        if ($file instanceof UploadedFile) {
            return self::storeUploaded($file, $folder);
        }

        $disk = Storage::disk('public');

        // String path
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'jpg';
        $filename = time() . '-' . Str::random(12) . '.' . $extension;
        $path = $folder . '/' . $filename;

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $disk->put($path, $content);
            $size = filesize($file);
            $mimeType = mime_content_type($file) ?: 'application/octet-stream';
        } else {
            $filename = basename($file);
            $path = $folder . '/' . $filename;
            $size = 0;
            $mimeType = 'image/jpeg';
        }

        $url = '/storage/' . $path;

        return [
            'url' => $url,
            'path' => $path,
            'filename' => $filename,
            'provider' => 'local',
            'publicId' => $path,
            'size' => $size,
            'mimeType' => $mimeType,
        ];
    }

    /**
     * Replace an old file with a new file. Automatically deletes the old file if it exists.
     */
    public static function replaceFile(UploadedFile|string $newFile, ?string $oldUrl, string $folder = 'general'): array
    {
        $result = $newFile instanceof UploadedFile
            ? self::storeUploaded($newFile, $folder)
            : self::uploadFile($newFile, $folder);

        if ($oldUrl) {
            self::deleteIfUnreferenced($oldUrl);
        }

        return $result;
    }

    /**
     * Whether a local storage path is still referenced by products or variants.
     */
    public static function isPathReferenced(?string $pathOrUrl, ?int $exceptVariantId = null): bool
    {
        $relative = self::toRelativePath($pathOrUrl);
        if (!$relative) {
            return true; // treat unknown/external as referenced → do not delete
        }

        $candidates = array_values(array_unique(array_filter([
            $relative,
            '/storage/' . $relative,
            'storage/' . $relative,
        ])));

        $variantQuery = ProductVariant::query()->where(function ($q) use ($candidates) {
            foreach ($candidates as $c) {
                $q->orWhere('thumbnail', $c);
            }
        });

        if ($exceptVariantId) {
            $variantQuery->where('id', '!=', $exceptVariantId);
        }

        if ($variantQuery->exists()) {
            return true;
        }

        // JSON images arrays may store either form
        foreach ($candidates as $c) {
            if (ProductVariant::where('images', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $c) . '%')->exists()) {
                return true;
            }
            if (Product::where('images', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $c) . '%')->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete a local file only when nothing still references it.
     */
    public static function deleteIfUnreferenced(?string $pathOrUrl, ?int $exceptVariantId = null): bool
    {
        if (!$pathOrUrl) {
            return false;
        }

        if (self::isPathReferenced($pathOrUrl, $exceptVariantId)) {
            return false;
        }

        return self::deleteFile($pathOrUrl);
    }

    /**
     * Delete an existing file by URL or path.
     */
    public static function deleteFile(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $disk = Storage::disk('public');

        if (str_starts_with($url, '/uploads/')) {
            $uploadFile = public_path(ltrim($url, '/'));
            if (file_exists($uploadFile)) {
                @unlink($uploadFile);

                return true;
            }

            return false;
        }

        $path = self::toRelativePath($url);
        if (!$path) {
            return false;
        }

        if ($disk->exists($path)) {
            return $disk->delete($path);
        }

        return false;
    }

    /**
     * Delete asset for backwards-compatibility with controllers.
     */
    public static function deleteAsset(?string $publicId = null, ?string $url = null): array
    {
        $deleted = false;
        if ($url) {
            $deleted = self::deleteFile($url);
        } elseif ($publicId) {
            $deleted = self::deleteFile($publicId);
        }

        return [
            'deleted' => $deleted,
            'provider' => 'local',
        ];
    }
}
