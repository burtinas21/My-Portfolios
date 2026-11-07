<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    private function rules(): array
    {
        return [
            'title'        => 'required|string|max:150',
            'summary'      => 'required|string|max:255',
            'detail'       => 'nullable|string|max:3000',
            'tag'          => 'required|string|max:50',
            'year'         => 'required|integer|min:2000|max:2100',
            'url'          => 'nullable|string|max:255',
            'github_url'   => ['nullable', 'string', 'max:255', 'regex:~^https://(?:www\\.)?github\\.com/[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9_.-]+/?$~i'],
            // Accept relative paths ("/storage/...") or legacy full URLs (for migration period)
            'image'        => 'nullable|string|max:255',
            'stack'        => 'nullable|array',
            'stack.*'      => 'string|max:40',
            'is_published' => 'boolean',
            'sort_order'   => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        // Admin sees everything, including unpublished drafts
        return response()->json(Project::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        return response()->json(Project::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate($this->rules());

        // Delete old image file when it changes (handles both relative and legacy full-URL formats)
        if ($request->filled('image') && $project->image && $project->image !== $request->image) {
            $oldStoragePath = preg_replace('#^https?://[^/]+/storage/#', '', $project->image);
            $oldStoragePath = ltrim(str_replace('/storage/', '', $oldStoragePath), '/');
            Storage::disk('public')->delete($oldStoragePath);
        }

        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        // Delete associated image file (handles both relative and legacy full-URL formats)
        if ($project->image) {
            $imagePath = preg_replace('#^https?://[^/]+/storage/#', '', $project->image);
            $imagePath = ltrim(str_replace('/storage/', '', $imagePath), '/');
            Storage::disk('public')->delete($imagePath);
        }

        $project->delete();

        return response()->noContent();
    }
}
