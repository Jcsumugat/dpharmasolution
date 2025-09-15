<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ChatViewController extends Controller
{
    public function customerChat(): View
    {
        return view('client.messages');
    }

    public function chatSupport(): View
    {
        return view('chat.chat');
    }
}
