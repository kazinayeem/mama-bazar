<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Memo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemoController extends Controller
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;

    private function selectColumns()
    {
        return [
            'memos.id',
            'memos.title',
            'memos.entity_type as entityType',
            'memos.entity_id as entityId',
            'memos.url',
            'memos.public_id as publicId',
            'memos.filename',
            'memos.mime_type as mimeType',
            'memos.size',
            'memos.folder',
            'memos.notes',
            'memos.uploaded_by_id as uploadedById',
            'users.name as uploadedByName',
            'memos.created_at as createdAt',
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = DB::table('memos')
            ->leftJoin('users', 'memos.uploaded_by_id', '=', 'users.id');

        if ($request->filled('entityType')) {
            $query->where('memos.entity_type', $request->query('entityType'));
        }
        if ($request->filled('folder')) {
            $query->where('memos.folder', $request->query('folder'));
        }
        if ($request->filled('search')) {
            $query->where('memos.title', 'like', '%' . $request->query('search') . '%');
        }

        $total = $query->count();

        $data = $query->select($this->selectColumns())
            ->orderBy('memos.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getById(int $id): JsonResponse
    {
        $row = DB::table('memos')
            ->leftJoin('users', 'memos.uploaded_by_id', '=', 'users.id')
            ->where('memos.id', $id)
            ->select($this->selectColumns())
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Memo not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'entityType' => 'required|string|max:50',
            'entityId' => 'nullable|integer',
            'url' => 'required|string',
            'publicId' => 'required|string|max:255',
            'filename' => 'required|string|max:255',
            'mimeType' => 'required|string|max:100',
            'size' => 'nullable|integer',
            'folder' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $actor = $request->user();

        $memo = Memo::create([
            'title' => $validated['title'],
            'entity_type' => $validated['entityType'],
            'entity_id' => $validated['entityId'] ?? null,
            'url' => $validated['url'],
            'public_id' => $validated['publicId'],
            'filename' => $validated['filename'],
            'mime_type' => $validated['mimeType'],
            'size' => $validated['size'] ?? 0,
            'folder' => $validated['folder'] ?? 'memos',
            'notes' => $validated['notes'] ?? null,
            'uploaded_by_id' => $actor?->id,
        ]);

        $created = $this->getById($memo->id)->getData()->data;
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function remove(int $id): JsonResponse
    {
        $memo = Memo::find($id);
        if (!$memo) {
            return response()->json(['success' => false, 'message' => 'Memo not found'], 404);
        }

        $memo->delete();
        return response()->json(['success' => true]);
    }

    public function removeMany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $validated['ids'];
        if (empty($ids)) {
            return response()->json(['success' => true, 'deleted' => 0]);
        }

        $count = Memo::whereIn('id', $ids)->delete();
        return response()->json(['success' => true, 'deleted' => $count]);
    }
}
