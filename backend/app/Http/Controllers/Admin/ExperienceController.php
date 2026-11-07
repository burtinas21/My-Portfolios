<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Models\Experience;

class ExperienceController extends Controller
{
    public function index()
    {
        return response()->json(Experience::query()->displayOrder()->get());
    }

    public function store(ExperienceRequest $request)
    {
        return response()->json(Experience::create($request->validated()), 201);
    }

    public function update(ExperienceRequest $request, Experience $experience)
    {
        $data = $request->validated();
        if (($data['is_current'] ?? false) && ! array_key_exists('end_date', $data)) {
            $data['end_date'] = null;
        }

        $experience->update($data);

        return response()->json($experience->refresh());
    }

    public function destroy(Experience $experience)
    {
        $experience->delete();

        return response()->noContent();
    }
}
