<?php

use App\Http\Controllers\Admin\AdminActivityController;
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
use App\Http\Controllers\Admin\AdminReviewWebController;
use App\Http\Controllers\Admin\AdminSettingWebController;
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
use App\Http\Controllers\Web\ShopController;
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
Route::get('/order/success', [CheckoutController::class, 'success'])->name('order.success');
Route::get('/track', [OrderTrackingController::class, 'index'])->name('track');
Route::post('/newsletter/subscribe', [HomeController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

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

    // Mandatory Password Change
    Route::get('/password/change', [AdminInvitationController::class, 'showChangePassword'])->name('admin.password.change');
    Route::post('/password/change', [AdminInvitationController::class, 'processChangePassword'])->middleware('throttle:10,1')->name('admin.password.change.submit');

    // Products
    Route::get('/products', [AdminProductWebController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductWebController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductWebController::class, 'store'])->name('admin.products.store');
    Route::get('/products/export', [AdminProductWebController::class, 'exportCsv'])->name('admin.products.export');
    Route::post('/products/import', [AdminProductWebController::class, 'importCsv'])->name('admin.products.import');
    Route::post('/products/bulk', [AdminProductWebController::class, 'bulkAction'])->name('admin.products.bulk');
    Route::post('/products/upload-image', [AdminProductWebController::class, 'uploadImage'])->name('admin.products.upload-image');
    Route::post('/products/{id}/upload-image', [AdminProductWebController::class, 'uploadImage'])->name('admin.products.upload-image-product');
    Route::post('/products/upload-editor-image', [AdminProductWebController::class, 'uploadEditorImage'])->name('admin.products.upload-editor-image');
    Route::get('/products/{id}', [AdminProductWebController::class, 'show'])->name('admin.products.show');
    Route::get('/products/{id}/edit', [AdminProductWebController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminProductWebController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminProductWebController::class, 'destroy'])->name('admin.products.destroy');
    Route::post('/products/{id}/duplicate', [AdminProductWebController::class, 'duplicate'])->name('admin.products.duplicate');
    Route::post('/products/{id}/toggle-featured', [AdminProductWebController::class, 'toggleFeatured'])->name('admin.products.toggle-featured');
    Route::post('/products/{id}/toggle', [AdminProductWebController::class, 'toggleStatus'])->name('admin.products.toggle');
    Route::post('/products/{id}/delete-image', [AdminProductWebController::class, 'deleteImage'])->name('admin.products.delete-image');

    // Orders
    Route::get('/orders', [AdminOrderWebController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{id}', [AdminOrderWebController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{id}/invoice', [AdminOrderWebController::class, 'invoice'])->name('admin.orders.invoice');
    Route::get('/orders/{id}/invoice/download', [AdminOrderWebController::class, 'downloadInvoice'])->name('admin.orders.invoice.download');
    Route::get('/orders/{id}/packing-slip', [AdminOrderWebController::class, 'packingSlip'])->name('admin.orders.packing-slip');
    Route::post('/orders/{id}/status', [AdminOrderWebController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/orders/{id}/payment', [AdminOrderWebController::class, 'updatePayment'])->name('admin.orders.payment');
    Route::post('/orders/{id}/notes', [AdminOrderWebController::class, 'addNote'])->name('admin.orders.notes');
    Route::post('/orders/{id}/email-invoice', [AdminOrderWebController::class, 'emailInvoice'])
        ->middleware(['admin.can:orders.update', 'throttle:10,1'])->name('admin.orders.email-invoice');

    // Email Management
    Route::prefix('email')->name('admin.email.')->middleware('email.schema')->group(function () {
        Route::get('/', [AdminEmailController::class, 'dashboard'])->middleware('admin.can:email.view')->name('dashboard');

        Route::middleware('admin.can:email.settings.manage')->group(function () {
            Route::get('/settings', [AdminEmailController::class, 'settings'])->name('settings');
            Route::post('/settings/unlock', [AdminEmailController::class, 'unlockSettings'])->middleware('throttle:10,1')->name('settings.unlock');
            Route::post('/settings/lock', [AdminEmailController::class, 'lockSettings'])->name('settings.lock');

            Route::middleware('smtp.unlocked')->group(function () {
                Route::post('/settings', [AdminEmailController::class, 'updateSettings'])->name('settings.update');
                Route::post('/settings/test-connection', [AdminEmailController::class, 'testConnection'])->middleware('throttle:6,1')->name('settings.test-connection');
                Route::post('/settings/send-test', [AdminEmailController::class, 'sendTestEmail'])->middleware('throttle:6,1')->name('settings.send-test');
                Route::post('/settings/check-dns', [AdminEmailController::class, 'checkDns'])->middleware('throttle:6,1')->name('settings.check-dns');
            });

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
    Route::get('/reviews', [AdminReviewWebController::class, 'index'])->name('admin.reviews.index');
    Route::get('/reviews/{id}', [AdminReviewWebController::class, 'show'])->name('admin.reviews.show');
    Route::put('/reviews/{id}', [AdminReviewWebController::class, 'update'])->name('admin.reviews.update');
    Route::post('/reviews/{id}/status', [AdminReviewWebController::class, 'updateStatus'])->name('admin.reviews.status');
    Route::post('/reviews/{id}/featured', [AdminReviewWebController::class, 'toggleFeatured'])->name('admin.reviews.featured');
    Route::delete('/reviews/{id}', [AdminReviewWebController::class, 'destroy'])->name('admin.reviews.destroy');

    // Categories
    Route::get('/categories', [AdminCategoryWebController::class, 'index'])->name('admin.categories.index');
    Route::post('/categories', [AdminCategoryWebController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{id}', [AdminCategoryWebController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [AdminCategoryWebController::class, 'destroy'])->name('admin.categories.destroy');

    // Catalog CRUD resources (React CatalogCrudPage equivalents)
    $catalogResources = ['brands', 'collections', 'colors', 'sizes', 'vendors', 'suppliers', 'checkout-notices', 'expense-categories'];
    foreach ($catalogResources as $resource) {
        Route::get("/{$resource}", fn () => app(AdminCatalogWebController::class)->index($resource))
            ->name("admin.{$resource}.index");
        Route::post("/{$resource}", fn (Request $r) => app(AdminCatalogWebController::class)->store($r, $resource))
            ->name("admin.{$resource}.store");
        Route::put("/{$resource}/{id}", fn (Request $r, $id) => app(AdminCatalogWebController::class)->update($r, $resource, (int) $id))
            ->name("admin.{$resource}.update");
        Route::delete("/{$resource}/{id}", fn ($id) => app(AdminCatalogWebController::class)->destroy($resource, (int) $id))
            ->name("admin.{$resource}.destroy");
    }

    // Coupons
    Route::get('/coupons', [AdminCouponWebController::class, 'index'])->name('admin.coupons.index');
    Route::post('/coupons', [AdminCouponWebController::class, 'store'])->name('admin.coupons.store');
    Route::put('/coupons/{id}', [AdminCouponWebController::class, 'update'])->name('admin.coupons.update');
    Route::delete('/coupons/{id}', [AdminCouponWebController::class, 'destroy'])->name('admin.coupons.destroy');

    // Customers (Customer 360 Management)
    Route::get('/customers', [AdminCustomerWebController::class, 'index'])->name('admin.customers.index');
    Route::get('/customers/export', [AdminCustomerWebController::class, 'exportList'])->name('admin.customers.export-list');
    Route::get('/customers/{id}', [AdminCustomerWebController::class, 'show'])->name('admin.customers.show');
    Route::put('/customers/{id}', [AdminCustomerWebController::class, 'update'])->name('admin.customers.update');
    Route::post('/customers/{id}/toggle', [AdminCustomerWebController::class, 'toggleStatus'])->name('admin.customers.toggle');
    Route::get('/customers/{id}/export', [AdminCustomerWebController::class, 'export'])->name('admin.customers.export');
    Route::post('/customers/{id}/notes', [AdminCustomerWebController::class, 'storeNote'])->name('admin.customers.notes.store');
    Route::delete('/customers/{id}/notes/{noteId}', [AdminCustomerWebController::class, 'deleteNote'])->name('admin.customers.notes.destroy');
    Route::post('/customers/{id}/addresses', [AdminCustomerWebController::class, 'storeAddress'])->name('admin.customers.addresses.store');
    Route::put('/customers/{id}/addresses/{addressId}', [AdminCustomerWebController::class, 'updateAddress'])->name('admin.customers.addresses.update');
    Route::delete('/customers/{id}/addresses/{addressId}', [AdminCustomerWebController::class, 'deleteAddress'])->name('admin.customers.addresses.destroy');
    Route::post('/customers/{id}/addresses/{addressId}/default', [AdminCustomerWebController::class, 'setDefaultAddress'])->name('admin.customers.addresses.default');
    Route::post('/customers/{id}/email', [AdminCustomerWebController::class, 'sendEmail'])->name('admin.customers.email.send');

    // Marketing
    Route::get('/marketing', [AdminModuleWebController::class, 'marketing'])->name('admin.marketing.index');
    Route::post('/marketing', [AdminModuleWebController::class, 'storeMarketing'])->name('admin.marketing.store');
    Route::put('/marketing/{id}', [AdminModuleWebController::class, 'updateMarketing'])->name('admin.marketing.update');
    Route::delete('/marketing/{id}', [AdminModuleWebController::class, 'destroyMarketing'])->name('admin.marketing.destroy');

    // Finance
    Route::get('/expenses', [AdminModuleWebController::class, 'expenses'])->name('admin.expenses.index');
    Route::post('/expenses', [AdminModuleWebController::class, 'storeExpense'])->name('admin.expenses.store');
    Route::put('/expenses/{id}', [AdminModuleWebController::class, 'updateExpense'])->name('admin.expenses.update');
    Route::delete('/expenses/{id}', [AdminModuleWebController::class, 'destroyExpense'])->name('admin.expenses.destroy');
    Route::get('/expenses/reports', [AdminModuleWebController::class, 'expenseReports'])->name('admin.expenses.reports');
    // Alias for React path /admin/expenses/categories
    Route::get('/expenses/categories', fn () => redirect()->route('admin.expense-categories.index'));

    // Checkout
    Route::get('/shipping', [AdminSettingWebController::class, 'shipping'])->name('admin.shipping.index');
    Route::post('/shipping', [AdminSettingWebController::class, 'storeShipping'])->name('admin.shipping.store');
    Route::put('/shipping/{id}', [AdminSettingWebController::class, 'updateShipping'])->name('admin.shipping.update');
    Route::post('/shipping/{id}/toggle', [AdminSettingWebController::class, 'toggleShipping'])->name('admin.shipping.toggle');
    Route::post('/shipping/reorder', [AdminSettingWebController::class, 'reorderShipping'])->name('admin.shipping.reorder');
    Route::delete('/shipping/{id}', [AdminSettingWebController::class, 'destroyShipping'])->name('admin.shipping.destroy');
    Route::get('/checkout-settings', [AdminSettingWebController::class, 'checkoutSettings'])->name('admin.checkout-settings.index');
    Route::post('/checkout-settings', [AdminSettingWebController::class, 'updateCheckoutSettings'])->name('admin.checkout-settings.update');
    Route::get('/payment-methods', [AdminSettingWebController::class, 'paymentMethods'])->name('admin.payment-methods.index');
    Route::post('/payment-methods', [AdminSettingWebController::class, 'storePaymentMethod'])->name('admin.payment-methods.store');
    Route::put('/payment-methods/{id}', [AdminSettingWebController::class, 'updatePaymentMethod'])->name('admin.payment-methods.update');
    Route::post('/payment-methods/{id}/toggle', [AdminSettingWebController::class, 'togglePaymentMethod'])->name('admin.payment-methods.toggle');
    Route::post('/payment-methods/bulk-status', [AdminSettingWebController::class, 'bulkPaymentMethodsStatus'])->name('admin.payment-methods.bulk-status');
    Route::delete('/payment-methods/{id}', [AdminSettingWebController::class, 'destroyPaymentMethod'])->name('admin.payment-methods.destroy');
    Route::get('/payments', fn () => redirect()->route('admin.payment-methods.index')); // legacy alias

    // Content
    Route::get('/homepage', [AdminModuleWebController::class, 'homepage'])->name('admin.homepage.index');
    Route::post('/homepage', [AdminModuleWebController::class, 'saveHomepage'])->name('admin.homepage.save');
    Route::post('/homepage/reset', [AdminModuleWebController::class, 'resetHomepage'])->name('admin.homepage.reset');
    Route::get('/policies', [AdminModuleWebController::class, 'policies'])->name('admin.policies.index');
    Route::post('/policies', [AdminModuleWebController::class, 'storePolicy'])->name('admin.policies.store');
    Route::put('/policies/{id}', [AdminModuleWebController::class, 'updatePolicy'])->name('admin.policies.update');
    Route::delete('/policies/{id}', [AdminModuleWebController::class, 'destroyPolicy'])->name('admin.policies.destroy');
    Route::get('/banners', [AdminSettingWebController::class, 'banners'])->name('admin.banners.index');
    Route::post('/banners', [AdminSettingWebController::class, 'storeBanner'])->name('admin.banners.store');
    Route::put('/banners/{id}', [AdminSettingWebController::class, 'updateBanner'])->name('admin.banners.update');
    Route::delete('/banners/{id}', [AdminSettingWebController::class, 'destroyBanner'])->name('admin.banners.destroy');
    Route::get('/media', [AdminSettingWebController::class, 'media'])->name('admin.media.index');
    Route::post('/media', [AdminSettingWebController::class, 'storeMedia'])->name('admin.media.store');
    Route::delete('/media/{id}', [AdminSettingWebController::class, 'destroyMedia'])->name('admin.media.destroy');
    Route::get('/media/picker', [AdminSettingWebController::class, 'mediaPicker'])->name('admin.media.picker');
    Route::post('/media/picker', [AdminSettingWebController::class, 'mediaPickerUpload'])->name('admin.media.picker.upload');

    // Insights
    Route::get('/analytics', [AdminModuleWebController::class, 'analytics'])->name('admin.analytics.index');

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
    Route::get('/backup', [AdminSettingWebController::class, 'backup'])->name('admin.backup.index');
    Route::post('/backup', [AdminSettingWebController::class, 'createBackup'])->name('admin.backup.create');
    Route::get('/backup/{id}/download', [AdminSettingWebController::class, 'downloadBackup'])->name('admin.backup.download');
    Route::post('/backup/restore', [AdminSettingWebController::class, 'restoreBackup'])->name('admin.backup.restore');
    Route::delete('/backup/{id}', [AdminSettingWebController::class, 'deleteBackup'])->name('admin.backup.destroy');

    // System
    Route::get('/inventory', [AdminModuleWebController::class, 'inventory'])->name('admin.inventory.index');
    Route::post('/inventory/{id}/adjust', [AdminModuleWebController::class, 'adjustStock'])->name('admin.inventory.adjust');
    Route::get('/settings/business', [AdminSettingWebController::class, 'businessSettings'])->name('admin.settings.business');
    Route::post('/settings/business', [AdminSettingWebController::class, 'updateBusinessSettings'])->name('admin.settings.business.update');
    Route::get('/settings', [AdminSettingWebController::class, 'settings'])->name('admin.settings.index');
    Route::post('/settings', [AdminSettingWebController::class, 'updateSettings'])->name('admin.settings.update');
    Route::match(['get', 'post'], '/fix-storage', [AdminSettingWebController::class, 'fixStorageWeb'])->name('admin.fix-storage');
});

// Storage and upload fallbacks for cPanel / shared hosting environments where symlink may be broken or disabled
Route::get('/storage/{path}', [StorageFileController::class, 'show'])->where('path', '.*');
Route::get('/uploads/{path}', [StorageFileController::class, 'showUploads'])->where('path', '.*');
