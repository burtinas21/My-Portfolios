<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    private function rules(): array
    {
        return [
            'category'   => 'required|string|max:50',
            'name'       => 'required|string|max:80',
            'level'      => 'required|integer|min:0|max:100',
            'icon'       => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/'],
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        return response()->json(Skill::orderBy('category')->orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        return response()->json(Skill::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, Skill $skill)
    {
        $skill->update($request->validate($this->rules()));

        return response()->json($skill);
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();

        return response()->noContent();
    }
}
