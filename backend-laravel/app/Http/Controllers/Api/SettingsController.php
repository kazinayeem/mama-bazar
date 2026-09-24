<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const STORE_INFO_KEY = 'store_info';
    private const SLIDER_KEY = 'hero_slides';

    private const DEFAULT_STORE_INFO = [
        'storeName' => 'Mama Bazar',
        'email' => 'support@mamabazar.com',
        'primaryPhone' => '01711111111',
        'alternativePhone' => '',
        'contactAddress' => 'House/Road/Area, Dhaka, Bangladesh',
        'city' => 'Dhaka',
        'country' => 'Bangladesh',
    ];

    public function getAll(): JsonResponse
    {
        $data = SiteSetting::all();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function get(string $key): JsonResponse
    {
        $setting = SiteSetting::where('key', $key)->orderBy('id', 'desc')->first();
        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function getStoreInfo(): JsonResponse
    {
        $setting = SiteSetting::where('key', self::STORE_INFO_KEY)->orderBy('id', 'desc')->first();
        $stored = [];
        if ($setting && $setting->value) {
            $decoded = json_decode($setting->value, true);
            if (is_array($decoded)) {
                $stored = $decoded;
            }
        }
        $info = array_merge(self::DEFAULT_STORE_INFO, $stored);
        return response()->json(['success' => true, 'data' => $info]);
    }

    public function set(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
        ]);

        $key = $request->input('key');
        $value = $request->input('value');
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $existing = SiteSetting::where('key', $key)->first();
        if ($existing) {
            $existing->update(['value' => $value]);
            $setting = $existing;
        } else {
            $setting = SiteSetting::create(['key' => $key, 'value' => $value]);
        }

        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function getHeroSlides(): JsonResponse
    {
        $setting = SiteSetting::where('key', self::SLIDER_KEY)->orderBy('id', 'desc')->first();
        $slides = ($setting && $setting->value) ? json_decode($setting->value, true) : [];
        if (!is_array($slides)) {
            $slides = [];
        }
        return response()->json(['success' => true, 'data' => $slides]);
    }

    public function addHeroSlide(Request $request): JsonResponse
    {
        if (!$request->hasFile('image')) {
            return response()->json(['success' => false, 'message' => 'Image is required'], 400);
        }

        $file = $request->file('image');
        $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
        $uploadDir = config('app.upload_dir', 'uploads');
        $file->move(public_path($uploadDir), $filename);

        $imageUrl = url($uploadDir . '/' . $filename);

        $setting = SiteSetting::where('key', self::SLIDER_KEY)->orderBy('id', 'desc')->first();
        $slides = ($setting && $setting->value) ? json_decode($setting->value, true) : [];
        if (!is_array($slides)) {
            $slides = [];
        }
        $slides[] = $imageUrl;

        $jsonSlides = json_encode($slides);
        if ($setting) {
            $setting->update(['value' => $jsonSlides]);
        } else {
            SiteSetting::create(['key' => self::SLIDER_KEY, 'value' => $jsonSlides]);
        }

        return response()->json(['success' => true, 'data' => $slides]);
    }

    public function addHeroSlideByLink(Request $request): JsonResponse
    {
        $link = $request->input('link');
        if (!$link || !is_string($link)) {
            return response()->json(['success' => false, 'message' => 'Image link is required'], 400);
        }

        $setting = SiteSetting::where('key', self::SLIDER_KEY)->orderBy('id', 'desc')->first();
        $slides = ($setting && $setting->value) ? json_decode($setting->value, true) : [];
        if (!is_array($slides)) {
            $slides = [];
        }
        $slides[] = $link;

        $jsonSlides = json_encode($slides);
        if ($setting) {
            $setting->update(['value' => $jsonSlides]);
        } else {
            SiteSetting::create(['key' => self::SLIDER_KEY, 'value' => $jsonSlides]);
        }

        return response()->json(['success' => true, 'data' => $slides]);
    }

    public function deleteHeroSlide(int $index): JsonResponse
    {
        $setting = SiteSetting::where('key', self::SLIDER_KEY)->orderBy('id', 'desc')->first();
        $slides = ($setting && $setting->value) ? json_decode($setting->value, true) : [];
        if (!is_array($slides) || $index < 0 || $index >= count($slides)) {
            return response()->json(['success' => false, 'message' => 'Invalid slide index'], 400);
        }

        array_splice($slides, $index, 1);
        $jsonSlides = json_encode(array_values($slides));

        if ($setting) {
            $setting->update(['value' => $jsonSlides]);
        } else {
            SiteSetting::create(['key' => self::SLIDER_KEY, 'value' => $jsonSlides]);
        }

        return response()->json(['success' => true, 'data' => array_values($slides)]);
    }
}
