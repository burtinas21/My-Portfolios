<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Resume;
use App\Services\SitePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvController extends Controller
{
    /**
     * Get public CV availability info.
     */
    public function show(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['cv'] ?? [
            'available' => false,
            'size' => null,
            'updated_at' => null,
        ]);
    }

    /**
     * Download CV file.
     * Use ?inline=1 to view in browser (does not increment counter).
     */
    public function download(Request $request)
    {
        $resume = Resume::first();

        $available = $resume
            && $resume->is_public
            && Storage::disk('private')->exists($resume->path);

        if (!$available) {
            abort(404);
        }

        // Get profile name for filename
        $profile = Profile::first();
        $profileName = $profile?->name ?? 'CV';
        $slug = Str::slug($profileName);
        $filename = $slug . '-CV.pdf';

        // Increment download count only for real downloads (not inline views)
        $isInline = $request->query('inline') === '1';
        if (!$isInline) {
            $resume->increment('download_count');
        }

        $disposition = $isInline ? 'inline' : 'attachment';

        return response()->file(
            Storage::disk('private')->path($resume->path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-cache',
            ]
        );
    }
}
