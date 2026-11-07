<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class ProfileController extends Controller
{
    public function show(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['profile']);
    }
}
