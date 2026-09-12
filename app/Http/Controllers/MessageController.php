<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
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
        if ($chat->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $userId = Auth::id();

        /*
        |--------------------------------------------------------------------------
        | 1. Save the user's message first
        |--------------------------------------------------------------------------
        */

        $userMessage = $chat->messages()->create([
            'user_id' => $userId,
            'role' => 'user',
            'status' => 'completed',
            'content' => $validated['content'],
            'provider_id' => null,
            'parent_message_id' => null,
            'error' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Build conversation history
        |--------------------------------------------------------------------------
        */

        $previousMessages = $chat->messages()
            ->orderBy('id')
            ->get([
                'role',
                'content',
                'provider_id',
            ]);

        $conversation = $previousMessages
            ->map(function ($message) {
                return [
                    'role' => $message->role === 'assistant'
                        ? 'assistant'
                        : 'user',

                    'content' => $message->content,

                    'provider_id' => $message->provider_id,
                ];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | 3. Ask all AI providers
        |--------------------------------------------------------------------------
        */

        $responses = $aiManager->send($conversation);

        /*
        |--------------------------------------------------------------------------
        | 4. Save provider responses
        |--------------------------------------------------------------------------
        */

        $newMessages = [];

        DB::transaction(function () use (
            $chat,
            $responses,
            $userId,
            $userMessage,
            $validated,
            &$newMessages
        ) {
            $newMessages[] = $userMessage;

            foreach ($responses as $response) {
                $aiMessage = $chat->messages()->create([
                    'user_id' => $userId,
                    'provider_id' => $response['provider_id'],
                    'parent_message_id' => $userMessage->id,

                    'role' => 'assistant',

                    'status' => $response['success']
                        ? 'completed'
                        : 'failed',

                    'content' => $response['content'] ?? '',

                    'error' => $response['error'] ?? null,
                ]);

                $newMessages[] = $aiMessage;
            }

            /*
            |--------------------------------------------------------------------------
            | First user message becomes chat title
            |--------------------------------------------------------------------------
            */

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

        /*
        |--------------------------------------------------------------------------
        | 5. JSON response for AJAX requests
        |--------------------------------------------------------------------------
        */

        if ($request->expectsJson()) {
            $messages = collect($newMessages)
                ->filter(fn ($message) => $message->role === 'assistant')
                ->map(function ($message) use ($markdownRenderer) {
                    $message->loadMissing('provider');

                    return [
                        'id' => $message->id,

                        'role' => $message->role,

                        'provider_id' => $message->provider_id,

                        'provider' => $message->provider?->name ?? 'AI',

                        'status' => $message->status,

                        'content' => $message->content,

                        'error' => $message->error,

                        'html' => $message->status === 'completed'
                            ? $markdownRenderer->render($message->content)
                            : null,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,

                'chat' => [
                    'id' => $chat->id,
                    'title' => $chat->title,
                ],

                'user_message_id' => $userMessage->id,

                'messages' => $messages,
            ]);
        }

        return redirect()->route('chats.show', $chat);
    }

    public function retry(
    Request $request,
    Message $message,
    AIManager $aiManager,
    MarkdownRenderer $markdownRenderer
    ) {
        /*
        |--------------------------------------------------------------------------
        | 1. Make sure this is an AI message
        |--------------------------------------------------------------------------
        */

        if ($message->role !== 'assistant') {
            abort(422, 'Only AI messages can be retried.');
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Make sure the message belongs to the logged-in user
        |--------------------------------------------------------------------------
        */

        if ($message->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Load the user message that caused this AI response
        |--------------------------------------------------------------------------
        */

        $message->load('parent');

        $parentMessage = $message->parent;

        if (!$parentMessage) {
            abort(422, 'Parent user message not found.');
        }
        
        /*
        |--------------------------------------------------------------------------
        | 4. Build conversation history
        |--------------------------------------------------------------------------
        */

        $conversation = $message->chat
            ->messages()
            ->where('id', '<', $parentMessage->id)
            ->orderBy('id')
            ->get([
                'role',
                'content',
                'provider_id',
            ])
            ->map(function ($item) {
                return [
                    'role' => $item->role === 'assistant'
                        ? 'assistant'
                        : 'user',

                    'content' => $item->content,

                    'provider_id' => $item->provider_id,
                ];
            })
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Add the original user message as the final message
        |--------------------------------------------------------------------------
        */

        $conversation[] = [
            'role' => 'user',

            'content' => $parentMessage->content,

            'provider_id' => null,
        ];

        /*
        |--------------------------------------------------------------------------
        | 5. Retry ONLY this provider
        |--------------------------------------------------------------------------
        */

        $response = $aiManager->sendToProvider(
            (int) $message->provider_id,
            $conversation
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Update the existing AI message
        |--------------------------------------------------------------------------
        */

        if ($response['success']) {
            $message->update([
                'status' => 'completed',
                'content' => $response['content'],
                'error' => null,
            ]);
        } else {
            $message->update([
                'status' => 'failed',
                'content' => '',
                'error' => $response['error'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Return the updated message
        |--------------------------------------------------------------------------
        */

        $message->load('provider');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $response['success'],

                'message' => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'provider_id' => $message->provider_id,
                    'provider' => $message->provider?->name ?? 'AI',

                    'status' => $message->status,

                    'content' => $message->content,

                    'error' => $message->error,

                    'html' => $message->status === 'completed'
                        ? $markdownRenderer->render($message->content)
                        : null,
                ],
            ]);
        }

        return redirect()->route('chats.show', $message->chat);
    }
}
