<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\CheckoutNoticeController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CostController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RentalController;
use App\Http\Controllers\Api\MemoController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes — 100% Parity with Node Express Backend
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'Mama Bazar API',
        'status' => 'healthy',
        'uptime' => defined('LARAVEL_START') ? (int) (microtime(true) - LARAVEL_START) : 0,
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ==================== USERS & AUTH ====================
Route::prefix('users')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/password-reset-request', [AuthController::class, 'requestPasswordReset']);
    Route::post('/password-reset', [AuthController::class, 'resetPassword']);
    Route::post('/dev-login', [AuthController::class, 'devLogin']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'getProfile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/orders', [AuthController::class, 'getOrderHistory']);
        Route::get('/addresses', [AuthController::class, 'getAddresses']);
        Route::post('/addresses', [AuthController::class, 'createAddress']);
        Route::put('/addresses/{id}', [AuthController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [AuthController::class, 'deleteAddress']);

        Route::post('/admin', [AuthController::class, 'createAdmin'])->middleware('require.permission:members.create');
        Route::get('/', [AuthController::class, 'getAll'])->middleware('require.permission:customers.view');
        Route::delete('/{id}', [AuthController::class, 'remove'])->middleware('require.permission:customers.delete');
    });
});

// ==================== CATEGORIES ====================
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'getAll']);
    Route::get('/tree', [CategoryController::class, 'getTree']);
    Route::get('/slug/{slug}', [CategoryController::class, 'getBySlug']);
    Route::get('/{id}', [CategoryController::class, 'getById']);
    Route::get('/{id}/products', [CategoryController::class, 'getProducts']);
    Route::get('/{id}/count', [CategoryController::class, 'getProductCount']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/', [CategoryController::class, 'create'])->middleware('require.permission:categories.create');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('require.permission:categories.update');
        Route::delete('/{id}', [CategoryController::class, 'remove'])->middleware('require.permission:categories.delete');
        Route::post('/{id}/move-products', [CategoryController::class, 'moveProducts'])->middleware('require.permission:categories.update');
    });
});

// ==================== PRODUCTS ====================
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'getAll']);
    Route::get('/slug/{slug}', [ProductController::class, 'getBySlug']);
    Route::get('/category/{categoryId}', [ProductController::class, 'getByCategory']);
    Route::get('/collection/{collectionId}', [ProductController::class, 'getByCollection']);
    Route::get('/brand/{brandId}', [ProductController::class, 'getByBrand']);
    Route::get('/vendor/{vendorId}', [ProductController::class, 'getByVendor']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'getById']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/', [ProductController::class, 'create'])->middleware('require.permission:products.create');
        Route::put('/{id}', [ProductController::class, 'update'])->middleware('require.permission:products.update');
        Route::delete('/{id}', [ProductController::class, 'remove'])->middleware('require.permission:products.delete');
        Route::post('/bulk/delete', [ProductController::class, 'bulkDelete'])->middleware('require.permission:products.delete');
        Route::post('/bulk/update-status', [ProductController::class, 'bulkUpdateStatus'])->middleware('require.permission:products.update');
        Route::post('/{id}/duplicate', [ProductController::class, 'duplicate'])->middleware('require.permission:products.create');
    });
});

// ==================== ORDERS ====================
Route::prefix('order')->group(function () {
    Route::post('/', [OrderController::class, 'createOrder']);
    Route::get('/track/{id}', [OrderController::class, 'trackOrder']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/admin/all', [OrderController::class, 'getAllOrders'])->middleware('require.permission:orders.view');
        Route::get('/admin/stats', [OrderController::class, 'getOrderStats'])->middleware('require.permission:orders.view');
        Route::get('/admin/{id}', [OrderController::class, 'getOrderById'])->middleware('require.permission:orders.view');
        Route::patch('/admin/{id}/status', [OrderController::class, 'updateOrderStatus'])->middleware('require.permission:orders.update_status');
        Route::patch('/admin/{id}/courier', [OrderController::class, 'updateCourierTracking'])->middleware('require.permission:orders.update_status');
    });
});

