<?php

namespace App\Models\Concerns;

use App\Services\SitePayload;
use Illuminate\Database\Eloquent\Model;

trait FlushesSitePayload
{
    protected static function bootFlushesSitePayload(): void
    {
        static::saved(static function (Model $model): void {
            static::forgetSitePayloadAfterCommit($model);
        });

        static::deleted(static function (Model $model): void {
            static::forgetSitePayloadAfterCommit($model);
        });
    }

    private static function forgetSitePayloadAfterCommit(Model $model): void
    {
        SitePayload::forgetCache();

        $connection = $model->getConnection();
        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit(static fn () => SitePayload::forgetCache());
        }
    }
}
