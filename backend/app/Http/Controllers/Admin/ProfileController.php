<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json(Profile::first());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'initials' => 'required|string|max:4',
            'headline' => 'required|string|max:150',
            'bio'      => 'required|string|max:2000',
            'email'    => 'required|email',
            'location' => 'nullable|string|max:100',
            'status'   => 'nullable|string|max:100',
            // Accept relative paths ("/storage/...") or any string up to 255 chars.
            // Full http(s) URLs are also accepted for backwards compat during migration.
            'avatar'   => 'nullable|string|max:255',
        ]);

        $profile = Profile::first() ?? new Profile();

        // Delete old avatar file when it changes (works for both "/storage/..." and legacy full URLs)
        if ($request->filled('avatar') && $profile->avatar && $profile->avatar !== $request->avatar) {
            // Derive the storage-relative path regardless of format
            $oldStoragePath = preg_replace('#^https?://[^/]+/storage/#', '', $profile->avatar);
            $oldStoragePath = ltrim(str_replace('/storage/', '', $oldStoragePath), '/');
            Storage::disk('public')->delete($oldStoragePath);
        }

        $profile->fill($data)->save();

        return response()->json($profile);
    }
}
