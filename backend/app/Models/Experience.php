<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'role',
        'organization',
        'period',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'employment_type',
        'work_mode',
        'location',
        'highlights',
        'technologies',
        'sort_order',
    ];

    protected $attributes = [
        'is_current' => false,
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_current' => 'boolean',
        'highlights' => 'array',
        'technologies' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopeDisplayOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_current')
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('start_date')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }
}
