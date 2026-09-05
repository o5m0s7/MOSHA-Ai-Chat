<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AI\AIManager;
use App\Services\MarkdownRenderer;

class MessageController extends Controller
{
    public function store(
        Request $request,
        Chat $chat,
        AIManager $aiManager,
        MarkdownRenderer $markdownRenderer
    ) {
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

        $newMessages = [];

        DB::transaction(function () use (
            $chat,
            $validated,
            $responses,
            $userId,
            &$newMessages
        ) {

            // Save user's message
            $userMessage = $chat->messages()->create([
                'user_id' => $userId,
                'role' => 'user',
                'content' => $validated['content'],
            ]);

            $newMessages[] = $userMessage;

            // Save AI responses
            foreach ($responses as $response) {

                $aiMessage = $chat->messages()->create([
                    'user_id'     => $userId,
                    'provider_id' => $response['provider_id'],
                    'role'        => 'assistant',
                    'content'     => $response['content'],
                ]);

                $newMessages[] = $aiMessage;
            }

            // Set chat title from first user message
            if ($chat->title === 'New Chat') {

                $title = trim($validated['content']);

                if (mb_strlen($title) > 50) {
                    $title = mb_substr($title, 0, 50) . '...';
                }

                $chat->update([
                    'title' => $title,
                ]);
            }
        });

        // Return JSON for JavaScript/fetch requests
        if ($request->expectsJson()) {

            $messages = collect($newMessages)
                ->filter(function ($message) {
                    return $message->role === 'assistant';
                })
                ->map(function ($message) use ($markdownRenderer) {

                    // Load provider for this message
                    $message->loadMissing('provider');

                    return [
                        'id' => $message->id,
                        'role' => $message->role,
                        'provider' => $message->provider?->name ?? 'AI',
                        'content' => $message->content,
                        'html' => $markdownRenderer->render($message->content),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,

                'chat' => [
                    'id' => $chat->id,
                    'title' => $chat->title,
                ],

                'messages' => $messages,
            ]);
        }

        // Normal browser form submission
        return redirect()->route('chats.show', $chat);
    }
}
