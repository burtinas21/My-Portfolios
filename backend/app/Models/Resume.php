<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'original_name',
        'path',
        'size',
        'is_public',
        'download_count',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
    ];
}
