<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    /**
     * Get team members.
     */
    public function getAll(Request $request): JsonResponse
    {
        $query = TeamMember::query()->ordered();

        if ($request->boolean('public_only', true)) {
            $query->active()->public();
        }

        if ($request->boolean('footer')) {
            $query->inFooter();
        }

        if ($request->filled('role')) {
            $query->where('position', 'like', '%'.$request->input('role').'%');
        }

        $members = $query->get();

        return response()->json([
            'success' => true,
            'count' => $members->count(),
            'data' => $members,
        ]);
    }

    /**
     * Get single team member by ID.
     */
    public function getById(int|string $id): JsonResponse
    {
        $member = TeamMember::find($id);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Team member not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    /**
     * Create a new team member.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'position' => 'required|string|max:191',
            'bio' => 'nullable|string|max:2000',
            'displayOrder' => 'nullable|integer',
            'display_order' => 'nullable|integer',
            'isActive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'isPublic' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'showInFooter' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
            'showEmailPublicly' => 'nullable|boolean',
            'show_email_publicly' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'imageUrl' => 'nullable|string|max:500',
            'socialLinks' => 'nullable|array',
            'social_links' => 'nullable|array',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'bio' => $validated['bio'] ?? null,
            'display_order' => $validated['display_order'] ?? $validated['displayOrder'] ?? (TeamMember::max('display_order') + 1),
            'is_active' => $request->boolean('is_active', $request->boolean('isActive', true)),
            'is_public' => $request->boolean('is_public', $request->boolean('isPublic', true)),
            'show_in_footer' => $request->boolean('show_in_footer', $request->boolean('showInFooter', false)),
            'show_email_publicly' => $request->boolean('show_email_publicly', $request->boolean('showEmailPublicly', false)),
            'social_links' => $validated['social_links'] ?? $validated['socialLinks'] ?? null,
            'image' => $validated['imageUrl'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'team');
            $data['image'] = $upload['url'];
        }

        $member = TeamMember::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Team member created successfully.',
            'data' => $member,
        ], 201);
    }

    /**
     * Update an existing team member.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $member = TeamMember::find($id);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Team member not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:191',
            'email' => 'sometimes|required|email|max:191',
            'position' => 'sometimes|required|string|max:191',
            'bio' => 'nullable|string|max:2000',
            'displayOrder' => 'nullable|integer',
            'display_order' => 'nullable|integer',
            'isActive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'isPublic' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'showInFooter' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
            'showEmailPublicly' => 'nullable|boolean',
            'show_email_publicly' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'imageUrl' => 'nullable|string|max:500',
            'removeImage' => 'nullable|boolean',
            'socialLinks' => 'nullable|array',
            'social_links' => 'nullable|array',
        ]);

        if (isset($validated['name'])) {
            $member->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $member->email = $validated['email'];
        }
        if (isset($validated['position'])) {
            $member->position = $validated['position'];
        }
        if (array_key_exists('bio', $validated)) {
            $member->bio = $validated['bio'];
        }

        if ($request->has('display_order')) {
            $member->display_order = $request->input('display_order');
        } elseif ($request->has('displayOrder')) {
            $member->display_order = $request->input('displayOrder');
        }

        if ($request->has('is_active')) {
            $member->is_active = $request->boolean('is_active');
        } elseif ($request->has('isActive')) {
            $member->is_active = $request->boolean('isActive');
        }

        if ($request->has('is_public')) {
            $member->is_public = $request->boolean('is_public');
        } elseif ($request->has('isPublic')) {
            $member->is_public = $request->boolean('isPublic');
        }

        if ($request->has('show_in_footer')) {
            $member->show_in_footer = $request->boolean('show_in_footer');
        } elseif ($request->has('showInFooter')) {
            $member->show_in_footer = $request->boolean('showInFooter');
        }

        if ($request->has('show_email_publicly')) {
            $member->show_email_publicly = $request->boolean('show_email_publicly');
        } elseif ($request->has('showEmailPublicly')) {
            $member->show_email_publicly = $request->boolean('showEmailPublicly');
        }

        if ($request->has('social_links')) {
            $member->social_links = $request->input('social_links');
        } elseif ($request->has('socialLinks')) {
            $member->social_links = $request->input('socialLinks');
        }

        if ($request->boolean('removeImage')) {
            if ($member->image) {
                MediaStorageService::deleteFile($member->image);
            }
            $member->image = null;
        } elseif ($request->hasFile('image')) {
            if ($member->image) {
                MediaStorageService::deleteFile($member->image);
            }
            $upload = MediaStorageService::uploadFile($request->file('image'), 'team');
            $member->image = $upload['url'];
        } elseif ($request->filled('imageUrl')) {
            $member->image = $request->input('imageUrl');
        }

        $member->save();

        return response()->json([
            'success' => true,
            'message' => 'Team member updated successfully.',
            'data' => $member,
        ]);
    }

    /**
     * Delete a team member.
     */
    public function remove(Request $request, int|string $id): JsonResponse
    {
        $member = TeamMember::find($id);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Team member not found.'], 404);
        }

        if ($member->image) {
            MediaStorageService::deleteFile($member->image);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team member deleted successfully.',
        ]);
    }

    /**
     * Reorder team members.
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
     * Upload an image independently.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $upload = MediaStorageService::uploadFile($request->file('image'), 'team');

        return response()->json([
            'success' => true,
            'url' => $upload['url'],
            'path' => $upload['path'],
        ]);
    }

    /**
     * Update footer team settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        if ($request->has('footerTeamEnabled')) {
            SiteSetting::updateOrCreate(
                ['key' => 'footer_team_enabled'],
                ['value' => $request->boolean('footerTeamEnabled') ? '1' : '0']
            );
        }

        if ($request->has('footerTeamTitle')) {
            SiteSetting::updateOrCreate(
                ['key' => 'footer_team_title'],
                ['value' => (string) $request->input('footerTeamTitle')]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Team settings updated successfully.',
        ]);
    }
}
