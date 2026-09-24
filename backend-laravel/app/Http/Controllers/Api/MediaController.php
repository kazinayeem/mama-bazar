<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediaAsset;
use App\Services\MediaStorageService;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file']);
        $folder = $request->input('folder', 'general');
        $alt = $request->input('alt');
        $user = $request->attributes->get('auth_user');

        $file = $request->file('file');
        $result = MediaStorageService::uploadFile($file, $folder);

        $asset = MediaAsset::create([
            'url' => $result['url'],
            'public_id' => $result['publicId'] ?? null,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'provider' => 'local',
            'folder' => $folder,
            'alt' => $alt,
            'uploader_id' => $user['id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $asset], 201);
    }

    public function uploadMultiple(Request $request)
    {
        $request->validate(['files' => 'required|array']);
        $folder = $request->input('folder', 'general');
        $user = $request->attributes->get('auth_user');

        $assets = [];
        foreach ($request->file('files') as $file) {
            $result = MediaStorageService::uploadFile($file, $folder);
            $asset = MediaAsset::create([
                'url' => $result['url'],
                'public_id' => $result['publicId'] ?? null,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'provider' => 'local',
                'folder' => $folder,
                'uploader_id' => $user['id'] ?? null,
            ]);
            $assets[] = $asset;
        }

        return response()->json(['success' => true, 'data' => $assets], 201);
    }

    public function getAll(Request $request)
    {
        $query = MediaAsset::query();
        if ($request->filled('folder')) {
            $query->where('folder', $request->input('folder'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('filename', 'like', "%{$s}%")
                  ->orWhere('alt', 'like', "%{$s}%");
            });
        }

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 24);

        $paginator = $query->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function getFolders()
    {
        $folders = MediaAsset::select('folder')
            ->distinct()
            ->pluck('folder')
            ->toArray();

        return response()->json(['success' => true, 'data' => array_values(array_unique(array_merge(['general', 'products', 'banners'], $folders)))]);
    }

    public function config()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'cloudinaryConfigured' => false,
                'cloudName' => null,
                'storage' => 'local',
            ],
        ]);
    }

    public function getById($id)
    {
        $asset = MediaAsset::find($id);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $asset]);
    }

    public function update(Request $request, $id)
    {
        $asset = MediaAsset::find($id);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }

        if ($request->has('alt')) $asset->alt = $request->input('alt');
        if ($request->has('folder')) $asset->folder = $request->input('folder');
        $asset->save();

        return response()->json(['success' => true, 'data' => $asset]);
    }

    public function remove($id)
    {
        $asset = MediaAsset::find($id);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }

        MediaStorageService::deleteFile($asset->url);
        $asset->delete();

        return response()->json(['success' => true, 'message' => 'Asset deleted']);
    }
}
