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
            'name' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:255', 'regex:/^(https?:\/\/|mailto:|tel:)/i'],
            'icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function index()
    {
        return response()->json(
            SocialLink::query()
                ->select(['id', 'name', 'icon', 'url', 'sort_order', 'created_at', 'updated_at'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $this->normalizeIcon($request);

        return response()->json(SocialLink::create($request->validate($this->rules())), 201);
    }

    public function update(Request $request, SocialLink $social)
    {
        $this->normalizeIcon($request);
        $social->update($request->validate($this->rules()));

        return response()->json($social);
    }

    public function destroy(SocialLink $social)
    {
        $social->delete();

        return response()->noContent();
    }

    private function normalizeIcon(Request $request): void
    {
        $icon = $request->input('icon');
        if ($request->exists('icon') && is_string($icon) && trim($icon) === '') {
            $request->merge(['icon' => null]);
        }
    }
}
