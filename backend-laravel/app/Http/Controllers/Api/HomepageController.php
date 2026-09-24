<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HomepageService;
use App\Services\JwtService;

class HomepageController extends Controller
{
    private function getOptionalUserId(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $decoded = JwtService::verify($token);
            if ($decoded && isset($decoded['id'])) {
                return (int) $decoded['id'];
            }
        }
        return null;
    }

    public function getHomepage(Request $request)
    {
        $userId = $this->getOptionalUserId($request);
        $data = HomepageService::getHomepage($userId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getHomepageData(Request $request)
    {
        return $this->getHomepage($request);
    }

    public function getConfig()
    {
        $data = HomepageService::getConfig();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function saveConfig(Request $request)
    {
        $data = HomepageService::saveConfig($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function resetConfig()
    {
        $data = HomepageService::resetConfig();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function subscribeNewsletter(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $data = HomepageService::subscribeNewsletter($request->input('email'), $request->input('source'));
        $status = $data['alreadySubscribed'] ? 200 : 201;
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    public function getSubscribers()
    {
        $data = HomepageService::getSubscribers();
        return response()->json(['success' => true, 'data' => $data]);
    }
}
