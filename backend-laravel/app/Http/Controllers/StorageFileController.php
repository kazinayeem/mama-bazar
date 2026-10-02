<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageFileController extends Controller
{
    /**
     * Safely serve public storage files when symlink is absent or broken (e.g. on cPanel shared hosting).
     *
     * @return Response
     */
    public function show(Request $request, string $path)
    {
        // Prevent path traversal
        $normalized = str_replace('\\', '/', $path);
        if (str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            abort(400, 'Invalid path');
        }

        // Candidate 1: public storage disk root (respects config/filesystems.php and test fakes)
        $disk = Storage::disk('public');
        $filePath = $disk->path($normalized);

        // Candidate 2: direct storage/app/public
        if (! file_exists($filePath)) {
            $storageCandidate = storage_path('app/public/'.$normalized);
            if (file_exists($storageCandidate) && ! is_dir($storageCandidate)) {
                $filePath = $storageCandidate;
            }
        }

        // Candidate 3: public/storage/... (in case real folder exists in public)
        if (! file_exists($filePath)) {
            $publicCandidate = public_path('storage/'.$normalized);
            if (file_exists($publicCandidate) && ! is_dir($publicCandidate)) {
                $filePath = $publicCandidate;
            }
        }

        if (! file_exists($filePath) || is_dir($filePath)) {
            abort(404, 'File not found');
        }

        return $this->serveFile($request, $filePath);
    }

    /**
     * Safely serve legacy /uploads/... files.
     *
     * @return Response
     */
    public function showUploads(Request $request, string $path)
    {
        $normalized = str_replace('\\', '/', $path);
        if (str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            abort(400, 'Invalid path');
        }

        $candidates = [
            public_path('uploads/'.$normalized),
            storage_path('app/public/'.$normalized),
            storage_path('app/public/uploads/'.$normalized),
        ];

        $filePath = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && ! is_dir($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        if (! $filePath) {
            abort(404, 'File not found');
        }

        return $this->serveFile($request, $filePath);
    }

    /**
     * Build response with proper MIME type, cache headers, and HTTP 304 handling.
     */
    protected function serveFile(Request $request, string $filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $extMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'ico' => 'image/x-icon',
            'avif' => 'image/avif',
        ];

        $mime = $extMimes[$ext] ?? (mime_content_type($filePath) ?: 'application/octet-stream');
        $size = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = sprintf('"%x-%x"', $lastModified, $size);

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        ];

        // Conditional GET handling (304 Not Modified)
        $ifNoneMatch = $request->header('If-None-Match');
        $ifModifiedSince = $request->header('If-Modified-Since');

        if ($ifNoneMatch && trim($ifNoneMatch, '"') === trim($etag, '"')) {
            return response('', 304, $headers);
        }

        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return response('', 304, $headers);
        }

        return response()->file($filePath, $headers);
    }
}
