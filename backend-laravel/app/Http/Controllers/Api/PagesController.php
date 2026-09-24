<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PolicyPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function getBySlug(string $slug): JsonResponse
    {
        $page = PolicyPage::where('slug', $slug)->first();
        if (!$page || $page->status !== 'published') {
            return response()->json(['success' => false, 'message' => 'Page not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'status' => $page->status,
                'lastUpdated' => $page->last_updated,
                'createdAt' => $page->created_at,
            ],
        ]);
    }

    public function getAll(): JsonResponse
    {
        $pages = PolicyPage::select(['id', 'slug', 'title', 'status', 'last_updated as lastUpdated', 'created_at as createdAt'])
            ->orderBy('slug')
            ->get();

        return response()->json(['success' => true, 'data' => $pages]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100',
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'status' => 'required|in:published,draft',
        ]);

        $exists = PolicyPage::where('slug', $validated['slug'])->first();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A page with this slug already exists'], 409);
        }

        $userId = $request->user() ? $request->user()->id : null;
        $now = time();

        $page = PolicyPage::create([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'],
            'last_updated' => $now,
            'updated_by' => $userId,
        ]);

        return response()->json(['success' => true, 'data' => ['id' => $page->id]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = PolicyPage::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Policy page not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:published,draft',
        ]);

        $userId = $request->user() ? $request->user()->id : null;
        $now = time();

        $updateData = ['last_updated' => $now, 'updated_by' => $userId];
        if ($request->has('title')) $updateData['title'] = $validated['title'];
        if ($request->has('content')) $updateData['content'] = $validated['content'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $page->update($updateData);

        return response()->json(['success' => true, 'data' => ['id' => $id, 'lastUpdated' => $now]]);
    }

    public function remove(int $id): JsonResponse
    {
        PolicyPage::destroy($id);
        return response()->json(['success' => true, 'data' => ['success' => true]]);
    }

    public function submitContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'message' => 'required|string',
        ]);

        $msg = ContactMessage::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'],
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'data' => ['success' => true, 'id' => $msg->id],
            'message' => 'আপনার বার্তাটি পেয়েছি, শীঘ্রই যোগাযোগ করব।',
        ], 201);
    }

    public function getContactMessages(): JsonResponse
    {
        $messages = ContactMessage::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function updateContactStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,read,archived',
        ]);

        ContactMessage::where('id', $id)->update(['status' => $validated['status']]);
        return response()->json(['success' => true, 'data' => ['success' => true]]);
    }
}
