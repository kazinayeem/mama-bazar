<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Http\Request;

class EmailUnsubscribeController extends Controller
{
    public function showUnsubscribe(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (empty($email)) {
            return redirect()->route('home');
        }

        return view('web.unsubscribe', compact('email', 'token'));
    }

    public function processUnsubscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'nullable|string',
        ]);

        $email = strtolower(trim($request->input('email')));

        // Update User marketing preference
        User::where('email', $email)->update(['marketing_opt_in' => false]);

        // Update Newsletter subscriber status
        Newsletter::where('email', $email)->update(['status' => 'unsubscribed']);

        return back()->with('success', "You have been successfully unsubscribed from Mama Bazar promotional emails. You will continue to receive critical order notifications.");
    }
}
