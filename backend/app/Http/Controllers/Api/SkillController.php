<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class SkillController extends Controller
{
    /**
     * Display skills grouped by category.
     */
    public function index(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['skills']);
    }
}
