<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendTemplatedEmailJob;
use App\Models\ContactMessage;
use App\Models\PolicyPage;
use App\Services\EmailSettingService;
use App\Support\EmailQueue;
use Illuminate\Http\Request;

class PageWebController extends Controller
{
    public function show($slug)
    {
        $page = PolicyPage::where('slug', $slug)->first();
        if (! $page) {
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

        $message = ContactMessage::create($data);
        $this->queueContactEmails($message->id, $data);

        return back()->with('success', 'Your message has been sent successfully. We will get back to you shortly.');
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, message: string}  $data
     */
    private function queueContactEmails(int $messageId, array $data): void
    {
        $values = [
            'customer_name' => $data['name'],
            'contact_name' => $data['name'],
            'contact_phone' => $data['phone'],
            'contact_email' => $data['email'] ?? '',
            'contact_message' => $data['message'],
        ];

        if (! empty($data['email']) && EmailSettingService::isAutomationEnabled('contact_form')) {
            EmailQueue::dispatch(new SendTemplatedEmailJob(
                'contact_form_notification', $data['email'], $data['name'], $values, 'notification',
                ['dedupe_key' => "contact:{$messageId}:reply"]
            ));
        }

        $admin = EmailSettingService::adminNotificationAddress();
        if ($admin && EmailSettingService::isAutomationEnabled('contact_admin')) {
            EmailQueue::dispatch(new SendTemplatedEmailJob(
                'contact_form_admin', $admin, null, $values, 'notification',
                ['dedupe_key' => "contact:{$messageId}:admin"]
            ));
        }
    }
}
