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
        foreach ($request->except(['_token']) as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Site settings updated successfully.');
    }

    public function shipping()
    {
        $methods = ShippingMethod::orderBy('priority', 'asc')->get();
        return view('admin.settings.shipping', compact('methods'));
    }

    public function storeShipping(Request $request)
    {
        $request->validate(['name' => 'required|string', 'charge' => 'required|numeric']);
        ShippingMethod::create($request->all());
        return back()->with('success', 'Shipping method created.');
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
}
