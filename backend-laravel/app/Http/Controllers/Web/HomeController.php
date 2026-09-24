<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $homepageData = HomepageService::getHomepage();
        $config = HomepageService::getConfig();

        return view('web.home', [
            'homepageData' => $homepageData,
            'config' => $config,
        ]);
    }

    public function subscribeNewsletter(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $result = HomepageService::subscribeNewsletter(
                $request->input('email'),
                $request->input('source', 'homepage')
            );
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            $status = $result['alreadySubscribed'] ? 200 : 201;

            return response()->json(['success' => true, 'data' => $result], $status);
        }

        $message = $result['alreadySubscribed']
            ? 'You are already subscribed!'
            : 'Subscribed! Check your inbox for updates.';

        return back()->with('success', $message);
    }
}