// ==================== COUPONS ====================
Route::prefix('coupons')->group(function () {
    Route::post('/validate', [CouponController::class, 'validateCoupon']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/', [CouponController::class, 'getAll'])->middleware('require.permission:coupons.view');
        Route::get('/{id}', [CouponController::class, 'getById'])->middleware('require.permission:coupons.view');
        Route::post('/', [CouponController::class, 'create'])->middleware('require.permission:coupons.manage');
        Route::put('/{id}', [CouponController::class, 'update'])->middleware('require.permission:coupons.manage');
        Route::delete('/{id}', [CouponController::class, 'remove'])->middleware('require.permission:coupons.manage');
    });
});

// ==================== SETTINGS ====================
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'getAll']);
    Route::get('/hero-slides', [SettingsController::class, 'getHeroSlides']);
    Route::get('/store-info', [SettingsController::class, 'getStoreInfo']);
    Route::get('/{key}', [SettingsController::class, 'get']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/hero-slides/link', [SettingsController::class, 'addHeroSlideByLink'])->middleware('require.permission:homepage.manage');
        Route::put('/', [SettingsController::class, 'set'])->middleware('require.permission:settings.manage');
        Route::post('/hero-slides', [SettingsController::class, 'addHeroSlide'])->middleware('require.permission:homepage.manage');
        Route::delete('/hero-slides/{index}', [SettingsController::class, 'deleteHeroSlide'])->middleware('require.permission:homepage.manage');
    });
});

// ==================== TRACKING ====================
Route::prefix('tracking')->group(function () {
    Route::get('/config', [TrackingController::class, 'getConfig']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/', [TrackingController::class, 'getAll'])->middleware('require.permission:marketing.view');
        Route::get('/logs', [TrackingController::class, 'getLogs'])->middleware('require.permission:marketing.view');
        Route::get('/{id}', [TrackingController::class, 'getById'])->middleware('require.permission:marketing.view');
        Route::post('/', [TrackingController::class, 'create'])->middleware('require.permission:marketing.manage');
        Route::put('/{id}', [TrackingController::class, 'update'])->middleware('require.permission:marketing.manage');
        Route::delete('/{id}', [TrackingController::class, 'remove'])->middleware('require.permission:marketing.manage');
    });
});

// ==================== ANALYTICS ====================
Route::prefix('analytics')->group(function () {
    Route::post('/purchase', [AnalyticsController::class, 'trackPurchase']);
});

// ==================== ADMIN DASHBOARD ====================
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'getDashboard'])->middleware(['jwt.auth', 'require.permission:dashboard.view']);
});

// ==================== CATALOG ROUTERS (Colors, Sizes, Collections, Vendors, Suppliers, Brands) ====================
$catalogEntities = [
    'colors' => 'catalog.manage',
    'sizes' => 'catalog.manage',
    'collections' => 'catalog.manage',
    'vendors' => 'catalog.manage',
    'suppliers' => 'catalog.manage',
    'brands' => 'catalog.manage',
];

foreach ($catalogEntities as $type => $permission) {
    Route::prefix($type)->group(function () use ($type, $permission) {
        Route::get('/', function (Illuminate\Http\Request $request) use ($type) {
            return app(CatalogController::class)->list($request, $type);
        });

        Route::middleware('jwt.auth')->group(function () use ($type, $permission) {
            Route::get('/admin', function (Illuminate\Http\Request $request) use ($type) {
                return app(CatalogController::class)->listAdmin($request, $type);
            })->middleware('require.permission:catalog.view');

            Route::get('/{id}/usage', function (Illuminate\Http\Request $request, $id) use ($type) {
                return app(CatalogController::class)->getUsage($request, $type, $id);
            })->middleware('require.permission:catalog.view');

            Route::post('/', function (Illuminate\Http\Request $request) use ($type) {
                return app(CatalogController::class)->create($request, $type);
            })->middleware("require.permission:{$permission}");

            Route::put('/{id}', function (Illuminate\Http\Request $request, $id) use ($type) {
                return app(CatalogController::class)->update($request, $type, $id);
            })->middleware("require.permission:{$permission}");

            Route::delete('/{id}', function (Illuminate\Http\Request $request, $id) use ($type) {
                return app(CatalogController::class)->remove($request, $type, $id);
            })->middleware("require.permission:{$permission}");

            Route::post('/{id}/move-products', function (Illuminate\Http\Request $request, $id) use ($type) {
                return app(CatalogController::class)->moveProducts($request, $type, $id);
            })->middleware("require.permission:{$permission}");
        });

        Route::get('/slug/{slug}', function (Illuminate\Http\Request $request, $slug) use ($type) {
            return app(CatalogController::class)->getBySlug($request, $type, $slug);
        });

        Route::get('/{id}', function (Illuminate\Http\Request $request, $id) use ($type) {
            return app(CatalogController::class)->getById($request, $type, $id);
        });
    });
}

