<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
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
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];
}
