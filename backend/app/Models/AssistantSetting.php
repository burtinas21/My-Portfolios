<?php

namespace App\Models;

use App\Models\Concerns\FlushesSitePayload;
use Illuminate\Database\Eloquent\Model;

class AssistantSetting extends Model
{
    use FlushesSitePayload;

    protected $fillable = [
        'enabled',
        'greeting',
        'extra_knowledge',
        'suggested_questions',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'suggested_questions' => 'array',
    ];
}
