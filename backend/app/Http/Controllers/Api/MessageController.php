<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Store a newly created contact message.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'body'  => 'required|string|max:3000',
        ]);

        Message::create($data);
        
        return response()->json(['message' => 'Thank you! I will reply soon.'], 201);
    }
}
