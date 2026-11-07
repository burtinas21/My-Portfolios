<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'name',
        'initials',
        'headline',
        'bio',
        'email',
        'location',
        'status',
        'avatar'
    ];

    protected $casts = [];
}
