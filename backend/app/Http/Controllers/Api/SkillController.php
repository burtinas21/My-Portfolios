<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;

class SkillController extends Controller
{
    /**
     * Display skills grouped by category.
     */
    public function index()
    {
        return response()->json(
            Skill::orderBy('sort_order')->get()->groupBy('category')
        );
    }
}
