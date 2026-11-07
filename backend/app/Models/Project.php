<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'detail',
        'tag',
        'year',
        'url',
        'image',
        'stack',
        'is_published',
        'sort_order'
    ];

    protected $casts = [
        'stack' => 'array',
        'is_published' => 'boolean',
        'sort_order' => 'integer'
    ];
}