// ==================== BANNERS ====================
Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'getAll']);
    Route::get('/slot/{slot}', [BannerController::class, 'getBySlot']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/admin', [BannerController::class, 'getAllAdmin'])->middleware('require.permission:homepage.manage');
        Route::get('/{id}', [BannerController::class, 'getById']);
        Route::post('/', [BannerController::class, 'create'])->middleware('require.permission:homepage.manage');
        Route::put('/{id}', [BannerController::class, 'update'])->middleware('require.permission:homepage.manage');
        Route::delete('/{id}', [BannerController::class, 'remove'])->middleware('require.permission:homepage.manage');
    });
});

// ==================== MEDIA ====================
Route::prefix('media')->middleware('jwt.auth')->group(function () {
    Route::get('/', [MediaController::class, 'list'])->middleware('require.permission:media.view');
    Route::post('/', [MediaController::class, 'create'])->middleware('require.permission:media.upload');
    Route::delete('/{id}', [MediaController::class, 'remove'])->middleware('require.permission:media.delete');
});

// ==================== SHIPPING METHODS ====================
Route::prefix('shipping-methods')->group(function () {
    Route::get('/public', [ShippingController::class, 'getActiveMethods']);
    Route::post('/estimate', [ShippingController::class, 'estimateShipping']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/', [ShippingController::class, 'getAll'])->middleware('require.permission:shipping.view');
        Route::get('/{id}', [ShippingController::class, 'getById'])->middleware('require.permission:shipping.view');
        Route::post('/', [ShippingController::class, 'create'])->middleware('require.permission:shipping.manage');
        Route::put('/{id}', [ShippingController::class, 'update'])->middleware('require.permission:shipping.manage');
        Route::delete('/{id}', [ShippingController::class, 'remove'])->middleware('require.permission:shipping.manage');
    });
});

// ==================== PAYMENT METHODS ====================
Route::prefix('payment-methods')->group(function () {
    Route::get('/public', [PaymentMethodController::class, 'getActiveMethods']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'getAll'])->middleware('require.permission:payment_methods.view');
        Route::put('/', [PaymentMethodController::class, 'updateStatuses'])->middleware('require.permission:payment_methods.manage');
        Route::get('/{id}', [PaymentMethodController::class, 'getById'])->middleware('require.permission:payment_methods.view');
        Route::post('/', [PaymentMethodController::class, 'create'])->middleware('require.permission:payment_methods.manage');
        Route::put('/{id}', [PaymentMethodController::class, 'update'])->middleware('require.permission:payment_methods.manage');
        Route::delete('/{id}', [PaymentMethodController::class, 'remove'])->middleware('require.permission:payment_methods.manage');
    });
});

// ==================== CHECKOUT NOTICES ====================
Route::prefix('checkout-notices')->group(function () {
    Route::get('/public', [CheckoutNoticeController::class, 'getActiveNotices']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/', [CheckoutNoticeController::class, 'getAll'])->middleware('require.permission:checkout_notices.view');
        Route::get('/{id}', [CheckoutNoticeController::class, 'getById'])->middleware('require.permission:checkout_notices.view');
        Route::post('/', [CheckoutNoticeController::class, 'create'])->middleware('require.permission:checkout_notices.manage');
        Route::put('/{id}', [CheckoutNoticeController::class, 'update'])->middleware('require.permission:checkout_notices.manage');
        Route::delete('/{id}', [CheckoutNoticeController::class, 'remove'])->middleware('require.permission:checkout_notices.manage');
    });
});

// ==================== UPLOADS ====================
Route::prefix('uploads')->middleware('jwt.auth')->group(function () {
    Route::post('/', [UploadController::class, 'upload']);
    Route::post('/multiple', [UploadController::class, 'uploadMultiple']);
    Route::delete('/{publicId}', [UploadController::class, 'delete']);
});

