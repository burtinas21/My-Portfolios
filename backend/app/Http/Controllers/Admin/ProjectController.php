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
        
        // Delete old image if a new one is provided
        if ($request->filled('image') && $project->image && $project->image !== $request->image) {
            $oldPath = str_replace('/storage/', '', $project->image);
            Storage::disk('public')->delete($oldPath);
        }
        
        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        // Delete associated image if exists
        if ($project->image) {
            $imagePath = str_replace('/storage/', '', $project->image);
            Storage::disk('public')->delete($imagePath);
        }
        
        $project->delete();

        return response()->noContent();
    }
}
