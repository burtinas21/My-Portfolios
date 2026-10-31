<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
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
