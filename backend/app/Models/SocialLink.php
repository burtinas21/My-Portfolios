<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
