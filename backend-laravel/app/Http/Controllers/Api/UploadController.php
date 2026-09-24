<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MediaStorageService;

class UploadController extends Controller
{
    public function uploadPaymentProof(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|max:10240',
        ]);

        $file = $request->file('file');
        $result = MediaStorageService::uploadFile($file, 'payments');

        return response()->json([
            'success' => true,
            'url' => $result['url'],
            'publicId' => $result['publicId'] ?? null,
            'provider' => 'local',
        ], 201);
    }
}
