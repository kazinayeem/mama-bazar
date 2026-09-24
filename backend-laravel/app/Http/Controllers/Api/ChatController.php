<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function handleChat(Request $request): JsonResponse
    {
        $message = $request->input('message');
        if (!$message || !is_string($message) || !trim($message)) {
            return response()->json(['success' => false, 'message' => 'Message is required'], 400);
        }

        $trimmedMessage = trim($message);
        $vpsUrl = env('AI_CHAT_URL', 'http://13.204.75.195:3000/chat');

        try {
            $response = Http::timeout(25)->post($vpsUrl, [
                'message' => $trimmedMessage,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI server unavailable. Please try again later.',
                ], 502);
            }

            $data = $response->json();
            $reply = (is_array($data) && isset($data['reply'])) ? $data['reply'] : 'দুঃখিত, কোনো উত্তর পাওয়া যায়নি।';

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ], 200);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to AI server. Please try again later.',
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to AI server. Please try again later.',
            ], 502);
        }
    }
}
