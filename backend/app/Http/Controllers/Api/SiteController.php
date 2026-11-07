<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitePayload;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function show(Request $request, SitePayload $sitePayload)
    {
        $response = response()
            ->json($sitePayload->payload())
            ->header('Cache-Control', 'no-cache');

        $response->setEtag(md5((string) $response->getContent()), true);

        $response->isNotModified($request);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