// ==================== REVIEWS ====================
Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'getAll']);
    Route::get('/admin/list', [ReviewController::class, 'getAllAdmin'])->middleware(['jwt.auth', 'admin.only']);
    Route::get('/{id}', [ReviewController::class, 'getById']);

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/', [ReviewController::class, 'create']);
        Route::patch('/{id}/status', [ReviewController::class, 'updateStatus'])->middleware('admin.only');
        Route::delete('/{id}', [ReviewController::class, 'remove'])->middleware('admin.only');
    });
});

// ==================== HOMEPAGE ====================
Route::prefix('homepage')->group(function () {
    Route::get('/', [HomepageController::class, 'getHomepageData']);
    Route::get('/config', [HomepageController::class, 'getConfig']);
    Route::post('/newsletter/subscribe', [HomepageController::class, 'subscribeNewsletter']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/admin/config', [HomepageController::class, 'getConfig'])->middleware('require.permission:homepage.view');
        Route::put('/admin/config', [HomepageController::class, 'saveConfig'])->middleware('require.permission:homepage.manage');
        Route::post('/admin/reset-defaults', [HomepageController::class, 'resetConfig'])->middleware('require.permission:homepage.manage');
        Route::get('/admin/subscribers', [HomepageController::class, 'getSubscribers'])->middleware('require.permission:marketing.view');
    });
});

// ==================== PAGES ====================
Route::prefix('pages')->group(function () {
    Route::get('/p/{slug}', [PagesController::class, 'getBySlug']);
    Route::post('/contact', [PagesController::class, 'submitContact']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/contact', [PagesController::class, 'getContactMessages'])->middleware('require.permission:policies.view');
        Route::patch('/contact/{id}', [PagesController::class, 'updateContactStatus'])->middleware('require.permission:policies.manage');
        Route::get('/', [PagesController::class, 'getAll'])->middleware('require.permission:policies.view');
        Route::post('/', [PagesController::class, 'create'])->middleware('require.permission:policies.manage');
        Route::put('/{id}', [PagesController::class, 'update'])->middleware('require.permission:policies.manage');
        Route::delete('/{id}', [PagesController::class, 'remove'])->middleware('require.permission:policies.manage');
    });
});

// ==================== EXPENSES ====================
Route::prefix('expenses')->middleware('jwt.auth')->group(function () {
    Route::get('/categories', [ExpenseController::class, 'categories'])->middleware('require.permission:expenses.view');
    Route::post('/categories', [ExpenseController::class, 'createCategory'])->middleware('require.permission:expenses.create');
    Route::put('/categories/{id}', [ExpenseController::class, 'updateCategory'])->middleware('require.permission:expenses.update');
    Route::delete('/categories/{id}', [ExpenseController::class, 'removeCategory'])->middleware('require.permission:expenses.delete');

    Route::get('/members', [ExpenseController::class, 'members'])->middleware('require.permission:expenses.view');

    Route::get('/summary', [ExpenseController::class, 'summary'])->middleware('require.permission:reports.view');
    Route::get('/by-member', [ExpenseController::class, 'byMember'])->middleware('require.permission:reports.view');
    Route::get('/by-category', [ExpenseController::class, 'byCategory'])->middleware('require.permission:reports.view');
    Route::get('/monthly', [ExpenseController::class, 'monthlyReport'])->middleware('require.permission:reports.view');
    Route::get('/trends', [ExpenseController::class, 'monthlyTrend'])->middleware('require.permission:reports.view');
    Route::get('/report', [ExpenseController::class, 'rangeReport'])->middleware('require.permission:reports.view');
    Route::get('/profit', [ExpenseController::class, 'profitOverview'])->middleware('require.permission:reports.view');
    Route::get('/export/csv', [ExpenseController::class, 'exportCsv'])->middleware('require.permission:reports.export');

    Route::get('/', [ExpenseController::class, 'list'])->middleware('require.permission:expenses.view');
    Route::post('/', [ExpenseController::class, 'create'])->middleware('require.permission:expenses.create');
    Route::put('/{id}', [ExpenseController::class, 'update'])->middleware('require.permission:expenses.update');
    Route::delete('/{id}', [ExpenseController::class, 'remove'])->middleware('require.permission:expenses.delete');
    Route::get('/{id}', [ExpenseController::class, 'getById'])->middleware('require.permission:expenses.view');
});

