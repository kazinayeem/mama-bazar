<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PolicyPage;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class PageWebController extends Controller
{
    public function show($slug)
    {
        $page = PolicyPage::where('slug', $slug)->first();
        if (!$page) {
            abort(404, 'Page not found');
        }

        return view('web.page', compact('page'));
    }

    public function about()
    {
        return view('web.about');
    }

    public function faq()
    {
        return view('web.faq');
    }

    public function contact()
    {
        return view('web.contact');
    }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'message' => 'required|string|max:2000',
        ]);

        ContactMessage::create($data);

        return back()->with('success', 'Your message has been sent successfully. We will get back to you shortly.');
    }
}
