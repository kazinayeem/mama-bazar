<?php

use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminAdvancedAnalyticsController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminCatalogWebController;
use App\Http\Controllers\Admin\AdminCategoryWebController;
use App\Http\Controllers\Admin\AdminCouponWebController;
use App\Http\Controllers\Admin\AdminCustomerWebController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailCampaignController;
use App\Http\Controllers\Admin\AdminEmailController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminInvitationController;
use App\Http\Controllers\Admin\AdminModuleWebController;
use App\Http\Controllers\Admin\AdminOrderWebController;
use App\Http\Controllers\Admin\AdminProductWebController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminReviewWebController;
use App\Http\Controllers\Admin\AdminSeoController;
use App\Http\Controllers\Admin\AdminSettingWebController;
use App\Http\Controllers\Admin\AdminTeamWebController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\Web\AccountEmailController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CustomerAccountController;
use App\Http\Controllers\Web\CustomerInvoiceController;
use App\Http\Controllers\Web\EmailUnsubscribeController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrderTrackingController;
use App\Http\Controllers\Web\PageWebController;
use App\Http\Controllers\Web\ProductWebController;
use App\Http\Controllers\Web\SeoController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\TeamWebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (User-Facing Blade UI)
|--------------------------------------------------------------------------
*/

Route::get('/', function (Request $request) {
    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'service' => 'Mama Bazar API',
            'status' => 'running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    return app(HomeController::class)->index();
})->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/suggest', [ShopController::class, 'suggest'])->name('shop.suggest');
Route::get('/products/{slug}', [ProductWebController::class, 'show'])->name('products.show');
Route::post('/products/{slug}/reviews', [ProductWebController::class, 'storeReview'])
    ->middleware(['auth', 'email.verified'])
    ->name('products.review');
Route::put('/products/{slug}/reviews/{id}', [ProductWebController::class, 'updateReview'])
    ->middleware(['auth', 'email.verified'])
    ->name('products.review.update');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::post('/checkout/validate-coupon', [CheckoutController::class, 'validateCoupon'])->name('checkout.coupon');
