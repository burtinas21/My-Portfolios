<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use FlushesSitePayload;

    protected $fillable = [
        'title',
        'issuer',
        'issuer_icon',
        'issue_date',
        'expiry_date',
        'credential_id',
        'credential_url',
        'image',
        'description',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
