<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MediaStorageService;

class UploadController extends Controller
{
    /**
     * Upload single file.
     * Route: POST /api/uploads
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|max:20480',
            'folder' => 'nullable|string|max:80',
        ]);

        $folder = $request->input('folder', 'general');
        $file = $request->file('file');
        $result = MediaStorageService::uploadFile($file, $folder);

        return response()->json([
            'success'  => true,
            'url'      => $result['url'],
            'path'     => $result['path'],
            'publicId' => $result['publicId'] ?? $result['path'],
            'provider' => 'local',
            'data'     => $result,
        ], 201);
    }

    /**
     * Upload multiple files.
     * Route: POST /api/uploads/multiple
     */
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'files'   => 'nullable|array',
            'files.*' => 'file|max:20480',
            'file'    => 'nullable|file|max:20480',
            'folder'  => 'nullable|string|max:80',
        ]);

        $folder = $request->input('folder', 'general');
        $files = $request->file('files') ?? ($request->hasFile('file') ? [$request->file('file')] : []);

        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No files provided for upload.',
            ], 422);
        }

        $results = [];
        foreach ($files as $f) {
            $results[] = MediaStorageService::uploadFile($f, $folder);
        }

        return response()->json([
            'success' => true,
            'urls'    => array_column($results, 'url'),
            'data'    => $results,
        ], 201);
    }

    /**
     * Delete an uploaded file by path/publicId.
     * Route: DELETE /api/uploads/{publicId}
     */
    public function delete(Request $request, string $publicId)
    {
        $deleted = MediaStorageService::deleteFile($publicId);

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

    /**
     * Upload customer payment proof during checkout.
     * Route: POST /api/uploads/payment-proof
     */
    public function uploadPaymentProof(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|max:10240',
        ]);

        $file = $request->file('file');
        $result = MediaStorageService::uploadFile($file, 'payments');

        return response()->json([
            'success'  => true,
            'url'      => $result['url'],
            'publicId' => $result['publicId'] ?? null,
            'provider' => 'local',
            'data'     => $result,
        ], 201);
    }
}
