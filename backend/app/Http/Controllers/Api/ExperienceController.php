<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class ExperienceController extends Controller
{
    /**
     * Display a listing of experiences.
     */
    public function index(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['experiences']);
    }
}