// ==================== COSTS ====================
Route::prefix('costs')->middleware('jwt.auth')->group(function () {
    Route::get('/', [CostController::class, 'list'])->middleware('require.permission:costs.view');
    Route::get('/{id}', [CostController::class, 'getById'])->middleware('require.permission:costs.view');
    Route::post('/', [CostController::class, 'create'])->middleware('require.permission:costs.create');
    Route::put('/{id}', [CostController::class, 'update'])->middleware('require.permission:costs.update');
    Route::delete('/{id}', [CostController::class, 'remove'])->middleware('require.permission:costs.delete');
});

// ==================== BOOKINGS ====================
Route::prefix('bookings')->middleware('jwt.auth')->group(function () {
    Route::get('/', [BookingController::class, 'list'])->middleware('require.permission:bookings.view');
    Route::get('/{id}', [BookingController::class, 'getById'])->middleware('require.permission:bookings.view');
    Route::post('/', [BookingController::class, 'create'])->middleware('require.permission:bookings.create');
    Route::put('/{id}', [BookingController::class, 'update'])->middleware('require.permission:bookings.update');
    Route::delete('/{id}', [BookingController::class, 'remove'])->middleware('require.permission:bookings.delete');
});

// ==================== RENTALS ====================
Route::prefix('rentals')->middleware('jwt.auth')->group(function () {
    Route::get('/', [RentalController::class, 'list'])->middleware('require.permission:rentals.view');
    Route::get('/{id}', [RentalController::class, 'getById'])->middleware('require.permission:rentals.view');
    Route::post('/', [RentalController::class, 'create'])->middleware('require.permission:rentals.create');
    Route::put('/{id}', [RentalController::class, 'update'])->middleware('require.permission:rentals.update');
    Route::delete('/{id}', [RentalController::class, 'remove'])->middleware('require.permission:rentals.delete');
});

// ==================== MEMOS ====================
Route::prefix('memos')->middleware('jwt.auth')->group(function () {
    Route::get('/', [MemoController::class, 'list'])->middleware('require.permission:memos.view');
    Route::get('/{id}', [MemoController::class, 'getById'])->middleware('require.permission:memos.view');
    Route::post('/', [MemoController::class, 'create'])->middleware('require.permission:memos.upload');
    Route::delete('/{id}', [MemoController::class, 'remove'])->middleware('require.permission:memos.delete');
    Route::post('/bulk-delete', [MemoController::class, 'removeMany'])->middleware('require.permission:memos.delete');
});

// ==================== MEMBERS ====================
Route::prefix('members')->middleware('jwt.auth')->group(function () {
    Route::get('/roles-permissions', [MemberController::class, 'getRolesAndPermissions'])->middleware('require.permission:members.view');
    Route::get('/audit-logs', [MemberController::class, 'listAuditLogs'])->middleware('require.permission:members.view');
    Route::get('/', [MemberController::class, 'listMembers'])->middleware('require.permission:members.view');
    Route::post('/', [MemberController::class, 'createMember'])->middleware('require.permission:members.create');
    Route::put('/{id}', [MemberController::class, 'updateMember'])->middleware('require.permission:members.update');
    Route::delete('/{id}', [MemberController::class, 'deleteMember'])->middleware('require.permission:members.delete');
});

// ==================== BACKUP ====================
Route::prefix('backup')->middleware('jwt.auth')->group(function () {
    Route::get('/history', [BackupController::class, 'listBackups'])->middleware('require.permission:backup.view');
    Route::post('/verify-pin', [BackupController::class, 'verifyPin'])->middleware('require.permission:backup.view');
    Route::post('/create', [BackupController::class, 'createBackup'])->middleware('require.permission:backup.create');
    Route::get('/download/{id}', [BackupController::class, 'downloadBackup'])->middleware('require.permission:backup.create');
    Route::post('/restore', [BackupController::class, 'restoreBackup'])->middleware('require.permission:backup.restore');
    Route::delete('/{id}', [BackupController::class, 'deleteBackup'])->middleware('require.permission:backup.restore');
});

// ==================== CHAT ====================
Route::prefix('chat')->group(function () {
    Route::post('/', [ChatController::class, 'handleChat']);
});
