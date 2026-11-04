<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * images:make-relative
 *
 * Rewrites full "http(s)://anything/storage/..." image URLs stored in the
 * database to relative "/storage/..." paths so they work after deployment.
 *
 * Usage:
 *   # Preview changes without touching the database
 *   php artisan images:make-relative --dry-run
 *
 *   # Apply the conversion (make a database backup first!)
 *   php artisan images:make-relative
 */
class MakeImagesRelative extends Command
{
    protected $signature = 'images:make-relative
                            {--dry-run : Show what would change without writing anything}';

    protected $description = 'Convert absolute image URLs in profiles and projects to relative /storage/... paths';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] No database changes will be made.');
        }

        $profileUpdates = 0;
        $projectUpdates = 0;

        // ── Profiles ──────────────────────────────────────────────────────────
        Profile::whereNotNull('avatar')->each(function (Profile $profile) use ($isDryRun, &$profileUpdates) {
            $relative = $this->toRelative($profile->avatar);

            // Skip rows that are already relative or don't match the pattern
            if ($relative === null || $relative === $profile->avatar) {
                return;
            }

            $this->line("  Profile #{$profile->id}: {$profile->avatar}  →  {$relative}");
            $profileUpdates++;

            if (!$isDryRun) {
                $profile->update(['avatar' => $relative]);
            }
        });

        // ── Projects ─────────────────────────────────────────────────────────
        Project::whereNotNull('image')->each(function (Project $project) use ($isDryRun, &$projectUpdates) {
            $relative = $this->toRelative($project->image);

            if ($relative === null || $relative === $project->image) {
                return;
            }

            $this->line("  Project #{$project->id} \"{$project->title}\": {$project->image}  →  {$relative}");
            $projectUpdates++;

            if (!$isDryRun) {
                $project->update(['image' => $relative]);
            }
        });

        // ── Summary ───────────────────────────────────────────────────────────
        $this->newLine();

        if ($isDryRun) {
            $this->info("Would update {$profileUpdates} profile(s) and {$projectUpdates} project(s).");
        } else {
            $this->info("Updated {$profileUpdates} profile(s) and {$projectUpdates} project(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Convert a full "http(s)://host/storage/..." URL to "/storage/...".
     * Returns null when the value does not match the expected pattern.
     * Returns the input unchanged when it is already a relative path.
     */
    private function toRelative(string $value): ?string
    {
        // Already relative — no conversion needed
        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        // Match any http/https URL whose path begins with /storage/
        if (preg_match('#^https?://[^/]+(/storage/.+)$#i', $value, $matches)) {
            return $matches[1];
        }

        // Value is neither a matching absolute URL nor already relative — leave it alone
        return null;
    }
}

