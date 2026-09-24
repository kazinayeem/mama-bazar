<?php

namespace App\Providers;

use App\Services\HomepageService;
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
        });
    }
}