Route::post('/checkout/track', [CheckoutController::class, 'trackProgress'])->middleware('throttle:60,1')->name('checkout.track');
Route::get('/order/success', [CheckoutController::class, 'success'])->name('order.success');
Route::get('/track', [OrderTrackingController::class, 'index'])->name('track');
Route::post('/newsletter/subscribe', [HomeController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

// Technical SEO & Sitemaps
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemapIndex'])->name('seo.sitemap');
Route::get('/sitemap-products.xml', [SeoController::class, 'sitemapProducts'])->name('seo.sitemap.products');
Route::get('/sitemap-categories.xml', [SeoController::class, 'sitemapCategories'])->name('seo.sitemap.categories');
Route::get('/sitemap-brands.xml', [SeoController::class, 'sitemapBrands'])->name('seo.sitemap.brands');
Route::get('/sitemap-pages.xml', [SeoController::class, 'sitemapPages'])->name('seo.sitemap.pages');

// Canonical redirects for category/brand/collection friendly aliases
Route::get('/category/{slug}', fn ($slug) => redirect()->route('shop', ['category' => $slug], 301))->name('category.show');
Route::get('/categories/{slug}', fn ($slug) => redirect()->route('shop', ['category' => $slug], 301));
Route::get('/brand/{slug}', fn ($slug) => redirect()->route('shop', ['brand' => $slug], 301))->name('brand.show');
Route::get('/brands/{slug}', fn ($slug) => redirect()->route('shop', ['brand' => $slug], 301));
Route::get('/collection/{slug}', fn ($slug) => redirect()->route('shop', ['collection' => $slug], 301))->name('collection.show');

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::get('/auth/login', [AuthWebController::class, 'showLogin']);
Route::post('/login', [AuthWebController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
Route::get('/auth/register', [AuthWebController::class, 'showRegister']);
Route::post('/register', [AuthWebController::class, 'register'])->middleware('throttle:6,1')->name('register.submit');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// Email verification, password reset and email-code sign-in
Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [AuthWebController::class, 'showVerifyOtp'])->name('auth.verify-otp');
    Route::post('/verify-email', [AuthWebController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('auth.verify-otp.submit');
    Route::post('/verify-email/resend', [AuthWebController::class, 'resendOtp'])->middleware('throttle:5,1')->name('auth.resend-otp');

    Route::get('/account/email', [AccountEmailController::class, 'show'])->name('account.email');
    Route::post('/account/email/preferences', [AccountEmailController::class, 'updatePreferences'])->middleware('throttle:10,1')->name('account.email.preferences');
    Route::post('/account/email/change', [AccountEmailController::class, 'requestChange'])->middleware('throttle:5,1')->name('account.email.change');
    Route::post('/account/email/confirm', [AccountEmailController::class, 'confirmChange'])->middleware('throttle:10,1')->name('account.email.confirm');

    // Customer Account Management
    Route::get('/account', [CustomerAccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [CustomerAccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{order}', [CustomerAccountController::class, 'showOrder'])->name('account.orders.show');
    Route::get('/account/profile', [CustomerAccountController::class, 'profile'])->name('account.profile');
    Route::match(['post', 'put'], '/account/profile', [CustomerAccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::get('/account/addresses', [CustomerAccountController::class, 'addresses'])->name('account.addresses');
    Route::post('/account/addresses', [CustomerAccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::match(['put', 'patch'], '/account/addresses/{id}', [CustomerAccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::delete('/account/addresses/{id}', [CustomerAccountController::class, 'deleteAddress'])->name('account.addresses.destroy');
    Route::post('/account/addresses/{id}/default', [CustomerAccountController::class, 'setDefaultAddress'])->name('account.addresses.default');
    Route::get('/account/settings', [CustomerAccountController::class, 'settings'])->name('account.settings');
    Route::post('/account/settings/password', [CustomerAccountController::class, 'updatePassword'])->middleware('throttle:10,1')->name('account.settings.password');
});
Route::get('/forgot-password', [AuthWebController::class, 'showForgotPassword'])->name('auth.forgot-password');
Route::post('/forgot-password', [AuthWebController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('auth.forgot-password.submit');
Route::get('/reset-password/{token}', [AuthWebController::class, 'showResetPassword'])->name('auth.reset-password');
Route::post('/reset-password', [AuthWebController::class, 'resetPassword'])->middleware('throttle:10,1')->name('auth.reset-password.submit');
Route::get('/login/email-code', [AuthWebController::class, 'showLoginOtp'])->name('auth.login-otp');
Route::post('/login/email-code', [AuthWebController::class, 'sendLoginOtp'])->middleware('throttle:5,1')->name('auth.login-otp.send');
Route::post('/login/email-code/verify', [AuthWebController::class, 'verifyLoginOtp'])->middleware('throttle:10,1')->name('auth.login-otp.verify');

// Signed marketing preference links (transactional email is never affected)
Route::get('/email/unsubscribe', [EmailUnsubscribeController::class, 'show'])->middleware('signed')->name('email.unsubscribe');
Route::post('/email/unsubscribe', [EmailUnsubscribeController::class, 'update'])->middleware(['signed', 'throttle:20,1'])->name('email.unsubscribe.submit');
Route::post('/email/unsubscribe/one-click', [EmailUnsubscribeController::class, 'oneClick'])->middleware(['signed', 'throttle:20,1'])->name('email.unsubscribe.one-click');

Route::get('/invoice/{orderId}', [CustomerInvoiceController::class, 'download'])->middleware('throttle:30,1')->name('order.invoice');

Route::get('/team', [TeamWebController::class, 'index'])->name('team');
Route::get('/our-team', fn () => redirect()->route('team'));

Route::get('/about', [PageWebController::class, 'about'])->name('about');
Route::get('/faq', [PageWebController::class, 'faq'])->name('faq');
Route::get('/contact', [PageWebController::class, 'contact'])->name('contact');
Route::post('/contact', [PageWebController::class, 'submitContact'])->middleware('throttle:5,1')->name('contact.submit');
Route::get('/pages/{slug}', [PageWebController::class, 'show'])->name('page.show');

Route::get('/refund-policy', fn () => app(PageWebController::class)->show('return-refund'));
Route::get('/return-refund-policy', fn () => app(PageWebController::class)->show('return-refund'));
Route::get('/shipping-policy', fn () => app(PageWebController::class)->show('shipping-policy'));
Route::get('/privacy-policy', fn () => app(PageWebController::class)->show('privacy-policy'));
Route::get('/terms-and-conditions', fn () => app(PageWebController::class)->show('terms'));
Route::get('/cookie-policy', fn () => app(PageWebController::class)->show('cookie-policy'));
Route::get('/payment-policy', fn () => app(PageWebController::class)->show('payment'));
Route::get('/cancellation-policy', fn () => app(PageWebController::class)->show('cancellation'));
Route::get('/warranty-policy', fn () => app(PageWebController::class)->show('warranty'));

/*
|--------------------------------------------------------------------------
| Admin Panel — full React adminNav.ts parity
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::get('/admin/setup-password/{token}', [AdminInvitationController::class, 'showSetup'])->name('admin.setup-password');
Route::post('/admin/setup-password/{token}', [AdminInvitationController::class, 'processSetup'])->middleware('throttle:10,1')->name('admin.setup-password.submit');

Route::prefix('admin')->middleware(['auth', 'admin.access', 'admin.password.changed'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Mandatory Password Change (first login / forced)
    Route::get('/password/change', [AdminInvitationController::class, 'showChangePassword'])->name('admin.password.change');
    Route::post('/password/change', [AdminInvitationController::class, 'processChangePassword'])->middleware('throttle:10,1')->name('admin.password.change.submit');

    // Admin Self-Service Change Password
    Route::get('/profile/password', [AdminProfileController::class, 'showPasswordForm'])->name('admin.profile.password');
    Route::post('/profile/password', [AdminProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('admin.profile.password.update');

    // Products
    Route::get('/products', [AdminProductWebController::class, 'index'])->middleware('admin.can:products.view')->name('admin.products.index');
    Route::get('/products/create', [AdminProductWebController::class, 'create'])->middleware('admin.can:products.create')->name('admin.products.create');
    Route::post('/products', [AdminProductWebController::class, 'store'])->middleware('admin.can:products.create')->name('admin.products.store');
    Route::get('/products/export', [AdminProductWebController::class, 'exportCsv'])->middleware('admin.can:products.export')->name('admin.products.export');
    Route::post('/products/import', [AdminProductWebController::class, 'importCsv'])->middleware('admin.can:products.import')->name('admin.products.import');
    Route::post('/products/bulk', [AdminProductWebController::class, 'bulkAction'])->middleware('admin.can:products.delete')->name('admin.products.bulk');
    Route::post('/products/upload-image', [AdminProductWebController::class, 'uploadImage'])->middleware('admin.can:products.update')->name('admin.products.upload-image');
    Route::post('/products/{id}/upload-image', [AdminProductWebController::class, 'uploadImage'])->middleware('admin.can:products.update')->name('admin.products.upload-image-product');
    Route::post('/products/upload-editor-image', [AdminProductWebController::class, 'uploadEditorImage'])->middleware('admin.can:products.update')->name('admin.products.upload-editor-image');
    Route::get('/products/{id}', [AdminProductWebController::class, 'show'])->middleware('admin.can:products.view')->name('admin.products.show');
    Route::get('/products/{id}/edit', [AdminProductWebController::class, 'edit'])->middleware('admin.can:products.update')->name('admin.products.edit');
    Route::put('/products/{id}', [AdminProductWebController::class, 'update'])->middleware('admin.can:products.update')->name('admin.products.update');
    Route::delete('/products/{id}', [AdminProductWebController::class, 'destroy'])->middleware('admin.can:products.delete')->name('admin.products.destroy');
    Route::post('/products/{id}/duplicate', [AdminProductWebController::class, 'duplicate'])->middleware('admin.can:products.create')->name('admin.products.duplicate');
    Route::post('/products/{id}/toggle-featured', [AdminProductWebController::class, 'toggleFeatured'])->middleware('admin.can:products.update')->name('admin.products.toggle-featured');
    Route::post('/products/{id}/toggle', [AdminProductWebController::class, 'toggleStatus'])->middleware('admin.can:products.update')->name('admin.products.toggle');
    Route::post('/products/{id}/delete-image', [AdminProductWebController::class, 'deleteImage'])->middleware('admin.can:products.delete')->name('admin.products.delete-image');

    // Orders
    Route::get('/orders', [AdminOrderWebController::class, 'index'])->middleware('admin.can:orders.view')->name('admin.orders.index');
    Route::get('/orders/{id}', [AdminOrderWebController::class, 'show'])->middleware('admin.can:orders.view')->name('admin.orders.show');
    Route::get('/orders/{id}/invoice', [AdminOrderWebController::class, 'invoice'])->middleware('admin.can:orders.view')->name('admin.orders.invoice');
    Route::get('/orders/{id}/invoice/download', [AdminOrderWebController::class, 'downloadInvoice'])->middleware('admin.can:orders.export')->name('admin.orders.invoice.download');
    Route::get('/orders/{id}/packing-slip', [AdminOrderWebController::class, 'packingSlip'])->middleware('admin.can:orders.view')->name('admin.orders.packing-slip');
    Route::post('/orders/{id}/status', [AdminOrderWebController::class, 'updateStatus'])->middleware('admin.can:orders.update')->name('admin.orders.status');
    Route::post('/orders/{id}/payment', [AdminOrderWebController::class, 'updatePayment'])->middleware('admin.can:orders.update')->name('admin.orders.payment');
    Route::post('/orders/{id}/notes', [AdminOrderWebController::class, 'addNote'])->middleware('admin.can:orders.update')->name('admin.orders.notes');
    Route::post('/orders/{id}/email-invoice', [AdminOrderWebController::class, 'emailInvoice'])
        ->middleware(['admin.can:orders.update', 'throttle:10,1'])->name('admin.orders.email-invoice');

    // Incomplete Orders & Checkout Analytics
    Route::prefix('incomplete-orders')->name('admin.incomplete-orders.')->middleware('admin.can:incomplete_orders.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminIncompleteOrderWebController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\AdminIncompleteOrderWebController::class, 'export'])->middleware('admin.can:incomplete_orders.export')->name('export');
        Route::post('/prune', [\App\Http\Controllers\Admin\AdminIncompleteOrderWebController::class, 'prune'])->middleware('admin.can:incomplete_orders.manage_retention')->name('prune');
    });

    // Email Management
    Route::prefix('email')->name('admin.email.')->middleware('email.schema')->group(function () {
        Route::get('/', [AdminEmailController::class, 'dashboard'])->middleware('admin.can:email.view')->name('dashboard');

        Route::middleware('admin.can:email.settings.manage')->group(function () {
            Route::get('/settings', [AdminEmailController::class, 'settings'])->name('settings');
            Route::post('/settings', [AdminEmailController::class, 'updateSettings'])->name('settings.update');
            Route::post('/settings/test-connection', [AdminEmailController::class, 'testConnection'])->middleware('throttle:6,1')->name('settings.test-connection');
            Route::post('/settings/probe-ports', [AdminEmailController::class, 'probePorts'])->middleware('throttle:6,1')->name('settings.probe-ports');
            Route::post('/settings/send-test', [AdminEmailController::class, 'sendTestEmail'])->middleware('throttle:6,1')->name('settings.send-test');
            Route::post('/settings/check-dns', [AdminEmailController::class, 'checkDns'])->middleware('throttle:6,1')->name('settings.check-dns');

            Route::get('/automation', [AdminEmailController::class, 'automation'])->name('automation');
            Route::post('/automation', [AdminEmailController::class, 'updateAutomation'])->name('automation.update');
        });

        Route::middleware('admin.can:email.templates.manage')->prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [AdminEmailTemplateController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [AdminEmailTemplateController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminEmailTemplateController::class, 'update'])->name('update');
            Route::match(['get', 'post'], '/{id}/preview', [AdminEmailTemplateController::class, 'preview'])->name('preview');
            Route::post('/{id}/test', [AdminEmailTemplateController::class, 'sendTest'])->middleware('throttle:10,1')->name('test');
            Route::post('/{id}/restore', [AdminEmailTemplateController::class, 'restore'])->name('restore');
        });

        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::middleware('admin.can:email.campaigns.manage|email.campaigns.send')->group(function () {
                Route::get('/', [AdminEmailCampaignController::class, 'index'])->name('index');
                Route::get('/{id}', [AdminEmailCampaignController::class, 'show'])->whereNumber('id')->name('show');
                Route::get('/{id}/preview', [AdminEmailCampaignController::class, 'preview'])->name('preview');
            });
            Route::middleware('admin.can:email.campaigns.manage')->group(function () {
                Route::get('/create', [AdminEmailCampaignController::class, 'create'])->name('create');
                Route::post('/', [AdminEmailCampaignController::class, 'store'])->name('store');
                Route::post('/audience-count', [AdminEmailCampaignController::class, 'audienceCount'])->middleware('throttle:30,1')->name('audience-count');
                Route::get('/{id}/edit', [AdminEmailCampaignController::class, 'edit'])->name('edit');
                Route::put('/{id}', [AdminEmailCampaignController::class, 'update'])->name('update');
                Route::post('/{id}/test', [AdminEmailCampaignController::class, 'sendTest'])->middleware('throttle:10,1')->name('test');
                Route::post('/{id}/duplicate', [AdminEmailCampaignController::class, 'duplicate'])->name('duplicate');
                Route::delete('/{id}', [AdminEmailCampaignController::class, 'destroy'])->name('destroy');
            });
            Route::middleware('admin.can:email.campaigns.send')->group(function () {
                Route::post('/{id}/confirm', [AdminEmailCampaignController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
                Route::post('/{id}/unschedule', [AdminEmailCampaignController::class, 'unschedule'])->name('unschedule');
                Route::post('/{id}/pause', [AdminEmailCampaignController::class, 'pause'])->name('pause');
                Route::post('/{id}/resume', [AdminEmailCampaignController::class, 'resume'])->name('resume');
                Route::post('/{id}/cancel', [AdminEmailCampaignController::class, 'cancel'])->name('cancel');
                Route::post('/{id}/retry-failed', [AdminEmailCampaignController::class, 'retryFailed'])->name('retry-failed');
            });
        });

        Route::middleware('admin.can:email.logs.view')->group(function () {
            Route::get('/logs', [AdminEmailController::class, 'logs'])->name('logs.index');
            Route::get('/logs/{id}', [AdminEmailController::class, 'showLog'])->name('logs.show');
            Route::post('/logs/{id}/retry', [AdminEmailController::class, 'retryLog'])->middleware('throttle:20,1')->name('logs.retry');
            Route::get('/suppressions', [AdminEmailController::class, 'suppressions'])->name('logs.suppressions');
            Route::post('/suppressions', [AdminEmailController::class, 'storeSuppression'])->name('logs.suppressions.store');
            Route::delete('/suppressions/{id}', [AdminEmailController::class, 'destroySuppression'])->name('logs.suppressions.destroy');
        });
    });

    // Reviews
    Route::get('/reviews', [AdminReviewWebController::class, 'index'])->middleware('admin.can:reviews.view')->name('admin.reviews.index');
    Route::get('/reviews/{id}', [AdminReviewWebController::class, 'show'])->middleware('admin.can:reviews.view')->name('admin.reviews.show');
    Route::put('/reviews/{id}', [AdminReviewWebController::class, 'update'])->middleware('admin.can:reviews.update')->name('admin.reviews.update');
    Route::post('/reviews/{id}/status', [AdminReviewWebController::class, 'updateStatus'])->middleware('admin.can:reviews.update')->name('admin.reviews.status');
    Route::post('/reviews/{id}/featured', [AdminReviewWebController::class, 'toggleFeatured'])->middleware('admin.can:reviews.update')->name('admin.reviews.featured');
    Route::delete('/reviews/{id}', [AdminReviewWebController::class, 'destroy'])->middleware('admin.can:reviews.delete')->name('admin.reviews.destroy');

    // Categories
    Route::get('/categories', [AdminCategoryWebController::class, 'index'])->middleware('admin.can:categories.view')->name('admin.categories.index');
    Route::post('/categories', [AdminCategoryWebController::class, 'store'])->middleware('admin.can:categories.create')->name('admin.categories.store');
    Route::put('/categories/{id}', [AdminCategoryWebController::class, 'update'])->middleware('admin.can:categories.update')->name('admin.categories.update');
    Route::delete('/categories/{id}', [AdminCategoryWebController::class, 'destroy'])->middleware('admin.can:categories.delete')->name('admin.categories.destroy');

    // Catalog CRUD resources (React CatalogCrudPage equivalents)
    $catalogResources = ['brands', 'collections', 'colors', 'sizes', 'vendors', 'suppliers', 'checkout-notices', 'expense-categories'];
    foreach ($catalogResources as $resource) {
        $viewPerm = match ($resource) {
            'checkout-notices' => 'checkout_notices.view|settings.view',
            'expense-categories' => 'expenses.view',
            default => "{$resource}.view|products.view",
        };
        $managePerm = match ($resource) {
            'checkout-notices' => 'checkout_notices.manage|settings.manage',
            'expense-categories' => 'expenses.update|expenses.create',
            default => "{$resource}.update|products.update|products.create",
        };
        $deletePerm = match ($resource) {
            'checkout-notices' => 'checkout_notices.manage|settings.manage',
            'expense-categories' => 'expenses.delete',
            default => "{$resource}.delete|products.delete",
        };

        Route::get("/{$resource}", fn () => app(AdminCatalogWebController::class)->index($resource))
            ->middleware("admin.can:{$viewPerm}")
            ->name("admin.{$resource}.index");
        Route::post("/{$resource}", fn (Request $r) => app(AdminCatalogWebController::class)->store($r, $resource))
            ->middleware("admin.can:{$managePerm}")
            ->name("admin.{$resource}.store");
        Route::put("/{$resource}/{id}", fn (Request $r, $id) => app(AdminCatalogWebController::class)->update($r, $resource, (int) $id))
            ->middleware("admin.can:{$managePerm}")
            ->name("admin.{$resource}.update");
        Route::delete("/{$resource}/{id}", fn ($id) => app(AdminCatalogWebController::class)->destroy($resource, (int) $id))
            ->middleware("admin.can:{$deletePerm}")
            ->name("admin.{$resource}.destroy");
    }

    // Coupons
    Route::get('/coupons', [AdminCouponWebController::class, 'index'])->middleware('admin.can:coupons.view')->name('admin.coupons.index');
    Route::post('/coupons', [AdminCouponWebController::class, 'store'])->middleware('admin.can:coupons.create')->name('admin.coupons.store');
    Route::put('/coupons/{id}', [AdminCouponWebController::class, 'update'])->middleware('admin.can:coupons.update')->name('admin.coupons.update');
    Route::delete('/coupons/{id}', [AdminCouponWebController::class, 'destroy'])->middleware('admin.can:coupons.delete')->name('admin.coupons.destroy');

    // Customers (Customer 360 Management)
    Route::get('/customers', [AdminCustomerWebController::class, 'index'])->middleware('admin.can:customers.view')->name('admin.customers.index');
    Route::get('/customers/export', [AdminCustomerWebController::class, 'exportList'])->middleware('admin.can:customers.export|customers.view')->name('admin.customers.export-list');
    Route::get('/customers/{id}', [AdminCustomerWebController::class, 'show'])->middleware('admin.can:customers.view')->name('admin.customers.show');
    Route::put('/customers/{id}', [AdminCustomerWebController::class, 'update'])->middleware('admin.can:customers.update')->name('admin.customers.update');
    Route::post('/customers/{id}/toggle', [AdminCustomerWebController::class, 'toggleStatus'])->middleware('admin.can:customers.update')->name('admin.customers.toggle');
    Route::get('/customers/{id}/export', [AdminCustomerWebController::class, 'export'])->middleware('admin.can:customers.export|customers.view')->name('admin.customers.export');
    Route::post('/customers/{id}/notes', [AdminCustomerWebController::class, 'storeNote'])->middleware('admin.can:customers.update')->name('admin.customers.notes.store');
    Route::delete('/customers/{id}/notes/{noteId}', [AdminCustomerWebController::class, 'deleteNote'])->middleware('admin.can:customers.delete')->name('admin.customers.notes.destroy');
    Route::post('/customers/{id}/addresses', [AdminCustomerWebController::class, 'storeAddress'])->middleware('admin.can:customers.update')->name('admin.customers.addresses.store');
    Route::put('/customers/{id}/addresses/{addressId}', [AdminCustomerWebController::class, 'updateAddress'])->middleware('admin.can:customers.update')->name('admin.customers.addresses.update');
    Route::delete('/customers/{id}/addresses/{addressId}', [AdminCustomerWebController::class, 'deleteAddress'])->middleware('admin.can:customers.delete')->name('admin.customers.addresses.destroy');
    Route::post('/customers/{id}/addresses/{addressId}/default', [AdminCustomerWebController::class, 'setDefaultAddress'])->middleware('admin.can:customers.update')->name('admin.customers.addresses.default');
    Route::post('/customers/{id}/email', [AdminCustomerWebController::class, 'sendEmail'])->middleware('admin.can:customers.update')->name('admin.customers.email.send');

    // Marketing
    Route::get('/marketing', [AdminModuleWebController::class, 'marketing'])->middleware('admin.can:marketing.view')->name('admin.marketing.index');
    Route::post('/marketing', [AdminModuleWebController::class, 'storeMarketing'])->middleware('admin.can:marketing.manage')->name('admin.marketing.store');
    Route::put('/marketing/{id}', [AdminModuleWebController::class, 'updateMarketing'])->name('admin.marketing.update');
    Route::delete('/marketing/{id}', [AdminModuleWebController::class, 'destroyMarketing'])->name('admin.marketing.destroy');

    // Finance
    // Finance
    Route::get('/expenses', [AdminModuleWebController::class, 'expenses'])->middleware('admin.can:expenses.view')->name('admin.expenses.index');
    Route::post('/expenses', [AdminModuleWebController::class, 'storeExpense'])->middleware('admin.can:expenses.create')->name('admin.expenses.store');
    Route::put('/expenses/{id}', [AdminModuleWebController::class, 'updateExpense'])->middleware('admin.can:expenses.update')->name('admin.expenses.update');
    Route::delete('/expenses/{id}', [AdminModuleWebController::class, 'destroyExpense'])->middleware('admin.can:expenses.delete')->name('admin.expenses.destroy');
    Route::get('/expenses/reports', [AdminModuleWebController::class, 'expenseReports'])->middleware('admin.can:reports.view')->name('admin.expenses.reports');
    // Alias for React path /admin/expenses/categories
    Route::get('/expenses/categories', fn () => redirect()->route('admin.expense-categories.index'));

    // Checkout
    Route::get('/shipping', [AdminSettingWebController::class, 'shipping'])->middleware('admin.can:shipping.view')->name('admin.shipping.index');
    Route::post('/shipping', [AdminSettingWebController::class, 'storeShipping'])->middleware('admin.can:shipping.manage')->name('admin.shipping.store');
    Route::put('/shipping/{id}', [AdminSettingWebController::class, 'updateShipping'])->middleware('admin.can:shipping.manage')->name('admin.shipping.update');
    Route::post('/shipping/{id}/toggle', [AdminSettingWebController::class, 'toggleShipping'])->middleware('admin.can:shipping.manage')->name('admin.shipping.toggle');
    Route::post('/shipping/reorder', [AdminSettingWebController::class, 'reorderShipping'])->middleware('admin.can:shipping.manage')->name('admin.shipping.reorder');
    Route::delete('/shipping/{id}', [AdminSettingWebController::class, 'destroyShipping'])->middleware('admin.can:shipping.manage')->name('admin.shipping.destroy');
    Route::get('/checkout-settings', [AdminSettingWebController::class, 'checkoutSettings'])->middleware('admin.can:settings.view')->name('admin.checkout-settings.index');
    Route::post('/checkout-settings', [AdminSettingWebController::class, 'updateCheckoutSettings'])->middleware('admin.can:settings.manage')->name('admin.checkout-settings.update');
    Route::get('/payment-methods', [AdminSettingWebController::class, 'paymentMethods'])->middleware('admin.can:payment_methods.view')->name('admin.payment-methods.index');
    Route::post('/payment-methods', [AdminSettingWebController::class, 'storePaymentMethod'])->middleware('admin.can:payment_methods.manage')->name('admin.payment-methods.store');
    Route::put('/payment-methods/{id}', [AdminSettingWebController::class, 'updatePaymentMethod'])->middleware('admin.can:payment_methods.manage')->name('admin.payment-methods.update');
    Route::post('/payment-methods/{id}/toggle', [AdminSettingWebController::class, 'togglePaymentMethod'])->middleware('admin.can:payment_methods.manage')->name('admin.payment-methods.toggle');
    Route::post('/payment-methods/bulk-status', [AdminSettingWebController::class, 'bulkPaymentMethodsStatus'])->middleware('admin.can:payment_methods.manage')->name('admin.payment-methods.bulk-status');
    Route::delete('/payment-methods/{id}', [AdminSettingWebController::class, 'destroyPaymentMethod'])->middleware('admin.can:payment_methods.manage')->name('admin.payment-methods.destroy');
    Route::get('/payments', fn () => redirect()->route('admin.payment-methods.index')); // legacy alias

    // Content
    Route::get('/homepage', [AdminModuleWebController::class, 'homepage'])->middleware('admin.can:homepage.view')->name('admin.homepage.index');
    Route::post('/homepage', [AdminModuleWebController::class, 'saveHomepage'])->middleware('admin.can:homepage.manage')->name('admin.homepage.save');
    Route::post('/homepage/reset', [AdminModuleWebController::class, 'resetHomepage'])->middleware('admin.can:homepage.manage')->name('admin.homepage.reset');
    Route::get('/policies', [AdminModuleWebController::class, 'policies'])->middleware('admin.can:policies.view')->name('admin.policies.index');
    Route::post('/policies', [AdminModuleWebController::class, 'storePolicy'])->middleware('admin.can:policies.manage')->name('admin.policies.store');
    Route::put('/policies/{id}', [AdminModuleWebController::class, 'updatePolicy'])->middleware('admin.can:policies.manage')->name('admin.policies.update');
    Route::delete('/policies/{id}', [AdminModuleWebController::class, 'destroyPolicy'])->middleware('admin.can:policies.manage')->name('admin.policies.destroy');
    Route::get('/banners', [AdminSettingWebController::class, 'banners'])->middleware('admin.can:banners.view')->name('admin.banners.index');
    Route::post('/banners', [AdminSettingWebController::class, 'storeBanner'])->middleware('admin.can:banners.create')->name('admin.banners.store');
    Route::put('/banners/{id}', [AdminSettingWebController::class, 'updateBanner'])->middleware('admin.can:banners.update')->name('admin.banners.update');
    Route::delete('/banners/{id}', [AdminSettingWebController::class, 'destroyBanner'])->middleware('admin.can:banners.delete')->name('admin.banners.destroy');
    Route::get('/media', [AdminSettingWebController::class, 'media'])->middleware('admin.can:media.view')->name('admin.media.index');
    Route::post('/media', [AdminSettingWebController::class, 'storeMedia'])->middleware('admin.can:media.upload')->name('admin.media.store');
    Route::delete('/media/{id}', [AdminSettingWebController::class, 'destroyMedia'])->middleware('admin.can:media.delete')->name('admin.media.destroy');
    Route::get('/media/picker', [AdminSettingWebController::class, 'mediaPicker'])->middleware('admin.can:media.view')->name('admin.media.picker');
    Route::post('/media/picker', [AdminSettingWebController::class, 'mediaPickerUpload'])->middleware('admin.can:media.upload')->name('admin.media.picker.upload');

    // Insights
    Route::get('/advanced-analytics', [AdminAdvancedAnalyticsController::class, 'index'])->middleware('admin.can:analytics.view|inventory.view')->name('admin.advanced-analytics.index');
    Route::get('/advanced-analytics/export/csv', [AdminAdvancedAnalyticsController::class, 'exportCsv'])->middleware('admin.can:analytics.view|inventory.view|reports.export')->name('admin.advanced-analytics.export.csv');
    Route::post('/advanced-analytics/export/pdf', [AdminAdvancedAnalyticsController::class, 'exportPdf'])->middleware('admin.can:analytics.view|inventory.view|reports.export')->name('admin.advanced-analytics.export.pdf');
    Route::get('/advanced-analytics/products/{id}/variants', [AdminAdvancedAnalyticsController::class, 'productVariants'])->middleware('admin.can:analytics.view|inventory.view')->name('admin.advanced-analytics.product-variants');
    Route::get('/analytics', [AdminModuleWebController::class, 'analytics'])->middleware('admin.can:analytics.view')->name('admin.analytics.index');

    // Security, Team Members & Activity Monitoring
    Route::get('/activity-monitor', [AdminActivityController::class, 'index'])->middleware('admin.can:activity.view')->name('admin.activity.index');
    Route::get('/activity-monitor/export', [AdminActivityController::class, 'export'])->middleware('admin.can:activity.export')->name('admin.activity.export');
    Route::get('/activity-monitor/entry/{uuid}', [AdminActivityController::class, 'show'])->middleware('admin.can:activity.view')->name('admin.activity.show');
    Route::post('/activity-monitor/scheduled-reports', [AdminActivityController::class, 'storeScheduledReport'])->middleware('admin.can:activity.manage')->name('admin.activity.scheduled.store');
    Route::post('/activity-monitor/scheduled-reports/{id}/toggle', [AdminActivityController::class, 'toggleScheduledReport'])->middleware('admin.can:activity.manage')->name('admin.activity.scheduled.toggle');
    Route::delete('/activity-monitor/scheduled-reports/{id}', [AdminActivityController::class, 'destroyScheduledReport'])->middleware('admin.can:activity.manage')->name('admin.activity.scheduled.destroy');
    Route::post('/activity-monitor/scheduled-reports/{id}/run', [AdminActivityController::class, 'runScheduledReportNow'])->middleware('admin.can:activity.manage')->name('admin.activity.scheduled.run');
    Route::get('/members', [AdminModuleWebController::class, 'members'])->middleware('admin.can:members.view')->name('admin.members.index');
    Route::post('/members', [AdminModuleWebController::class, 'storeMember'])->middleware('admin.can:members.create')->name('admin.members.store');
    Route::get('/members/{id}', [AdminModuleWebController::class, 'showMember'])->middleware('admin.can:members.view')->name('admin.members.show');
    Route::put('/members/{id}', [AdminModuleWebController::class, 'updateMember'])->middleware('admin.can:members.update')->name('admin.members.update');
    Route::delete('/members/{id}', [AdminModuleWebController::class, 'destroyMember'])->middleware('admin.can:members.delete')->name('admin.members.destroy');
    Route::post('/members/{id}/resend-invitation', [AdminModuleWebController::class, 'resendInvitation'])->middleware('admin.can:members.create|members.update')->name('admin.members.resend-invitation');

    // Team Management
    Route::get('/team', [AdminTeamWebController::class, 'index'])->middleware('admin.can:team.view')->name('admin.team.index');
    Route::post('/team', [AdminTeamWebController::class, 'store'])->middleware('admin.can:team.manage')->name('admin.team.store');
    Route::get('/team/{id}', [AdminTeamWebController::class, 'show'])->middleware('admin.can:team.view')->name('admin.team.show');
    Route::put('/team/{id}', [AdminTeamWebController::class, 'update'])->middleware('admin.can:team.manage')->name('admin.team.update');
    Route::delete('/team/{id}', [AdminTeamWebController::class, 'destroy'])->middleware('admin.can:team.manage')->name('admin.team.destroy');
    Route::post('/team/reorder', [AdminTeamWebController::class, 'reorder'])->middleware('admin.can:team.manage')->name('admin.team.reorder');
    Route::post('/team/{id}/toggle-status', [AdminTeamWebController::class, 'toggleStatus'])->middleware('admin.can:team.manage')->name('admin.team.toggle-status');
    Route::post('/team/{id}/toggle-footer', [AdminTeamWebController::class, 'toggleFooter'])->middleware('admin.can:team.manage')->name('admin.team.toggle-footer');
    Route::post('/team/{id}/toggle-public', [AdminTeamWebController::class, 'togglePublic'])->middleware('admin.can:team.manage')->name('admin.team.toggle-public');
    Route::post('/team/settings', [AdminTeamWebController::class, 'updateSettings'])->middleware('admin.can:team.manage')->name('admin.team.settings.update');

    Route::get('/backup', [AdminSettingWebController::class, 'backup'])->middleware('admin.can:backup.view')->name('admin.backup.index');
    Route::post('/backup', [AdminSettingWebController::class, 'createBackup'])->middleware('admin.can:backup.create')->name('admin.backup.create');
    Route::get('/backup/{id}/download', [AdminSettingWebController::class, 'downloadBackup'])->middleware('admin.can:backup.view')->name('admin.backup.download');
    Route::post('/backup/restore', [AdminSettingWebController::class, 'restoreBackup'])->middleware('admin.can:backup.restore')->name('admin.backup.restore');
    Route::delete('/backup/{id}', [AdminSettingWebController::class, 'deleteBackup'])->middleware('admin.can:backup.delete')->name('admin.backup.destroy');

    // System
    Route::get('/inventory', [AdminModuleWebController::class, 'inventory'])->middleware('admin.can:inventory.view')->name('admin.inventory.index');
    Route::post('/inventory/{id}/adjust', [AdminModuleWebController::class, 'adjustStock'])->middleware('admin.can:inventory.manage')->name('admin.inventory.adjust');
    Route::get('/settings/business', [AdminSettingWebController::class, 'businessSettings'])->middleware('admin.can:settings.view')->name('admin.settings.business');
    Route::post('/settings/business', [AdminSettingWebController::class, 'updateBusinessSettings'])->middleware('admin.can:settings.manage')->name('admin.settings.business.update');
    Route::get('/settings', [AdminSettingWebController::class, 'settings'])->middleware('admin.can:settings.view')->name('admin.settings.index');
    Route::post('/settings', [AdminSettingWebController::class, 'updateSettings'])->middleware('admin.can:settings.manage')->name('admin.settings.update');
    Route::match(['get', 'post'], '/fix-storage', [AdminSettingWebController::class, 'fixStorageWeb'])->name('admin.fix-storage');

    // SEO Optimization & Catalog Audit
    Route::get('/seo', [AdminSeoController::class, 'index'])->name('admin.seo.index');
    Route::post('/seo/route', [AdminSeoController::class, 'updateRouteSeo'])->name('admin.seo.update-route');
    Route::post('/seo/generate-drafts', [AdminSeoController::class, 'generateDrafts'])->name('admin.seo.generate-drafts');
    Route::post('/seo/refresh-sitemap', [AdminSeoController::class, 'refreshSitemap'])->name('admin.seo.refresh-sitemap');
    Route::get('/seo/export/csv', [AdminSeoController::class, 'exportCsv'])->name('admin.seo.export-csv');
    Route::get('/seo/export/pdf', [AdminSeoController::class, 'exportPdf'])->name('admin.seo.export-pdf');
});

// Storage and upload fallbacks for cPanel / shared hosting environments where symlink may be broken or disabled
Route::get('/storage/{path}', [StorageFileController::class, 'show'])->where('path', '.*');
Route::get('/uploads/{path}', [StorageFileController::class, 'showUploads'])->where('path', '.*');
