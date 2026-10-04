<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamWebController extends Controller
{
    /**
     * Display the public Team showcase page.
     */
    public function index(Request $request): View|JsonResponse
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

        $seo = SeoService::getForTeam();

        return view('web.team', compact('members', 'seo'));
    }
}
