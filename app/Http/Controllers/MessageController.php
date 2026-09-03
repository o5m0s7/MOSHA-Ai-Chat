<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AI\AIManager;

class MessageController extends Controller
{
    public function store(Request $request, Chat $chat, AIManager $aiManager)
    {
        // Make sure this chat belongs to the logged-in user
        if ($chat->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the message
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $userId = Auth::id();

        // Get responses from AI providers
        $responses = $aiManager->send($validated['content']);

        DB::transaction(function () use ($chat, $validated, $responses, $userId) {

            // Save user's message
            $chat->messages()->create([
                'user_id' => $userId,
                'role' => 'user',
                'content' => $validated['content'],
            ]);

            // Save AI responses
            foreach ($responses as $response) {

                $chat->messages()->create([
                    'user_id'     => $userId,
                    'provider_id' => $response['provider_id'],
                    'role'        => 'assistant',
                    'content'     => $response['content'],
                ]);
            }

            // Give the chat a title using the first user message
            if ($chat->title === 'New Chat') {

                $title = trim($validated['content']);

                // Limit the title length
                if (mb_strlen($title) > 50) {
                    $title = mb_substr($title, 0, 50) . '...';
                }

                $chat->update([
                    'title' => $title,
                ]);
            }
        });

        return redirect()->route('chats.show', $chat);
    }
}
