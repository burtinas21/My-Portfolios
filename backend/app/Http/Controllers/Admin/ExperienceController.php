<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    private function rules(): array
    {
        return [
            'role'         => 'required|string|max:120',
            'organization' => 'required|string|max:120',
            'period'       => 'required|string|max:50',
            'description'  => 'nullable|string|max:1500',
            'sort_order'   => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        return response()->json(Experience::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        return response()->json(Experience::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, Experience $experience)
    {
        $experience->update($request->validate($this->rules()));

        return response()->json($experience);
    }

    public function destroy(Experience $experience)
    {
        $experience->delete();

        return response()->noContent();
    }
}
