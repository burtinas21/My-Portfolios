<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;

class SocialLinkController extends Controller
{
    /**
     * Display a listing of social links.
     */
    public function index()
    {
        return response()->json(
            SocialLink::orderBy('sort_order')->get()
        );
    }
}
