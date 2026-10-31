<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;

class MessageController extends Controller
{
    public function index()
    {
        return response()->json(Message::latest()->get());
    }

    public function markAsRead(Message $message)
    {
        $message->update(['read_at' => now()]);

        return response()->json($message);
    }

    public function destroy(Message $message)
    {
        $message->delete();

        return response()->noContent();
    }
}
