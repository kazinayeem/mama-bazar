<?php

namespace App\Providers;

use App\Services\HomepageService;
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

        \App\Models\Order::created(fn ($order) => \App\Services\OrderEmailService::handleCreated($order));
        \App\Models\Order::updated(fn ($order) => \App\Services\OrderEmailService::handleUpdated($order));

        Blade::directive('sanitizedHtml', function ($expression) {
            return "<?php echo \\App\\Services\\HtmlSanitizer::forDisplay($expression); ?>";
        });

        View::composer('*', function ($view) {
            try {
                $view->with('business', \App\Services\BusinessSettingService::all());
            } catch (\Throwable $e) {
                $view->with('business', \App\Services\BusinessSettingService::defaults());
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
                \App\Models\PaymentMethod::ensureDefaults();
                $footerPayments = \App\Models\PaymentMethod::activeCheckout()
                    ->get(['code', 'name', 'type']);
                $view->with('footerPaymentMethods', $footerPayments);
            } catch (\Throwable $e) {
                $view->with('footerPaymentMethods', collect());
            }

            // Footer social links from centralized business settings
            try {
                $b = \App\Services\BusinessSettingService::all();
                $view->with('footerSocials', $b['social_links'] ?? []);
            } catch (\Throwable $e) {
                $view->with('footerSocials', []);
            }
        });
    }
}
