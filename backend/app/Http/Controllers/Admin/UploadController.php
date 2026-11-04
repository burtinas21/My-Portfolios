<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        ]);

        $file = $request->file('image');

        // Generate a unique, collision-resistant filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Store under storage/app/public/uploads/ (served publicly at /storage/uploads/)
        $storagePath = $file->storeAs('uploads', $filename, 'public');

        // The relative path that must be saved in the database:
        //   "/storage/uploads/filename.ext"
        //   Works on every host — combine with VITE_BACKEND_URL on the frontend to display.
        $relativePath = '/storage/' . $storagePath;

        // The full URL is returned only as a convenience for the upload response preview.
        // The frontend should use the "path" field, not this one.
        $absoluteUrl = Storage::disk('public')->url($storagePath);

        return response()->json([
            'path' => $relativePath,  // persist this in profiles.avatar / projects.image
            'url'  => $absoluteUrl,   // display-only; never save to the database
        ], 201);
    }
}
