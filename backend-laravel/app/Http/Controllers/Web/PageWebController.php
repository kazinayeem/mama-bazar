<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendTemplatedEmailJob;
use App\Models\ContactMessage;
use App\Models\PolicyPage;
use App\Services\EmailSettingService;
use App\Services\SeoService;
use App\Support\EmailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageWebController extends Controller
{
    public function show($slug)
    {
        $page = PolicyPage::where('slug', $slug)->first();
        if (! $page) {
            abort(404, 'Page not found');
        }

        $cleanDesc = trim(preg_replace('/\s+/', ' ', strip_tags($page->content ?? '')));
        $seo = SeoService::getForPage(
            $page->title,
            Str::limit($cleanDesc, 155),
            url('/pages/'.$slug),
            'index, follow',
            [$page->title => url('/pages/'.$slug)]
        );

        return view('web.page', compact('page', 'seo'));
    }

    public function about()
    {
        $seo = SeoService::getForAbout();

        return view('web.about', compact('seo'));
    }

    public function faq()
    {
        $seo = SeoService::getForFaq();

        return view('web.faq', compact('seo'));
    }

    public function contact()
    {
        $seo = SeoService::getForPage(
            'Contact Us',
            'Get in touch with Mama Bazar customer support. We are available via phone, email, and live messaging across Bangladesh.',
            route('contact'),
            'index, follow',
            ['Contact Us' => route('contact')]
        );

        return view('web.contact', compact('seo'));
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
