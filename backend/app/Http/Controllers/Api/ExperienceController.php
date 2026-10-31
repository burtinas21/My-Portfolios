<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;

class ExperienceController extends Controller
{
    /**
     * Display a listing of experiences.
     */
    public function index()
    {
        return response()->json(
            Experience::orderBy('sort_order')->get()
        );
    }
}
