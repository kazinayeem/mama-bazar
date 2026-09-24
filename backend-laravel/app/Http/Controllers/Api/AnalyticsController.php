<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingIntegration;
use App\Models\TrackingLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    private const FB_GRAPH_API_VERSION = 'v21.0';
    private const FB_EVENT_NAME = 'Purchase';
    private const FB_PLATFORM = 'facebook_capi';
    private const FB_ACTION_SOURCE = 'website';
    private const DEFAULT_CURRENCY = 'BDT';
    private const DEFAULT_CONTENT_TYPE = 'product';

    private function hashSHA256(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }

    public function trackPurchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'contentIds' => 'required',
            'currency' => 'nullable|string',
            'contentType' => 'nullable|string',
            'fbp' => 'nullable|string',
            'fbc' => 'nullable|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $contentIds = is_array($validated['contentIds']) ? $validated['contentIds'] : [$validated['contentIds']];
        $currency = $validated['currency'] ?? self::DEFAULT_CURRENCY;
        $contentType = $validated['contentType'] ?? self::DEFAULT_CONTENT_TYPE;
        $eventId = (string) Str::uuid();

        $activeIntegrations = MarketingIntegration::where('status', 'active')->get();
        $facebookPixelId = null;
        $facebookAccessToken = null;
        $facebookTestEventCode = null;

        foreach ($activeIntegrations as $row) {
            if ($row->type === 'facebook_pixel' && $row->pixel_id) {
                $facebookPixelId = $row->pixel_id;
            }
            if ($row->type === 'facebook_conversion_api') {
                if ($row->access_token) $facebookAccessToken = $row->access_token;
                if ($row->test_event_code) $facebookTestEventCode = $row->test_event_code;
            }
        }

        if (!$facebookPixelId || !$facebookAccessToken) {
            return response()->json([
                'success' => true,
                'data' => ['sent' => false, 'reason' => 'Facebook Conversion API not configured'],
            ]);
        }

        $userIp = $request->header('x-forwarded-for') ? explode(',', $request->header('x-forwarded-for'))[0] : $request->ip();
        $userAgent = $request->header('user-agent') ?? '';

        $userData = [
            'client_ip_address' => trim($userIp),
            'client_user_agent' => $userAgent,
        ];

        if (!empty($validated['fbp'])) $userData['fbp'] = $validated['fbp'];
        if (!empty($validated['fbc'])) $userData['fbc'] = $validated['fbc'];
        if (!empty($validated['email'])) $userData['em'] = [$this->hashSHA256($validated['email'])];
        if (!empty($validated['phone'])) $userData['ph'] = [$this->hashSHA256($validated['phone'])];

        $eventData = [
            'event_name' => self::FB_EVENT_NAME,
            'event_time' => time(),
            'event_id' => $eventId,
            'action_source' => self::FB_ACTION_SOURCE,
            'user_data' => $userData,
            'custom_data' => [
                'currency' => $currency,
                'value' => (float) $validated['value'],
                'content_ids' => array_map('strval', $contentIds),
                'content_type' => $contentType,
            ],
        ];

        $payload = ['data' => [$eventData]];
        if ($facebookTestEventCode) {
            $payload['test_event_code'] = $facebookTestEventCode;
        }

        $url = "https://graph.facebook.com/" . self::FB_GRAPH_API_VERSION . "/{$facebookPixelId}/events?access_token=" . urlencode($facebookAccessToken);

        try {
            $response = Http::post($url, $payload);
            $result = $response->json();

            if (!$response->successful()) {
                TrackingLog::create([
                    'event_name' => self::FB_EVENT_NAME,
                    'platform' => self::FB_PLATFORM,
                    'payload' => $payload,
                    'status' => 'failed',
                    'error_message' => json_encode($result),
                ]);
                return response()->json(['success' => true, 'data' => ['sent' => false, 'error' => $result]]);
            }

            TrackingLog::create([
                'event_name' => self::FB_EVENT_NAME,
                'platform' => self::FB_PLATFORM,
                'payload' => $payload,
                'status' => 'success',
            ]);

            return response()->json(['success' => true, 'data' => ['sent' => true, 'result' => $result]]);
        } catch (\Throwable $e) {
            TrackingLog::create([
                'event_name' => self::FB_EVENT_NAME,
                'platform' => self::FB_PLATFORM,
                'payload' => $payload,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json(['success' => true, 'data' => ['sent' => false, 'error' => $e->getMessage()]]);
        }
    }
}
