<?php

namespace App\Services;

use App\Models\AssistantSetting;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SitePayload
{
    public const CACHE_KEY = 'site.payload';

    /**
     * Get the public bootstrap payload.
     */
    public function payload(): array
    {
        $cachedPayload = Cache::get(self::CACHE_KEY);
        if ($this->hasValidPayloadShape($cachedPayload)) {
            return $cachedPayload;
        }

        if ($cachedPayload !== null) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $payload = [
                'profile' => $this->profile(),
                'socials' => $this->socials(),
                'projects' => $this->projects(),
                'skills' => $this->skills(),
                'experiences' => $this->experiences(),
            ];

            if (class_exists(Certification::class)) {
                $payload['certifications'] = $this->certifications();
            }

            if (class_exists(Resume::class)) {
                $payload['cv'] = $this->cv();
            }

            if (class_exists(AssistantSetting::class)) {
                $payload['assistant'] = $this->assistant();
            }

            return $payload;
        });
    }

    private function hasValidPayloadShape(mixed $payload): bool
    {
        if (! is_array($payload)
            || ! array_key_exists('profile', $payload)
            || (! is_array($payload['profile']) && $payload['profile'] !== null)
            || ! is_array($payload['socials'] ?? null)
            || ! is_array($payload['projects'] ?? null)
            || ! is_array($payload['skills'] ?? null)
            || ! $this->hasExperienceShape($payload['experiences'] ?? null)) {
            return false;
        }

        if (is_array($payload['profile'])
            && (! array_key_exists('avatar_optimized', $payload['profile'])
                || ! array_key_exists('avatar_optimized_srcset', $payload['profile']))) {
            return false;
        }

        if (class_exists(Certification::class) && ! is_array($payload['certifications'] ?? null)) {
            return false;
        }

        if (class_exists(Resume::class) && ! is_array($payload['cv'] ?? null)) {
            return false;
        }

        return ! class_exists(AssistantSetting::class) || is_array($payload['assistant'] ?? null);
    }

    private function hasExperienceShape(mixed $experiences): bool
    {
        if (! is_array($experiences)) {
            return false;
        }

        foreach ($experiences as $experience) {
            if (! is_array($experience)
                || ! array_key_exists('start_date', $experience)
                || ! array_key_exists('end_date', $experience)
                || ! array_key_exists('is_current', $experience)
                || ! array_key_exists('employment_type', $experience)
                || ! array_key_exists('work_mode', $experience)
                || ! array_key_exists('location', $experience)
                || ! array_key_exists('highlights', $experience)
                || ! array_key_exists('technologies', $experience)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Invalidate the cached public payload.
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function profile(): ?array
    {
        $profile = Profile::query()
            ->select([
                'id', 'name', 'initials', 'headline', 'bio', 'email',
                'location', 'status', 'avatar', 'created_at', 'updated_at',
            ])
            ->first();

        if (! $profile) {
            return null;
        }

        $data = $profile->toArray();
        $optimizedAvatar = $this->optimizedAvatarPaths($profile->avatar);
        $data['avatar_optimized'] = $optimizedAvatar['path'];
        $data['avatar_optimized_srcset'] = $optimizedAvatar['srcset'];

        return $data;
    }

    private function optimizedAvatarPaths(?string $avatar): array
    {
        if (! $avatar) {
            return ['path' => null, 'srcset' => null];
        }

        $parsedUrl = parse_url($avatar);
        $path = $parsedUrl['path'] ?? null;
        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return ['path' => null, 'srcset' => null];
        }

        $storagePath = substr($path, strlen('/storage/'));
        $pathInfo = pathinfo($storagePath);
        if (empty($pathInfo['filename'])) {
            return ['path' => null, 'srcset' => null];
        }

        $basePath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '')
            . $pathInfo['filename'];
        $optimizedPath = $basePath . '.optimized.webp';
        $smallPath = $basePath . '.optimized-480.webp';

        if (! Storage::disk('public')->exists($optimizedPath)) {
            return ['path' => null, 'srcset' => null];
        }

        $optimizedUrl = $this->storageUrl($optimizedPath, $parsedUrl);
        $srcset = null;
        if (Storage::disk('public')->exists($smallPath)) {
            $srcset = $this->storageUrl($smallPath, $parsedUrl) . ' 480w, ' . $optimizedUrl . ' 800w';
        }

        return ['path' => $optimizedUrl, 'srcset' => $srcset];
    }

    private function storageUrl(string $storagePath, array $originalUrl): string
    {
        $optimizedUrl = '/storage/' . $storagePath;
        if (! isset($originalUrl['scheme'], $originalUrl['host'])) {
            return $optimizedUrl;
        }

        $host = $originalUrl['host'];
        if (isset($originalUrl['port'])) {
            $host .= ':' . $originalUrl['port'];
        }

        return $originalUrl['scheme'] . '://' . $host . $optimizedUrl;
    }

    private function socials(): array
    {
        return SocialLink::query()
            ->select(['id', 'name', 'icon', 'url', 'sort_order', 'created_at', 'updated_at'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    private function projects(): array
    {
        return Project::query()
            ->select([
                'id', 'title', 'summary', 'detail', 'tag', 'year', 'url', 'github_url',
                'image', 'stack', 'is_published', 'sort_order', 'created_at', 'updated_at',
            ])
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    private function skills(): array
    {
        return Skill::query()
            ->select(['id', 'category', 'name', 'level', 'icon', 'sort_order', 'created_at', 'updated_at'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category')
            ->map(fn ($skills): array => $skills->values()->toArray())
            ->all();
    }

    private function experiences(): array
    {
        return Experience::query()
            ->select([
                'id', 'role', 'organization', 'period', 'description',
                'start_date', 'end_date', 'is_current', 'employment_type',
                'work_mode', 'location', 'highlights', 'technologies',
                'sort_order', 'created_at', 'updated_at',
            ])
            ->displayOrder()
            ->get()
            ->toArray();
    }

    private function certifications(): array
    {
        return Certification::query()
            ->select([
                'id', 'title', 'issuer', 'issuer_icon', 'issue_date', 'expiry_date',
                'credential_id', 'credential_url', 'image', 'description',
            ])
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('issue_date')
            ->get()
            ->map(fn (Certification $certification): array => [
                'id' => $certification->id,
                'title' => $certification->title,
                'issuer' => $certification->issuer,
                'issuer_icon' => $certification->issuer_icon,
                'issue_date' => $certification->issue_date->toDateString(),
                'expiry_date' => $certification->expiry_date?->toDateString(),
                'credential_id' => $certification->credential_id,
                'credential_url' => $certification->credential_url,
                'image' => $certification->image,
                'description' => $certification->description,
                'is_expired' => $certification->expiry_date !== null
                    && $certification->expiry_date->lt(today()),
            ])
            ->values()
            ->all();
    }

    private function cv(): array
    {
        $resume = Resume::query()
            ->select(['path', 'size', 'is_public', 'updated_at'])
            ->first();

        $available = $resume
            && $resume->is_public
            && Storage::disk('private')->exists($resume->path);

        if (! $available) {
            return [
                'available' => false,
                'size' => null,
                'updated_at' => null,
            ];
        }

        return [
            'available' => true,
            'size' => $resume->size,
            'updated_at' => $resume->updated_at->toISOString(),
        ];
    }

    private function assistant(): array
    {
        $settings = AssistantSetting::query()
            ->select(['enabled', 'greeting', 'suggested_questions'])
            ->first();
        $defaultGreeting = 'Hi! I\'m an AI assistant. Ask me anything about my background, skills, or projects.';

        if (! $settings) {
            return [
                'enabled' => false,
                'greeting' => $defaultGreeting,
                'suggested_questions' => [],
            ];
        }

        return [
            'enabled' => $settings->enabled && ! empty(config('services.anthropic.key')),
            'greeting' => $settings->greeting,
            'suggested_questions' => $settings->suggested_questions ?? [],
        ];
    }
}
