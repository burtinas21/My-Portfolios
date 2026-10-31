<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json(Profile::first());
    }
}
