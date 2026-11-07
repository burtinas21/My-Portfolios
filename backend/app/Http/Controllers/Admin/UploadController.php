<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AvatarImageOptimizer;
use App\Services\SitePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Upload an image file.
     * POST /api/admin/uploads
     *
     * Returns:
     *   "path" — the storage-relative path to persist in the DB, e.g. "/storage/uploads/file.png"
     *   "url"  — the full absolute URL for immediate display (convenience only; do NOT save this)
     *
     * The frontend must save only "path". Use the assetUrl() helper to build display URLs.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048', // max 2 MB
            'optimize_avatar' => ['sometimes', 'boolean'],
        ]);

        $file = $request->file('image');
        $optimizeAvatar = $request->boolean('optimize_avatar');

        if ($optimizeAvatar && ! function_exists('imagewebp')) {
            return response()->json([
                'message' => 'Avatar optimization is unavailable on this server.',
            ], 503);
        }

        // Generate a unique, collision-resistant filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Store under storage/app/public/uploads/ (served publicly at /storage/uploads/)
        $storagePath = $file->storeAs('uploads', $filename, 'public');

        // The relative path that must be saved in the database:
        //   "/storage/uploads/filename.ext"
        //   Works on every host — combine with VITE_BACKEND_URL on the frontend to display.
        $relativePath = '/storage/' . $storagePath;
        $optimizedPath = null;

        if ($optimizeAvatar) {
            $sourcePath = $file->getRealPath();
            if ($sourcePath === false) {
                return response()->json([
                    'message' => 'The uploaded avatar could not be read for optimization.',
                ], 422);
            }

            $optimizedPath = app(AvatarImageOptimizer::class)->optimize($sourcePath, $storagePath);
        }

        // The full URL is returned only as a convenience for the upload response preview.
        // Build the absolute URL using the app URL from config.
        $absoluteUrl = url($relativePath);
        SitePayload::forgetCache();

        return response()->json([
            'path' => $relativePath,  // persist this in profiles.avatar / projects.image
            'url'  => $absoluteUrl,   // display-only; never save to the database
            'optimized_path' => $optimizedPath ? '/storage/' . $optimizedPath : null,
        ], 201);
    }
}
