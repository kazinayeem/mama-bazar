<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\BusinessSettingService;
use App\Services\HomepageService;
use App\Services\OrderEmailService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Order::created(fn ($order) => OrderEmailService::handleCreated($order));
        Order::updated(fn ($order) => OrderEmailService::handleUpdated($order));

        Blade::directive('sanitizedHtml', function ($expression) {
            return "<?php echo \\App\\Services\\HtmlSanitizer::forDisplay($expression); ?>";
        });

        View::composer('*', function ($view) {
            try {
                $view->with('business', BusinessSettingService::all());
            } catch (\Throwable $e) {
                $view->with('business', BusinessSettingService::defaults());
            }
        });

        View::composer('layouts.app', function ($view) {
            try {
                $view->with('announcement', HomepageService::getAnnouncement());
            } catch (\Throwable $e) {
                $view->with('announcement', [
                    'enabled' => false,
                    'text' => '',
                    'backgroundColor' => '#1e293b',
                    'textColor' => '#ffffff',
                ]);
            }

            try {
                PaymentMethod::ensureDefaults();
                $footerPayments = PaymentMethod::activeCheckout()
                    ->get(['code', 'name', 'type']);
                $view->with('footerPaymentMethods', $footerPayments);
            } catch (\Throwable $e) {
                $view->with('footerPaymentMethods', collect());
            }

            // Footer social links from centralized business settings
            try {
                $b = BusinessSettingService::all();
                $view->with('footerSocials', $b['social_links'] ?? []);
            } catch (\Throwable $e) {
                $view->with('footerSocials', []);
            }

            // Dynamic footer team members & settings
            try {
                $footerTeamSetting = \App\Models\SiteSetting::where('key', 'footer_team_enabled')->value('value');
                $footerTeamEnabled = $footerTeamSetting === null ? true : in_array((string) $footerTeamSetting, ['1', 'true', 'yes'], true);
                $footerTeamTitle = \App\Models\SiteSetting::where('key', 'footer_team_title')->value('value') ?: 'Leadership & Core Team';
                $footerTeamMembers = $footerTeamEnabled
                    ? \App\Models\TeamMember::query()->active()->public()->inFooter()->ordered()->get()
                    : collect();

                $view->with('footerTeamEnabled', $footerTeamEnabled);
                $view->with('footerTeamTitle', $footerTeamTitle);
                $view->with('footerTeamMembers', $footerTeamMembers);
            } catch (\Throwable $e) {
                $view->with('footerTeamEnabled', false);
                $view->with('footerTeamTitle', 'Leadership & Core Team');
                $view->with('footerTeamMembers', collect());
            }
        });
    }
}
