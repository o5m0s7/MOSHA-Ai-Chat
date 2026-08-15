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
        if ($chat->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $userId = Auth::id();

            $responses = $aiManager->send($validated['content']);
            
            DB::transaction(function () use ($chat, $validated, $responses, $userId) {
            
                $chat->messages()->create([
                    'user_id' => $userId,
                    'role' => 'user',
                    'content' => $validated['content'],
                ]);
            
                foreach ($responses as $response) {
            
                    $chat->messages()->create([
                        'user_id'     => $userId,
                        'provider_id' => $response['provider_id'],
                        'role'        => 'assistant',
                        'content'     => $response['content'],
                    ]);
            
                }
            });

        return redirect()->route('chats.show', $chat);
    }
}