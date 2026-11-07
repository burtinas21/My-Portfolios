<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;

class CertificationController extends Controller
{
    public function index(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['certifications'] ?? []);
    }
}
