<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Http\Requests\Admin\ReorderCertificationsRequest;
use App\Models\Certification;
use App\Models\Profile;
use App\Models\Project;
use App\Services\SitePayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificationController extends Controller
{
    public function index()
    {
        return response()->json(
            Certification::orderBy('sort_order')->orderByDesc('issue_date')->get()
        );
    }

    public function store(CertificationRequest $request)
    {
        return response()->json(Certification::create($request->validated()), 201);
    }

    public function update(CertificationRequest $request, Certification $certification)
    {
        $data = $request->validated();
        $oldImage = $certification->image;

        $certification->update($data);

        if (array_key_exists('image', $data) && $oldImage !== $certification->image) {
            $this->deleteImageIfUnused($oldImage, $certification->id);
        }

        return response()->json($certification->refresh());
    }

    public function destroy(Certification $certification)
    {
        $oldImage = $certification->image;
        $certification->delete();
        $this->deleteImageIfUnused($oldImage);

        return response()->noContent();
    }

    public function reorder(ReorderCertificationsRequest $request)
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('certifications') as $item) {
                Certification::whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        SitePayload::forgetCache();

        return response()->json(
            Certification::orderBy('sort_order')->orderByDesc('issue_date')->get()
        );
    }

    private function deleteImageIfUnused(?string $image, ?int $exceptCertificationId = null): void
    {
        $imagePath = $this->storageImagePath($image);
        if ($imagePath === null || ! str_starts_with($imagePath, '/storage/uploads/')) {
            return;
        }

        $certificationImages = Certification::query()
            ->when($exceptCertificationId !== null, fn ($query) => $query->whereKeyNot($exceptCertificationId))
            ->whereNotNull('image')
            ->pluck('image');

        $otherImages = $certificationImages
            ->concat(Project::whereNotNull('image')->pluck('image'))
            ->concat(Profile::whereNotNull('avatar')->pluck('avatar'));

        foreach ($otherImages as $otherImage) {
            if ($this->storageImagePath($otherImage) === $imagePath) {
                return;
            }
        }

        Storage::disk('public')->delete(substr($imagePath, strlen('/storage/')));
    }

    private function storageImagePath(?string $image): ?string
    {
        if (! is_string($image) || $image === '') {
            return null;
        }

        $path = parse_url($image, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/storage/') ? $path : null;
    }
}
