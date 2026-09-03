@php
    $markdownRenderer = app(\App\Services\MarkdownRenderer::class);
@endphp

<div class="chat-area">

    <header class="chat-header">
        <h1>All AI Responses</h1>
    </header>

    <div class="chat-messages">

        @forelse($messages as $message)

            @if($message->role === 'user')

                <div class="message-box user-message">

                    <h2 class="sender-name user-name">
                        You
                    </h2>

                    <div class="message-content">
                        <p>{{ $message->content }}</p>
                    </div>

                </div>

            @else

                <div class="message-box ai-message">

                    <h2 class="sender-name ai-name">
                        {{ $message->provider?->name ?? 'AI' }}
                    </h2>

                    <div class="message-content">
                        {!! $markdownRenderer->render($message->content) !!}
                    </div>

                </div>

            @endif

        @empty

            <div class="empty-chat">
                <p>Start a new conversation with MOSHA AI.</p>
            </div>

        @endforelse

    </div>

    <form
        action="{{ route('messages.store', $chat) }}"
        method="POST"
        class="chat-input-container"
    >

        @csrf

        <label class="icon-btn" title="Upload File">

            <input
                type="file"
                name="file"
                hidden
            >

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                />
            </svg>

        </label>

        <textarea
            name="content"
            class="chat-input"
            rows="1"
            placeholder="Message MOSHA AI..."
            required
        ></textarea>

        <button
            type="submit"
            class="icon-btn send-btn"
            title="Send Message"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                />
            </svg>
        </button>

    </form>

</div>
