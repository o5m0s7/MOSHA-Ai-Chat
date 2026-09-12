import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


// ========================================
// Copy Code Button
// ========================================

document.addEventListener('click', async (event) => {

    const button = event.target.closest('.copy-code-btn');

    if (!button) {
        return;
    }

    const codeBlock = button.closest('.code-block');
    const code = codeBlock?.querySelector('code');

    if (!code) {
        return;
    }

    try {

        await navigator.clipboard.writeText(code.innerText);

        button.textContent = 'Copied!';
        button.classList.add('copied');

        setTimeout(() => {

            button.textContent = 'Copy';
            button.classList.remove('copied');

        }, 1500);

    } catch (error) {

        console.error('Failed to copy code:', error);

    }
});


// ========================================
// Chat
// ========================================

document.addEventListener('DOMContentLoaded', () => {

    const chatForm = document.querySelector('.chat-input-container');
    const textarea = chatForm?.querySelector('.chat-input');
    const submitButton = chatForm?.querySelector('.send-btn');
    const messagesContainer = document.querySelector('.chat-messages');

    if (
        !chatForm ||
        !textarea ||
        !submitButton ||
        !messagesContainer
    ) {
        return;
    }


    // ========================================
    // Submit Chat
    // ========================================

    chatForm.addEventListener('submit', async (event) => {

        event.preventDefault();

        const content = textarea.value.trim();

        if (!content) {
            return;
        }


        // Disable input
        textarea.disabled = true;
        submitButton.disabled = true;


        // Show user's message immediately
        addUserMessage(content);


        // Clear input
        textarea.value = '';


        // Scroll down
        scrollToBottom();


        // Show thinking message
        const thinkingMessage = addThinkingMessage();


        try {

            const formData = new FormData(chatForm);

            formData.set('content', content);


            const response = await fetch(chatForm.action, {

                method: 'POST',

                body: formData,

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },

            });


            const data = await response.json();


            // Remove thinking message
            thinkingMessage.remove();


            if (!response.ok) {

                throw new Error(
                    data.message || 'Something went wrong.'
                );

            }


            if (
                !data.success ||
                !Array.isArray(data.messages)
            ) {

                throw new Error(
                    'Invalid response from server.'
                );

            }


            // ========================================
            // Add ONLY the new AI responses
            // ========================================

            data.messages.forEach((message) => {

                addAIMessage(message);

            });


            // Update document title
        if (data.chat?.title) {
            document.title = `${data.chat.title} - MOSHA AI`;
                    
            const activeChatLink = document.querySelector(
                `.recent-list a[href$="/chats/${data.chat.id}"]`
            );
        
            if (activeChatLink) {
                activeChatLink.textContent = data.chat.title;
            }
        }


            scrollToBottom();

        } catch (error) {

            console.error('Chat error:', error);


            thinkingMessage.remove();


            addAIMessage({
                provider: 'MOSHA AI',
                status: 'completed',
                html: '<p>Sorry, something went wrong while processing your message.</p>',
                id: null,
                error: null,
            });


            scrollToBottom();

        } finally {

            textarea.disabled = false;
            submitButton.disabled = false;

            textarea.focus();

        }

    });


    // ========================================
    // Enter / Shift + Enter
    // ========================================

    textarea.addEventListener('keydown', (event) => {

        if (
            event.key === 'Enter' &&
            !event.shiftKey
        ) {

            event.preventDefault();

            chatForm.requestSubmit();

        }

    });


    // ========================================
    // User Message
    // ========================================

    function addUserMessage(content) {

        const messageBox =
            document.createElement('div');

        messageBox.classList.add(
            'message-box',
            'user-message'
        );


        const sender =
            document.createElement('h2');

        sender.classList.add(
            'sender-name',
            'user-name'
        );

        sender.textContent = 'You';


        const messageContent =
            document.createElement('div');

        messageContent.classList.add(
            'message-content'
        );


        const paragraph =
            document.createElement('p');

        paragraph.textContent = content;


        messageContent.appendChild(paragraph);

        messageBox.appendChild(sender);
        messageBox.appendChild(messageContent);

        messagesContainer.appendChild(messageBox);

    }


    // ========================================
    // AI Message
    // ========================================

    function addAIMessage(message) {

        const messageBox =
            document.createElement('div');

        messageBox.classList.add(
            'message-box',
            'ai-message'
        );


        const sender =
            document.createElement('h2');

        sender.classList.add(
            'sender-name',
            'ai-name'
        );

        sender.textContent =
            message.provider || 'AI';


        const messageContent =
            document.createElement('div');

        messageContent.classList.add(
            'message-content'
        );


        // Failed provider
        if (message.status === 'failed') {

            const errorContainer =
                document.createElement('div');

            errorContainer.classList.add(
                'message-error'
            );


            const errorText =
                document.createElement('p');

            errorText.textContent =
                message.error ||
                'This provider is currently unavailable.';


            const retryButton =
                document.createElement('button');

            retryButton.type = 'button';

            retryButton.classList.add(
                'retry-btn'
            );

            retryButton.dataset.messageId =
                message.id;

            retryButton.textContent =
                'Retry';


            errorContainer.appendChild(
                errorText
            );

            errorContainer.appendChild(
                retryButton
            );


            messageContent.appendChild(
                errorContainer
            );

        }

        // Successful provider
        else {

            messageContent.innerHTML =
                message.html || '';

        }


        messageBox.appendChild(sender);

        messageBox.appendChild(
            messageContent
        );

        messagesContainer.appendChild(
            messageBox
        );
    }


    // ========================================
    // Thinking Message
    // ========================================

    function addThinkingMessage() {

        const messageBox =
            document.createElement('div');

        messageBox.classList.add(
            'message-box',
            'ai-message'
        );


        const sender =
            document.createElement('h2');

        sender.classList.add(
            'sender-name',
            'ai-name'
        );

        sender.textContent = 'MOSHA AI';


        const messageContent =
            document.createElement('div');

        messageContent.classList.add(
            'message-content'
        );

        messageContent.innerHTML =
            '<p>Thinking...</p>';


        messageBox.appendChild(sender);
        messageBox.appendChild(messageContent);

        messagesContainer.appendChild(messageBox);

        scrollToBottom();


        return messageBox;

    }


    // ========================================
    // Scroll
    // ========================================

    function scrollToBottom() {

        messagesContainer.scrollTop =
            messagesContainer.scrollHeight;

    }

});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.retry-btn');

    if (!button) return;

    const messageId = button.dataset.messageId;

    if (!messageId) return;

    button.disabled = true;
    button.textContent = 'Retrying...';

    try {
        const response = await fetch(
            `/messages/${messageId}/retry`,
            {
                method: 'POST',

                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message || 'Retry failed.'
            );
        }

        const message = data.message;

        const messageBox = button.closest('.message-box');

        if (!messageBox) {
            return;
        }

        const messageContent =
            messageBox.querySelector('.message-content');

        if (!messageContent) {
            return;
        }

        if (
            data.success &&
            message.status === 'completed'
        ) {
            messageContent.innerHTML = message.html;

            button.remove();

            return;
        }

        messageContent.innerHTML = `
            <div class="message-error">
                <p>${escapeHtml(
                    message.error ||
                    'This provider is currently unavailable.'
                )}</p>

                <button
                    type="button"
                    class="retry-btn"
                    data-message-id="${message.id}"
                >
                    Retry
                </button>
            </div>
        `;
    } catch (error) {
        console.error('Retry error:', error);

        button.disabled = false;
        button.textContent = 'Retry';
    }
});

function escapeHtml(value) {
    const div = document.createElement('div');

    div.textContent = value ?? '';

    return div.innerHTML;
}

