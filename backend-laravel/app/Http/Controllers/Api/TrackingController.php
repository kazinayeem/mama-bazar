<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingIntegration;
use App\Models\TrackingLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    private const PIXEL_ID_PATTERNS = [
        'facebook_pixel' => '/^\d{10,20}$/',
        'google_analytics' => '/^G-[A-Z0-9]{6,14}$/',
        'google_tag_manager' => '/^GTM-[A-Z0-9]{4,12}$/',
        'tiktok_pixel' => '/^[A-Z0-9]{10,30}$/i',
    ];

    private function sanitizeScript(string $script): string
    {
        $sanitized = preg_replace('/<\/?iframe[^>]*>/i', '', $script);
        $sanitized = preg_replace('/on\w+\s*=/i', '', $sanitized);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        $sanitized = preg_replace('/eval\s*\(/i', '', $sanitized);
        $sanitized = preg_replace('/document\.cookie/i', '', $sanitized);
        $sanitized = preg_replace('/document\.write/i', '', $sanitized);
        return preg_replace('/window\.location\s*=/i', '', $sanitized);
    }

    private function validatePixelId(string $type, string $pixelId): bool
    {
        if (!isset(self::PIXEL_ID_PATTERNS[$type])) {
            return true;
        }
        return (bool) preg_match(self::PIXEL_ID_PATTERNS[$type], $pixelId);
    }

    public function getConfig(): JsonResponse
    {
        $active = MarketingIntegration::where('status', 'active')->get();
        $config = [
            'customHeadScripts' => [],
            'customBodyScripts' => [],
        ];

        foreach ($active as $row) {
            switch ($row->type) {
                case 'google_tag_manager':
                    if ($row->pixel_id) $config['gtmId'] = $row->pixel_id;
                    break;
                case 'google_analytics':
                    if ($row->pixel_id) $config['gaMeasurementId'] = $row->pixel_id;
                    break;
                case 'facebook_pixel':
                    if ($row->pixel_id) $config['facebookPixelId'] = $row->pixel_id;
                    break;
                case 'tiktok_pixel':
                    if ($row->pixel_id) $config['tiktokPixelId'] = $row->pixel_id;
                    break;
                case 'custom_script':
                    if ($row->script_code) $config['customHeadScripts'][] = $row->script_code;
                    break;
            }
        }

        return response()->json(['success' => true, 'data' => $config]);
    }

    public function getAll(): JsonResponse
    {
        $data = MarketingIntegration::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getById(int $id): JsonResponse
    {
        $data = MarketingIntegration::find($id);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:google_tag_manager,google_analytics,facebook_pixel,facebook_conversion_api,tiktok_pixel,custom_script',
            'pixelId' => 'nullable|string|max:100',
            'scriptCode' => 'nullable|string',
            'accessToken' => 'nullable|string',
            'testEventCode' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive',
        ]);

        $type = $validated['type'];
        $pixelId = $validated['pixelId'] ?? null;
        $scriptCode = $validated['scriptCode'] ?? null;
        $accessToken = $validated['accessToken'] ?? null;

        $typesNeedingPixelId = ['google_tag_manager', 'google_analytics', 'facebook_pixel', 'tiktok_pixel'];
        if (in_array($type, $typesNeedingPixelId) && !$pixelId) {
            return response()->json(['success' => false, 'message' => 'Pixel/Measurement ID is required'], 400);
        }
        if ($type === 'custom_script' && !$scriptCode) {
            return response()->json(['success' => false, 'message' => 'Script code is required'], 400);
        }
        if ($type === 'facebook_conversion_api' && !$accessToken) {
            return response()->json(['success' => false, 'message' => 'Access token is required'], 400);
        }

        if ($pixelId && !$this->validatePixelId($type, $pixelId)) {
            return response()->json(['success' => false, 'message' => "Invalid pixel ID format for {$type}"], 400);
        }

        $item = MarketingIntegration::create([
            'name' => $validated['name'],
            'type' => $type,
            'pixel_id' => $pixelId,
            'script_code' => ($type === 'custom_script' && $scriptCode) ? $this->sanitizeScript($scriptCode) : null,
            'access_token' => $accessToken,
            'test_event_code' => $validated['testEventCode'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $integration = MarketingIntegration::find($id);
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:google_tag_manager,google_analytics,facebook_pixel,facebook_conversion_api,tiktok_pixel,custom_script',
            'pixelId' => 'nullable|string|max:100',
            'scriptCode' => 'nullable|string',
            'accessToken' => 'nullable|string',
            'testEventCode' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $type = $request->input('type', $integration->type);
        $pixelId = $request->input('pixelId', $integration->pixel_id);

        if ($pixelId && !$this->validatePixelId($type, $pixelId)) {
            return response()->json(['success' => false, 'message' => "Invalid pixel ID format for {$type}"], 400);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $validated['name'];
        if ($request->has('type')) $updateData['type'] = $validated['type'];
        if ($request->has('pixelId')) $updateData['pixel_id'] = $validated['pixelId'];
        if ($request->has('scriptCode')) {
            $updateData['script_code'] = $validated['scriptCode'] ? $this->sanitizeScript($validated['scriptCode']) : null;
        }
        if ($request->has('accessToken')) $updateData['access_token'] = $validated['accessToken'];
        if ($request->has('testEventCode')) $updateData['test_event_code'] = $validated['testEventCode'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $integration->update($updateData);

        return response()->json(['success' => true, 'data' => $integration->fresh()]);
    }

    public function remove(int $id): JsonResponse
    {
        MarketingIntegration::destroy($id);
        return response()->json(['success' => true, 'message' => 'Integration deleted']);
    }

    public function getLogs(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?: 50);
        $logs = TrackingLog::orderBy('created_at', 'desc')->limit($limit)->get();
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
