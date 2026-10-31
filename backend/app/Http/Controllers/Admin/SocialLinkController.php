<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    private function rules(): array
    {
        return [
            'name'       => 'required|string|max:50',
            'url'        => 'required|url|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        return response()->json(SocialLink::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        return response()->json(SocialLink::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, SocialLink $socialLink)
    {
        $socialLink->update($request->validate($this->rules()));

        return response()->json($socialLink);
    }

    public function destroy(SocialLink $socialLink)
    {
        $socialLink->delete();

        return response()->noContent();
    }
}
