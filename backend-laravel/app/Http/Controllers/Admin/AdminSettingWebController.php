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
        $methods = PaymentMethod::orderBy('sort_order', 'asc')->get();
        return view('admin.settings.payments', compact('methods'));
    }

    public function togglePaymentMethod($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->enabled = !$method->enabled;
        $method->save();
        return back()->with('success', 'Payment method status toggled.');
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
