<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\ShippingMethod;
use App\Models\PaymentMethod;
use App\Models\CheckoutNotice;
use App\Models\Banner;
use App\Models\MediaAsset;
use App\Services\BackupService;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSettingWebController extends Controller
{
    public function settings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
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
            if (!preg_match('/^[a-zA-Z0-9_.\-]{1,100}$/', (string) $key)) {
                continue;
            }
            SiteSetting::updateOrCreate(['key' => $key], ['value' => is_string($value) ? trim($value) : $value]);
        }

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
            'cod_available' => $request->boolean('cod_available', $existing?->cod_available ?? true),
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
        $ordersUsing = \App\Models\Order::where('shipping_method_id', $method->id)->count();
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
        $payload = [
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : 0,
            'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? null,
            'default_district' => trim((string) ($validated['default_district'] ?? 'Dhaka')),
            'require_alt_phone' => $request->boolean('require_alt_phone', false),
            'allow_notes' => $request->boolean('allow_notes', true),
            'cod_note' => trim((string) ($validated['cod_note'] ?? '')),
            'announcement_enabled' => $request->boolean('announcement_enabled', true),
        ];
        SiteSetting::updateOrCreate(['key' => 'checkout_settings'], ['value' => json_encode($payload)]);
        return back()->with('success', 'Checkout settings saved.');
    }

    public function paymentMethods()
    {
        PaymentMethod::ensureDefaults();
        $methods = PaymentMethod::ordered()->get();

        return view('admin.settings.payments', compact('methods'));
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

        $method->update([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'enabled' => $request->boolean('enabled'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'config' => $this->normalizePaymentConfig($request->input('config', [])),
        ]);

        return back()->with('success', 'Payment method updated.');
    }

    public function togglePaymentMethod($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->enabled = !$method->enabled;
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

        PaymentMethod::whereIn('id', $validated['ids'])
            ->update(['enabled' => $request->boolean('enabled')]);

        $action = $request->boolean('enabled') ? 'Enabled' : 'Disabled';

        return back()->with('success', "{$action} " . count($validated['ids']) . ' payment method(s).');
    }

    public function destroyPaymentMethod($id)
    {
        PaymentMethod::findOrFail($id)->delete();

        return back()->with('success', 'Payment method deleted.');
    }

    private function normalizePaymentConfig($config): array
    {
        if (!is_array($config)) {
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
        if (!BackupService::verifyPin($pin)) {
            return back()->with('error', 'Invalid security PIN provided.');
        }

        $user = Auth::user();
        $backup = BackupService::createBackup([
            'type'          => 'manual',
            'createdById'   => $user->id,
            'actorName'     => $user->name,
            'actorEmail'    => $user->email,
            'ip'            => $request->ip(),
            'userAgent'     => $request->userAgent(),
        ]);

        return back()->with('success', "Database backup created: {$backup->filename}");
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
                $results[] = "Created directory: " . basename($dir);
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
                $results[] = "Removed broken symlink at public/storage";
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
                $results[] = "Successfully created storage symlink!";
            } else {
                $results[] = "Note: Symlink creation is restricted by host, but the built-in HTTP storage fallback route is active and serving all uploaded images.";
            }
        } else {
            $results[] = "Storage link or folder is present and ready.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Storage check complete',
                'details' => $results,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Storage check complete: ' . implode(' | ', $results));
    }
}
