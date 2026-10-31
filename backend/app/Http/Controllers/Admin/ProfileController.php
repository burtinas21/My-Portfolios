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
            'avatar'   => 'nullable|string|max:255',
        ]);

        $profile = Profile::first() ?? new Profile();
        
        // Delete old avatar if a new one is provided
        if ($request->filled('avatar') && $profile->avatar && $profile->avatar !== $request->avatar) {
            $oldPath = str_replace('/storage/', '', $profile->avatar);
            Storage::disk('public')->delete($oldPath);
        }
        
        $profile->fill($data)->save();

        return response()->json($profile);
    }
}
