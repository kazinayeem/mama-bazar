<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\ProductWebController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\OrderTrackingController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\PageWebController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProductWebController;
use App\Http\Controllers\Admin\AdminOrderWebController;
use App\Http\Controllers\Admin\AdminCategoryWebController;
use App\Http\Controllers\Admin\AdminCustomerWebController;
use App\Http\Controllers\Admin\AdminCouponWebController;
use App\Http\Controllers\Admin\AdminSettingWebController;
use App\Http\Controllers\Admin\AdminCatalogWebController;
use App\Http\Controllers\Admin\AdminModuleWebController;

/*
|--------------------------------------------------------------------------
| Web Routes (User-Facing Blade UI)
|--------------------------------------------------------------------------
*/

Route::get('/', function (\Illuminate\Http\Request $request) {
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
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/order/success', [CheckoutController::class, 'success'])->name('order.success');
Route::get('/track', [OrderTrackingController::class, 'index'])->name('track');

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::get('/auth/login', [AuthWebController::class, 'showLogin']);
Route::post('/login', [AuthWebController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
Route::get('/auth/register', [AuthWebController::class, 'showRegister']);
Route::post('/register', [AuthWebController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

Route::get('/about', [PageWebController::class, 'about'])->name('about');
Route::get('/faq', [PageWebController::class, 'faq'])->name('faq');
Route::get('/contact', [PageWebController::class, 'contact'])->name('contact');
Route::post('/contact', [PageWebController::class, 'submitContact'])->name('contact.submit');
Route::get('/pages/{slug}', [PageWebController::class, 'show'])->name('page.show');

Route::get('/refund-policy', fn () => app(PageWebController::class)->show('return-refund'));
Route::get('/return-refund-policy', fn () => app(PageWebController::class)->show('return-refund'));
Route::get('/shipping-policy', fn () => app(PageWebController::class)->show('shipping'));
Route::get('/privacy-policy', fn () => app(PageWebController::class)->show('privacy-policy'));
Route::get('/terms-and-conditions', fn () => app(PageWebController::class)->show('terms-and-conditions'));
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

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Products
    Route::get('/products', [AdminProductWebController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductWebController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductWebController::class, 'store'])->name('admin.products.store');
    Route::get('/products/export', [AdminProductWebController::class, 'exportCsv'])->name('admin.products.export');
    Route::post('/products/import', [AdminProductWebController::class, 'importCsv'])->name('admin.products.import');
    Route::post('/products/bulk', [AdminProductWebController::class, 'bulkAction'])->name('admin.products.bulk');
    Route::post('/products/upload-image', [AdminProductWebController::class, 'uploadImage'])->name('admin.products.upload-image');
    Route::get('/products/{id}', [AdminProductWebController::class, 'show'])->name('admin.products.show');
    Route::get('/products/{id}/edit', [AdminProductWebController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{id}', [AdminProductWebController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminProductWebController::class, 'destroy'])->name('admin.products.destroy');
    Route::post('/products/{id}/duplicate', [AdminProductWebController::class, 'duplicate'])->name('admin.products.duplicate');
    Route::post('/products/{id}/toggle-featured', [AdminProductWebController::class, 'toggleFeatured'])->name('admin.products.toggle-featured');
    Route::post('/products/{id}/toggle', [AdminProductWebController::class, 'toggleStatus'])->name('admin.products.toggle');

    // Orders
    Route::get('/orders', [AdminOrderWebController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{id}', [AdminOrderWebController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{id}/invoice', [AdminOrderWebController::class, 'invoice'])->name('admin.orders.invoice');
    Route::post('/orders/{id}/status', [AdminOrderWebController::class, 'updateStatus'])->name('admin.orders.status');

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
        Route::post("/{$resource}", fn (\Illuminate\Http\Request $r) => app(AdminCatalogWebController::class)->store($r, $resource))
            ->name("admin.{$resource}.store");
        Route::put("/{$resource}/{id}", fn (\Illuminate\Http\Request $r, $id) => app(AdminCatalogWebController::class)->update($r, $resource, (int) $id))
            ->name("admin.{$resource}.update");
        Route::delete("/{$resource}/{id}", fn ($id) => app(AdminCatalogWebController::class)->destroy($resource, (int) $id))
            ->name("admin.{$resource}.destroy");
    }

    // Coupons
    Route::get('/coupons', [AdminCouponWebController::class, 'index'])->name('admin.coupons.index');
    Route::post('/coupons', [AdminCouponWebController::class, 'store'])->name('admin.coupons.store');
    Route::delete('/coupons/{id}', [AdminCouponWebController::class, 'destroy'])->name('admin.coupons.destroy');

    // Customers
    Route::get('/customers', [AdminCustomerWebController::class, 'index'])->name('admin.customers.index');
    Route::post('/customers/{id}/toggle', [AdminCustomerWebController::class, 'toggleStatus'])->name('admin.customers.toggle');

    // Marketing
    Route::get('/marketing', [AdminModuleWebController::class, 'marketing'])->name('admin.marketing.index');
    Route::post('/marketing', [AdminModuleWebController::class, 'storeMarketing'])->name('admin.marketing.store');
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
    Route::get('/payment-methods', [AdminSettingWebController::class, 'paymentMethods'])->name('admin.payment-methods.index');
    Route::post('/payment-methods/{id}/toggle', [AdminSettingWebController::class, 'togglePaymentMethod'])->name('admin.payment-methods.toggle');
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
    Route::delete('/banners/{id}', [AdminSettingWebController::class, 'destroyBanner'])->name('admin.banners.destroy');
    Route::get('/media', [AdminSettingWebController::class, 'media'])->name('admin.media.index');
    Route::post('/media', [AdminSettingWebController::class, 'storeMedia'])->name('admin.media.store');

    // Insights
    Route::get('/analytics', [AdminModuleWebController::class, 'analytics'])->name('admin.analytics.index');

    // Security
    Route::get('/members', [AdminModuleWebController::class, 'members'])->name('admin.members.index');
    Route::post('/members', [AdminModuleWebController::class, 'storeMember'])->name('admin.members.store');
    Route::put('/members/{id}', [AdminModuleWebController::class, 'updateMember'])->name('admin.members.update');
    Route::delete('/members/{id}', [AdminModuleWebController::class, 'destroyMember'])->name('admin.members.destroy');
    Route::get('/backup', [AdminSettingWebController::class, 'backup'])->name('admin.backup.index');
    Route::post('/backup', [AdminSettingWebController::class, 'createBackup'])->name('admin.backup.create');

    // System
    Route::get('/inventory', [AdminModuleWebController::class, 'inventory'])->name('admin.inventory.index');
    Route::post('/inventory/{id}/adjust', [AdminModuleWebController::class, 'adjustStock'])->name('admin.inventory.adjust');
    Route::get('/settings', [AdminSettingWebController::class, 'settings'])->name('admin.settings.index');
    Route::post('/settings', [AdminSettingWebController::class, 'updateSettings'])->name('admin.settings.update');
});
