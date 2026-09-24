<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminBackup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function listBackups(): JsonResponse
    {
        $data = BackupService::listBackups();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function createBackup(Request $request): JsonResponse
    {
        $actor = $request->user();
        $type = $request->input('type', 'manual');

        $data = BackupService::createBackup([
            'type' => $type,
            'createdById' => $actor?->id,
            'actorName' => $actor?->name ?? 'Super Admin',
            'actorEmail' => $actor?->email,
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Backup created successfully',
            'data' => $data,
        ], 201);
    }

    public function downloadBackup(int $id)
    {
        $backup = AdminBackup::find($id);
        if (!$backup) {
            return response()->json(['success' => false, 'message' => 'Backup not found'], 404);
        }

        $filepath = $backup->filepath;

        // If local file path
        if (file_exists($filepath)) {
            $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $backup->filename);
            return response()->download($filepath, $safeFilename, [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // If Cloudinary / remote URL
        if (str_starts_with($filepath, 'http://') || str_starts_with($filepath, 'https://')) {
            $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $backup->filename);
            return new StreamedResponse(function () use ($filepath) {
                $handle = fopen($filepath, 'rb');
                while (!feof($handle)) {
                    echo fread($handle, 1024 * 8);
                    flush();
                }
                fclose($handle);
            }, 200, [
                'Content-Disposition' => "attachment; filename=\"{$safeFilename}\"",
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to retrieve backup archive from storage.'], 503);
    }

    public function restoreBackup(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => "Please attach a backup archive (.zip) in the 'file' field"], 400);
        }

        $file = $request->file('file');
        if ($file->getSize() === 0) {
            return response()->json(['success' => false, 'message' => 'Uploaded file is empty'], 400);
        }

        $tempPath = $file->getRealPath();

        try {
            $result = BackupService::restoreBackup($tempPath, [
                'id' => $actor?->id,
                'name' => $actor?->name,
                'email' => $actor?->email,
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Database successfully restored. Pre-restore safety backup was preserved.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function deleteBackup(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        try {
            BackupService::deleteBackup($id, [
                'id' => $actor?->id,
                'name' => $actor?->name,
                'email' => $actor?->email,
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ]);

            return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $pin = $request->input('pin');
        if (!$pin || !is_string($pin)) {
            return response()->json(['success' => false, 'message' => 'PIN is required'], 400);
        }

        $isValid = BackupService::validatePin($pin);
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong PIN. Nice try 😄 Please check your backup PIN and try again.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Security PIN verified successfully',
        ]);
    }
}
