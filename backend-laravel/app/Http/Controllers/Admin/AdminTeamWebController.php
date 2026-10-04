<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminTeamWebController extends Controller
{
    /**
     * Predefined suggested team positions.
     *
     * @var list<string>
     */
    public const PREDEFINED_POSITIONS = [
        'Founder & CEO',
        'Co-Founder & CTO',
        'Chief Operating Officer (COO)',
        'Managing Director',
        'Project Manager',
        'Lead Software Engineer',
        'Senior Frontend Engineer',
        'Senior Backend Engineer',
        'UI/UX Designer',
        'Product Designer',
        'Marketing Manager',
        'Digital Marketing Specialist',
        'Supply Chain & Operations Manager',
        'Quality Assurance Specialist',
        'Customer Support Lead',
        'Finance & Accounts Lead',
    ];

    /**
     * Display the Team Management dashboard.
     */
    public function index(Request $request): View
    {
        $query = TeamMember::query()->ordered();

        // Search filter (name, email, position)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        // Footer filter
        if ($request->filled('footer') && in_array($request->input('footer'), ['yes', 'no'], true)) {
            $query->where('show_in_footer', $request->input('footer') === 'yes');
        }

        // Public visibility filter
        if ($request->filled('public') && in_array($request->input('public'), ['yes', 'no'], true)) {
            $query->where('is_public', $request->input('public') === 'yes');
        }

        $members = $query->get();

        // Metric counts
        $totalCount = TeamMember::count();
        $activeCount = TeamMember::where('is_active', true)->count();
        $footerCount = TeamMember::where('show_in_footer', true)->count();
        $publicCount = TeamMember::where('is_public', true)->count();

        // Footer team settings
        $footerTeamSetting = SiteSetting::where('key', 'footer_team_enabled')->value('value');
        $footerTeamEnabled = $footerTeamSetting === null ? true : in_array((string) $footerTeamSetting, ['1', 'true', 'yes'], true);
        $footerTeamTitle = SiteSetting::where('key', 'footer_team_title')->value('value') ?: 'Leadership & Core Team';

        return view('admin.team.index', [
            'members' => $members,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'footerCount' => $footerCount,
            'publicCount' => $publicCount,
            'footerTeamEnabled' => $footerTeamEnabled,
            'footerTeamTitle' => $footerTeamTitle,
            'predefinedPositions' => self::PREDEFINED_POSITIONS,
        ]);
    }

    /**
     * Fetch a team member for JSON/modal editing.
     */
    public function show(int $id): JsonResponse
    {
        $member = TeamMember::findOrFail($id);

        return response()->json([
            'success' => true,
            'member' => $member,
        ]);
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'position' => 'required|string|max:191',
            'bio' => 'nullable|string|max:2000',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
            'show_email_publicly' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'social_linkedin' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|url|max:255',
            'social_github' => 'nullable|url|max:255',
            'social_website' => 'nullable|url|max:255',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'bio' => $validated['bio'] ?? null,
            'display_order' => $validated['display_order'] ?? (TeamMember::max('display_order') + 1),
            'is_active' => $request->boolean('is_active', true),
            'is_public' => $request->boolean('is_public', true),
            'show_in_footer' => $request->boolean('show_in_footer', false),
            'show_email_publicly' => $request->boolean('show_email_publicly', false),
        ];

        // Process social links
        $socials = array_filter([
            'linkedin' => $validated['social_linkedin'] ?? null,
            'twitter' => $validated['social_twitter'] ?? null,
            'github' => $validated['social_github'] ?? null,
            'website' => $validated['social_website'] ?? null,
        ]);
        $data['social_links'] = $socials ?: null;

        // Process Image Upload
        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'team');
            $data['image'] = $upload['url'];
        }

        $member = TeamMember::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Team member created successfully.',
                'member' => $member,
            ]);
        }

        return redirect()->route('admin.team.index')->with('success', "Team member '{$member->name}' added successfully.");
    }

    /**
     * Update an existing team member.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $member = TeamMember::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'position' => 'required|string|max:191',
            'bio' => 'nullable|string|max:2000',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
            'show_email_publicly' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_image' => 'nullable|boolean',
            'social_linkedin' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|url|max:255',
            'social_github' => 'nullable|url|max:255',
            'social_website' => 'nullable|url|max:255',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'bio' => $validated['bio'] ?? null,
            'display_order' => $validated['display_order'] ?? $member->display_order,
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'show_in_footer' => $request->boolean('show_in_footer'),
            'show_email_publicly' => $request->boolean('show_email_publicly'),
        ];

        // Process social links
        $socials = array_filter([
            'linkedin' => $validated['social_linkedin'] ?? null,
            'twitter' => $validated['social_twitter'] ?? null,
            'github' => $validated['social_github'] ?? null,
            'website' => $validated['social_website'] ?? null,
        ]);
        $data['social_links'] = $socials ?: null;

        // Image replacement or removal
        if ($request->boolean('remove_image')) {
            if ($member->image) {
                MediaStorageService::deleteFile($member->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($member->image) {
                MediaStorageService::deleteFile($member->image);
            }
            $upload = MediaStorageService::uploadFile($request->file('image'), 'team');
            $data['image'] = $upload['url'];
        }

        $member->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully.',
                'member' => $member,
            ]);
        }

        return redirect()->route('admin.team.index')->with('success', "Team member '{$member->name}' updated successfully.");
    }

    /**
     * Delete a team member.
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $member = TeamMember::findOrFail($id);
        $name = $member->name;

        if ($member->image) {
            MediaStorageService::deleteFile($member->image);
        }

        $member->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Team member '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('admin.team.index')->with('success', "Team member '{$name}' deleted successfully.");
    }

    /**
     * Reorder team members via Drag and Drop.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:team_members,id',
        ]);

        $orderIds = $request->input('order');

        DB::transaction(function () use ($orderIds) {
            foreach ($orderIds as $index => $id) {
                TeamMember::where('id', $id)->update(['display_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Team members reordered successfully.',
        ]);
    }

    /**
     * Quick toggle for active status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $member = TeamMember::findOrFail($id);
        $member->is_active = ! $member->is_active;
        $member->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $member->is_active,
                'message' => "'{$member->name}' is now " . ($member->is_active ? 'active' : 'inactive') . '.',
            ]);
        }

        return back()->with('success', "'{$member->name}' status updated.");
    }

    /**
     * Quick toggle for footer appearance.
     */
    public function toggleFooter(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $member = TeamMember::findOrFail($id);
        $member->show_in_footer = ! $member->show_in_footer;
        $member->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'show_in_footer' => $member->show_in_footer,
                'message' => "'{$member->name}' " . ($member->show_in_footer ? 'added to' : 'removed from') . ' website footer.',
            ]);
        }

        return back()->with('success', "'{$member->name}' footer visibility updated.");
    }

    /**
     * Quick toggle for public visibility.
     */
    public function togglePublic(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $member = TeamMember::findOrFail($id);
        $member->is_public = ! $member->is_public;
        $member->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_public' => $member->is_public,
                'message' => "'{$member->name}' public visibility updated.",
            ]);
        }

        return back()->with('success', "'{$member->name}' public visibility updated.");
    }

    /**
     * Update global Team section settings (e.g. footer section toggle & title).
     */
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'footer_team_enabled' => 'nullable|boolean',
            'footer_team_title' => 'nullable|string|max:191',
        ]);

        $enabled = $request->boolean('footer_team_enabled') ? '1' : '0';
        SiteSetting::updateOrCreate(['key' => 'footer_team_enabled'], ['value' => $enabled]);

        if ($request->filled('footer_team_title')) {
            SiteSetting::updateOrCreate(
                ['key' => 'footer_team_title'],
                ['value' => trim($validated['footer_team_title'])]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Footer team settings updated.',
            ]);
        }

        return back()->with('success', 'Footer team settings updated successfully.');
    }
}
