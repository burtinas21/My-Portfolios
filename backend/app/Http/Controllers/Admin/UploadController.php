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
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048', // max 2MB
        ]);

        $file = $request->file('image');
        
        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store in public disk under uploads directory
        $path = $file->storeAs('uploads', $filename, 'public');
        
        // Return the public URL
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }
}
