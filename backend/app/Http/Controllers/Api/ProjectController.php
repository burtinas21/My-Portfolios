<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ProjectController extends Controller
{
    /**
     * Display a listing of published projects.
     */
    public function index()
    {
        return response()->json(
            Project::where('is_published', true)->orderBy('sort_order')->get()
        );
    }

    /**
     * Display the specified project (only if published).
     */
    public function show(Project $project)
    {
        abort_unless($project->is_published, 404);
        return response()->json($project);
    }
}
