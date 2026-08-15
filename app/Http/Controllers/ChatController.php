<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $chats = $user->chats()->latest()->get();

        $chat = $chats->first();

        if (!$chat) {
            $chat = Chat::create([
                'user_id' => $user->id,
                'title' => 'New Chat',
            ]);

            $chats = $user->chats()->latest()->get();
        }

        $chat->load('messages.provider');

        return view('home', [
            'chat'     => $chat,
            'chats'    => $chats,
            'messages' => $chat->messages,
        ]);
    }

    public function store()
    {
        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title'   => 'New Chat',
        ]);

        return redirect()->route('chats.show', $chat);
    }

    public function show(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->load('messages.provider');

        return view('home', [
            'chat'     => $chat,
            'chats'    => Auth::user()->chats()->latest()->get(),
            'messages' => $chat->messages,
        ]);
    }

    public function destroy(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->delete();

        return redirect()->route('chats.index');
    }
}