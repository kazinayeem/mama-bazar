<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\BusinessSettingService;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamWebController extends Controller
{
    /**
     * Display the public Team showcase page.
     */
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $query = TeamMember::query()
            ->active()
            ->public()
            ->ordered();

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleFilter = trim($request->input('role'));
            if ($roleFilter !== 'all') {
                $query->where('position', 'like', "%{$roleFilter}%");
            }
        }

        $members = $query->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $members->count(),
                'data' => $members,
            ]);
        }

        $business = BusinessSettingService::all();
        $businessName = $business['business_name'] ?? 'Mama Bazar';

        $seo = new SeoMetadata(
            title: "Meet Our Team — {$businessName}",
            description: "Discover the passionate leadership, software engineers, designers, and operations specialists powering {$businessName} across Bangladesh.",
            canonical: route('team'),
            ogType: 'website',
            keywords: ['mama bazar team', 'leadership', 'executives', 'engineers', 'management team', 'bangladesh e-commerce']
        );

        return view('web.team', compact('members', 'seo'));
    }
}
