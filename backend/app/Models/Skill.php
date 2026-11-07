<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use FlushesSitePayload;
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'level',
        'icon',
        'sort_order'
    ];

    protected $casts = [
        'level'      => 'integer',
        'sort_order' => 'integer',
    ];
}
