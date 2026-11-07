<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatUsage extends Model
{
    protected $table = 'chat_usage';

    // Disable updated_at since we only track creation
    const UPDATED_AT = null;

    protected $fillable = [
        'input_tokens',
        'output_tokens',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];
}
