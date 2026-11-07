<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\SitePayload;

class ProjectController extends Controller
{
    /**
     * Display a listing of published projects.
     */
    public function index(SitePayload $sitePayload)
    {
        return response()->json($sitePayload->payload()['projects']);
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
