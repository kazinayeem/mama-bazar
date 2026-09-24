<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Services\MediaStorageService;

class BannerController extends Controller
{
    public function getAll(Request $request)
    {
        $query = Banner::query();
        if ($request->filled('position')) {
            $query->where('position', $request->input('position'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'active');
        }

        $banners = $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $banners]);
    }

    public function getById($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function create(Request $request)
    {
        $data = $request->all();

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'banners');
            $data['image'] = $upload['url'];
        }
        if ($request->hasFile('imageTablet')) {
            $upload = MediaStorageService::uploadFile($request->file('imageTablet'), 'banners');
            $data['image_tablet'] = $upload['url'];
        }
        if ($request->hasFile('imageMobile')) {
            $upload = MediaStorageService::uploadFile($request->file('imageMobile'), 'banners');
            $data['image_mobile'] = $upload['url'];
        }

        if (empty($data['image'])) {
            return response()->json(['success' => false, 'message' => 'Image is required'], 400);
        }

        $banner = Banner::create($data);
        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
        }

        $data = $request->all();

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'banners');
            $data['image'] = $upload['url'];
        }
        if ($request->hasFile('imageTablet')) {
            $upload = MediaStorageService::uploadFile($request->file('imageTablet'), 'banners');
            $data['image_tablet'] = $upload['url'];
        }
        if ($request->hasFile('imageMobile')) {
            $upload = MediaStorageService::uploadFile($request->file('imageMobile'), 'banners');
            $data['image_mobile'] = $upload['url'];
        }

        $banner->update($data);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function remove($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner not found'], 404);
        }

        $banner->delete();
        return response()->json(['success' => true, 'message' => 'Banner deleted']);
    }
}
