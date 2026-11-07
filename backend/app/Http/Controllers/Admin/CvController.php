<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Services\SitePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvController extends Controller
{
    /**
     * Get current CV info or return { exists: false }.
     */
    public function show()
    {
        $resume = Resume::first();

        if (!$resume) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'original_name' => $resume->original_name,
            'size' => $resume->size,
            'is_public' => $resume->is_public,
            'download_count' => $resume->download_count,
            'updated_at' => $resume->updated_at->toISOString(),
        ]);
    }

    /**
     * Upload or replace CV. Validates PDF, stores on private disk.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|mimetypes:application/pdf|max:5120', // max 5MB
        ]);

        $file = $request->file('file');

        // Verify PDF header bytes
        $header = file_get_contents($file->getRealPath(), false, null, 0, 4);
        if ($header !== '%PDF') {
            return response()->json([
                'message' => 'The file must be a valid PDF.',
                'errors' => ['file' => ['The file is not a valid PDF document.']],
            ], 422);
        }

        $oldResume = Resume::first();
        $oldPath = $oldResume?->path;

        // Generate unique filename
        $filename = Str::uuid() . '.pdf';
        $storedPath = $file->storeAs('cv', $filename, 'private');

        try {
            DB::beginTransaction();

            // Create or update the resume record
            $resume = Resume::updateOrCreate(
                ['id' => $oldResume?->id ?? null],
                [
                    'original_name' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 150, ''),
                    'path' => $storedPath,
                    'size' => $file->getSize(),
                    'is_public' => $oldResume?->is_public ?? true,
                    'download_count' => 0, // Reset on replace
                ]
            );

            DB::commit();
            SitePayload::forgetCache();

            // Delete old file after successful transaction
            if ($oldPath && $oldPath !== $storedPath && Storage::disk('private')->exists($oldPath)) {
                Storage::disk('private')->delete($oldPath);
            }

            return response()->json([
                'exists' => true,
                'original_name' => $resume->original_name,
                'size' => $resume->size,
                'is_public' => $resume->is_public,
                'download_count' => $resume->download_count,
                'updated_at' => $resume->updated_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete the new file if transaction failed
            if (Storage::disk('private')->exists($storedPath)) {
                Storage::disk('private')->delete($storedPath);
            }

            return response()->json([
                'message' => 'Failed to save CV. Please try again.',
                'errors' => ['file' => ['Failed to save CV.']],
            ], 500);
        }
    }

    /**
     * Toggle is_public flag.
     */
    public function togglePublic(Request $request)
    {
        $request->validate([
            'is_public' => 'required|boolean',
        ]);

        $resume = Resume::first();

        if (!$resume) {
            return response()->json(['message' => 'CV not found.'], 404);
        }

        $resume->update(['is_public' => $request->is_public]);

        return response()->json([
            'exists' => true,
            'original_name' => $resume->original_name,
            'size' => $resume->size,
            'is_public' => $resume->is_public,
            'download_count' => $resume->download_count,
            'updated_at' => $resume->updated_at->toISOString(),
        ]);
    }

    /**
     * Delete CV file and record.
     */
    public function destroy()
    {
        $resume = Resume::first();

        if (!$resume) {
            return response()->json(['message' => 'CV not found.'], 404);
        }

        if ($resume->path && Storage::disk('private')->exists($resume->path)) {
            Storage::disk('private')->delete($resume->path);
        }

        $resume->delete();

        return response()->noContent();
    }

    /**
     * Stream CV file for admin preview (inline).
     */
    public function preview()
    {
        $resume = Resume::first();

        if (!$resume || !Storage::disk('private')->exists($resume->path)) {
            return response()->json(['message' => 'CV not found.'], 404);
        }

        return response()->file(
            Storage::disk('private')->path($resume->path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $resume->original_name . '.pdf"',
            ]
        );
    }
}
