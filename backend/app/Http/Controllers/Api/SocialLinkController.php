<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class SocialLinkController extends Controller
{
    /**
     * Display a listing of social links.
     */
    public function index(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['socials']);
    }
}
