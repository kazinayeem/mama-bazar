<?php

namespace App\Services;

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
     * Upload an uploaded file or file from path to public storage disk.
     *
     * @param UploadedFile|string $file
     * @param string $folder e.g. 'products', 'categories', 'banners', 'users', 'media'
     * @return array
     */
    public static function uploadFile(UploadedFile|string $file, string $folder = 'general'): array
    {
        $disk = Storage::disk('public');

        if ($file instanceof UploadedFile) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (empty($extension)) {
                $extension = strtolower($file->guessExtension() ?? 'jpg');
            }
            $filename = time() . '-' . Str::random(12) . '.' . $extension;
            $path = $folder . '/' . $filename;

            $disk->putFileAs($folder, $file, $filename);
            $size = $file->getSize();
            $mimeType = $file->getClientMimeType();
        } else {
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
        if ($oldUrl) {
            self::deleteFile($oldUrl);
        }

        return self::uploadFile($newFile, $folder);
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

        // Extract relative path from URL (e.g. /storage/products/123.jpg -> products/123.jpg)
        $path = $url;
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, strlen('/storage/'));
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        } elseif (str_starts_with($path, '/uploads/')) {
            // Legacy /uploads/ path
            $uploadFile = public_path(ltrim($path, '/'));
            if (file_exists($uploadFile)) {
                @unlink($uploadFile);
                return true;
            }
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
