<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminPermission;
use App\Models\AdminBackup;
use App\Models\Banner;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Services\ActivityLoggerService;
use App\Services\BackupService;
use App\Services\BusinessSettingService;
use App\Services\MediaStorageService;
use App\Support\SslcommerzSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSettingWebController extends Controller
{
    public function settings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');

        return view('admin.settings.index', compact('settings'));
    }

    protected function authorizeAdmin(): void
    {
        $role = Auth::user()?->role;
        if (! in_array($role, ['admin', 'manager', 'superadmin'], true)) {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    public function businessSettings()
    {
        $this->authorizeAdmin();
        $business = BusinessSettingService::all();

        return view('admin.settings.business', compact('business'));
    }

    public function updateBusinessSettings(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'business_name' => 'required|string|max:255',
            'site_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'business_description' => 'nullable|string|max:1000',
            'logo_url' => 'nullable|string|max:500',
            'logo_file' => 'nullable|image|max:5120',
            'favicon_url' => 'nullable|string|max:500',
            'favicon_file' => 'nullable|image|max:2048',
            'primary_phone' => 'required|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'support_phone' => 'nullable|string|max:50',
            'primary_email' => 'required|email|max:255',
            'support_email' => 'nullable|email|max:255',
            'sales_email' => 'nullable|email|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'support_url' => 'nullable|string|max:500',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'copyright_text' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string|max:255',
            'footer_description' => 'nullable|string|max:1000',
            'return_policy_url' => 'nullable|string|max:255',
            'privacy_policy_url' => 'nullable|string|max:255',
            'terms_url' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['logo_file', 'favicon_file']);

        if ($request->hasFile('logo_file')) {
            $upload = MediaStorageService::uploadFile($request->file('logo_file'), 'branding');
            $data['logo_url'] = $upload['url'];
        }

        if ($request->hasFile('favicon_file')) {
            $upload = MediaStorageService::uploadFile($request->file('favicon_file'), 'branding');
            $data['favicon_url'] = $upload['url'];
        }

        BusinessSettingService::setMany($data);

        return back()->with('success', 'Business information updated successfully.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            '*' => 'nullable|string|max:10000',
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, ['_token', '_method'], true)) {
                continue;
            }
            if (! preg_match('/^[a-zA-Z0-9_.\-]{1,100}$/', (string) $key)) {
                continue;
            }
            SiteSetting::updateOrCreate(['key' => $key], ['value' => is_string($value) ? trim($value) : $value]);
        }

        BusinessSettingService::clearCache();

        return back()->with('success', 'Site settings updated successfully.');
    }

    public function shipping()
    {
        $methods = ShippingMethod::orderBy('priority', 'asc')->orderBy('id')->get();

        return view('admin.settings.shipping', compact('methods'));
    }

    protected function shippingRules(bool $isUpdate = false): array
    {
        return [
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0|max:100000',
            'estimated_delivery' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'applicable_areas' => 'nullable|string|max:2000',
            'priority' => 'nullable|integer|min:0|max:10000',
            'free_shipping_min_amount' => 'nullable|numeric|min:0|max:1000000',
            'cod_available' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function shippingPayload(Request $request, ?ShippingMethod $existing = null): array
    {
        $v = $request->validate($this->shippingRules($existing !== null));
        $maxPriority = (int) (ShippingMethod::max('priority') ?? 0);

        return [
            'name' => trim($v['name']),
            'charge' => (float) $v['charge'],
            'estimated_delivery' => isset($v['estimated_delivery']) ? trim((string) $v['estimated_delivery']) : null,
            'description' => isset($v['description']) ? trim((string) $v['description']) : null,
            'applicable_areas' => isset($v['applicable_areas']) ? trim((string) $v['applicable_areas']) : null,
            'priority' => isset($v['priority']) && $v['priority'] !== null ? (int) $v['priority'] : ($existing?->priority ?? $maxPriority + 10),
            'free_shipping_min_amount' => isset($v['free_shipping_min_amount']) && $v['free_shipping_min_amount'] !== null && $v['free_shipping_min_amount'] !== '' ? (float) $v['free_shipping_min_amount'] : null,
            'cod_available' => $request->boolean('cod_available'),
            'status' => $v['status'] ?? ($existing?->status ?? 'active'),
        ];
    }

    public function storeShipping(Request $request)
    {
        ShippingMethod::create($this->shippingPayload($request));

        return back()->with('success', 'Shipping method created.');
    }

    public function updateShipping(Request $request, $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update($this->shippingPayload($request, $method));

        return back()->with('success', 'Shipping method updated.');
    }

    public function toggleShipping($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->status = $method->status === 'active' ? 'inactive' : 'active';
        $method->save();

        return back()->with('success', "Shipping method {$method->status}.");
    }

    public function reorderShipping(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer',
        ]);
        foreach ($validated['order'] as $index => $id) {
            ShippingMethod::where('id', $id)->update(['priority' => ($index + 1) * 10]);
        }

        return back()->with('success', 'Shipping order updated.');
    }

    public function destroyShipping($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $ordersUsing = Order::where('shipping_method_id', $method->id)->count();
        if ($ordersUsing > 0) {
            // Safe-delete: keep history, deactivate instead.
            $method->status = 'inactive';
            $method->save();

            return back()->with('success', "Method is used by {$ordersUsing} order(s) — deactivated instead of deleted to preserve history.");
        }
        $method->delete();

        return back()->with('success', 'Shipping method deleted.');
    }

    public function checkoutSettings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        $checkout = [];
        $raw = $settings['checkout_settings'] ?? null;
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $checkout = $decoded;
            }
        }

        return view('admin.settings.checkout', compact('settings', 'checkout'));
    }

    public function updateCheckoutSettings(Request $request)
    {
        $validated = $request->validate([
            'min_order_amount' => 'nullable|numeric|min:0|max:1000000',
            'free_shipping_threshold' => 'nullable|numeric|min:0|max:1000000',
            'default_district' => 'nullable|string|max:100',
            'require_alt_phone' => 'nullable|boolean',
            'allow_notes' => 'nullable|boolean',
            'cod_note' => 'nullable|string|max:1000',
            'announcement_enabled' => 'nullable|boolean',
        ]);
        $existing = [];
        $row = SiteSetting::where('key', 'checkout_settings')->first();
        if ($row && is_array(json_decode((string) $row->value, true))) {
            $existing = json_decode((string) $row->value, true);
        }
        $payload = [
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : 0,
            'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? null,
            'default_district' => trim((string) ($validated['default_district'] ?? 'Dhaka')),
            'require_alt_phone' => $request->has('require_alt_phone') ? $request->boolean('require_alt_phone') : ($existing['require_alt_phone'] ?? false),
            'allow_notes' => $request->has('allow_notes') ? $request->boolean('allow_notes') : ($existing['allow_notes'] ?? true),
            'cod_note' => trim((string) ($validated['cod_note'] ?? '')),
            'announcement_enabled' => $request->has('announcement_enabled') ? $request->boolean('announcement_enabled') : ($existing['announcement_enabled'] ?? true),
        ];
        SiteSetting::updateOrCreate(['key' => 'checkout_settings'], ['value' => json_encode($payload)]);

        return back()->with('success', 'Checkout settings saved.');
    }

    public function paymentMethods()
    {
        PaymentMethod::ensureDefaults();
        $methods = PaymentMethod::ordered()->get();

        $user = request()->user();
        $gateway = [
            'summary' => SslcommerzSettings::load()->summary(),
            'unlocked' => AdminPaymentGatewayController::isUnlocked(request()),
            'unlockExpiresAt' => AdminPaymentGatewayController::unlockExpiresAt(request()),
            'canConfigure' => EnsureAdminPermission::allows($user, ['payment_methods.configure']),
            'canTest' => EnsureAdminPermission::allows($user, ['payment_methods.test']),
            'canEnableLive' => EnsureAdminPermission::allows($user, ['payment_methods.enable_live']),
            'callbackUrls' => [
                'success' => route('payment.sslcommerz.success'),
                'fail' => route('payment.sslcommerz.fail'),
                'cancel' => route('payment.sslcommerz.cancel'),
                'ipn' => route('payment.sslcommerz.ipn'),
            ],
        ];

        return view('admin.settings.payments', compact('methods', 'gateway'));
    }

    /**
     * Returns an error when this change would enable SSLCOMMERZ before it is ready.
     */
    private function gatewayEnableError(PaymentMethod $method, bool $enabling, Request $request): ?string
    {
        if (! $enabling || $method->code !== SslcommerzSettings::METHOD_CODE || $method->enabled) {
            return null;
        }

        return SslcommerzSettings::load()->enableBlocker($request->user());
    }

    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:cod,mobile_banking,bank,online',
            'enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'maintenance_mode' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        PaymentMethod::create([
            'code' => strtolower(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'enabled' => $request->boolean('enabled', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'config' => $this->normalizePaymentConfig($request->input('config', [])),
        ]);

        return back()->with('success', 'Payment method created.');
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cod,mobile_banking,bank,online',
            'enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'maintenance_mode' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        $enableError = $this->gatewayEnableError($method, $request->boolean('enabled'), $request);
        if ($enableError !== null) {
            return back()->with('error', $enableError);
        }

        $method->update([
            'name' => trim($validated['name']),
            'type' => $method->code === SslcommerzSettings::METHOD_CODE ? 'online' : $validated['type'],
            'enabled' => $request->boolean('enabled'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'config' => $this->normalizePaymentConfig($request->input('config', [])),
        ]);

        return back()->with('success', 'Payment method updated.');
    }

    public function togglePaymentMethod(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);
        $enableError = $this->gatewayEnableError($method, ! $method->enabled, $request);
        if ($enableError !== null) {
            return back()->with('error', $enableError);
        }
        $method->enabled = ! $method->enabled;
        if ($method->enabled && $method->code === SslcommerzSettings::METHOD_CODE) {
            $method->maintenance_mode = false;
        }
        $method->save();

        return back()->with('success', $method->enabled ? 'Payment method enabled.' : 'Payment method disabled.');
    }

    public function bulkPaymentMethodsStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'enabled' => 'required|boolean',
        ]);

        $ids = $validated['ids'];
        $skippedGateway = null;
        if ($request->boolean('enabled')) {
            $gateway = PaymentMethod::whereIn('id', $ids)->where('code', SslcommerzSettings::METHOD_CODE)->first();
            if ($gateway && ($skippedGateway = $this->gatewayEnableError($gateway, true, $request)) !== null) {
                $ids = array_values(array_diff($ids, [$gateway->id]));
            }
        }

        PaymentMethod::whereIn('id', $ids)
            ->update(['enabled' => $request->boolean('enabled')]);

        $action = $request->boolean('enabled') ? 'Enabled' : 'Disabled';
        $redirect = back()->with('success', "{$action} ".count($ids).' payment method(s).');

        return $skippedGateway !== null ? $redirect->with('error', 'SSLCOMMERZ was not enabled: '.$skippedGateway) : $redirect;
    }

    public function destroyPaymentMethod($id)
    {
        $method = PaymentMethod::findOrFail($id);
        if ($method->code === SslcommerzSettings::METHOD_CODE) {
            return back()->with('error', 'The SSLCOMMERZ gateway cannot be deleted. Disable it instead.');
        }
        $method->delete();

        return back()->with('success', 'Payment method deleted.');
    }

    private function normalizePaymentConfig($config): array
    {
        if (! is_array($config)) {
            return [];
        }

        $out = [];
        foreach ($config as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (in_array($key, ['minAmount', 'maxAmount', 'extraFee', 'extraFeePercent'], true) && is_numeric($value)) {
                $out[$key] = 0 + $value;
            } else {
                $out[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $out;
    }

    public function banners()
    {
        $banners = Banner::orderBy('priority', 'desc')->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function storeBanner(Request $request)
    {
        $request->validate(['title' => 'required|string']);
        $data = $request->except(['image']);

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'banners');
            $data['image'] = $upload['url'];
        }

        Banner::create($data);

        return back()->with('success', 'Banner created successfully.');
    }

    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'position' => 'required|string|max:50',
            'link' => 'nullable|string|max:255',
            'priority' => 'nullable|integer',
            'status' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = $request->except(['image']);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                MediaStorageService::deleteFile($banner->image);
            }
            $upload = MediaStorageService::uploadFile($request->file('image'), 'banners');
            $data['image'] = $upload['url'];
        }

        $banner->update($data);

        return back()->with('success', 'Banner updated successfully.');
    }

    public function destroyBanner($id)
    {
        $banner = Banner::findOrFail($id);
        if ($banner->image) {
            MediaStorageService::deleteFile($banner->image);
        }
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    public function media()
    {
        $media = MediaAsset::orderBy('created_at', 'desc')->paginate(24);

        return view('admin.media.index', compact('media'));
    }

    public function storeMedia(Request $request)
    {
        $request->validate(['file' => 'required|file|image|max:10240']);
        $file = $request->file('file');
        $folder = $request->input('folder', 'general');

        $upload = MediaStorageService::uploadFile($file, $folder);

        MediaAsset::create([
            'url' => $upload['url'],
            'public_id' => $upload['publicId'],
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'provider' => 'local',
            'folder' => $folder,
            'uploader_id' => Auth::id(),
        ]);

        return back()->with('success', 'File uploaded locally.');
    }

    public function destroyMedia($id)
    {
        $asset = MediaAsset::findOrFail((int) $id);
        MediaStorageService::deleteFile($asset->url);
        $asset->delete();

        return back()->with('success', 'Media file deleted.');
    }

    /** JSON media library for Alpine media pickers (Homepage Builder, etc.). */
    public function mediaPicker(Request $request)
    {
        $query = MediaAsset::query()->orderByDesc('created_at');

        if ($request->filled('folder') && $request->input('folder') !== 'all') {
            $query->where('folder', $request->input('folder'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('filename', 'like', "%{$s}%")
                    ->orWhere('alt', 'like', "%{$s}%");
            });
        }

        $limit = min(60, max(12, (int) $request->input('limit', 30)));
        $paginator = $query->paginate($limit);

        $folders = MediaAsset::query()
            ->select('folder')
            ->whereNotNull('folder')
            ->distinct()
            ->pluck('folder')
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
            'folders' => array_values(array_unique(array_merge(['general', 'products', 'banners', 'hero'], $folders))),
        ]);
    }

    public function mediaPickerUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|image|max:10240']);
        $file = $request->file('file');
        $folder = $request->input('folder', 'banners');

        $upload = MediaStorageService::uploadFile($file, $folder);

        $asset = MediaAsset::create([
            'url' => $upload['url'],
            'public_id' => $upload['publicId'],
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'provider' => 'local',
            'folder' => $folder,
            'uploader_id' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'data' => $asset], 201);
    }

    public function backup()
    {
        $backups = BackupService::getBackupList();
        $challenge = BackupService::getPinChallenge();

        return view('admin.backup.index', compact('backups', 'challenge'));
    }

    public function createBackup(Request $request)
    {
        $pin = $request->input('pin');
        if (! BackupService::verifyPin($pin)) {
            return back()->with('error', 'Invalid security PIN provided.');
        }

        $user = Auth::user();
        $backup = BackupService::createBackup([
            'type' => 'manual',
            'createdById' => $user->id,
            'actorName' => $user->name,
            'actorEmail' => $user->email,
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);

        ActivityLoggerService::logSystem(
            'system.backup_created',
            "Database backup archive created: {$backup->filename}",
            ['actor' => $user, 'source' => 'admin', 'metadata' => ['filename' => $backup->filename]]
        );

        return back()->with('success', "Database backup created: {$backup->filename}");
    }

    public function downloadBackup($id)
    {
        $backup = AdminBackup::findOrFail((int) $id);
        $filepath = $backup->filepath;

        if ($filepath && file_exists($filepath)) {
            $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $backup->filename);

            return response()->download($filepath, $safeFilename, [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return back()->with('error', 'Backup file is no longer available on this server.');
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
            'file' => 'required|file|mimes:zip|max:102400',
        ]);

        if (! BackupService::verifyPin($request->input('pin'))) {
            return back()->with('error', 'Invalid security PIN provided.');
        }

        $user = Auth::user();

        try {
            BackupService::restoreBackup($request->file('file')->getRealPath(), [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }

        return back()->with('success', 'Database restored. A pre-restore safety backup was preserved.');
    }

    public function deleteBackup(Request $request, $id)
    {
        $request->validate(['pin' => 'required|string']);

        if (! BackupService::verifyPin($request->input('pin'))) {
            return back()->with('error', 'Invalid security PIN provided.');
        }

        $user = Auth::user();

        try {
            BackupService::deleteBackup((int) $id, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }

        return back()->with('success', 'Backup deleted.');
    }

    /**
     * Fix storage permissions, directories, and symlinks for cPanel environments.
     */
    public function fixStorageWeb(Request $request)
    {
        $results = [];

        // 1. Ensure required storage directories exist
        $dirs = [
            storage_path('app'),
            storage_path('app/public'),
            storage_path('app/public/products'),
            storage_path('app/public/categories'),
            storage_path('app/public/banners'),
            storage_path('app/public/payments'),
            storage_path('app/public/general'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
                $results[] = 'Created directory: '.basename($dir);
            }
            @chmod($dir, 0775);
        }

        // 2. Check and fix public/storage symlink
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($link)) {
            $currentTarget = @readlink($link);
            if (! file_exists($link) || ! file_exists($currentTarget)) {
                @unlink($link);
                $results[] = 'Removed broken symlink at public/storage';
            }
        }

        if (! file_exists($link)) {
            // Attempt relative symlink first (best for cPanel)
            $success = false;
            try {
                $relativeTarget = '../storage/app/public';
                $success = @symlink($relativeTarget, $link);
            } catch (\Throwable $e) {
                $success = false;
            }

            if (! $success) {
                try {
                    $success = @symlink($target, $link);
                } catch (\Throwable $e) {
                    $success = false;
                }
            }

            if ($success) {
                $results[] = 'Successfully created storage symlink!';
            } else {
                $results[] = 'Note: Symlink creation is restricted by host, but the built-in HTTP storage fallback route is active and serving all uploaded images.';
            }
        } else {
            $results[] = 'Storage link or folder is present and ready.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Storage check complete',
                'details' => $results,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Storage check complete: '.implode(' | ', $results));
    }
}
