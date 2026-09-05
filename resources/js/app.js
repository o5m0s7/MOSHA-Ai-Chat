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

                addAIMessage(
                    message.provider || 'AI',
                    message.html
                );

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


            addAIMessage(
                'MOSHA AI',
                '<p>Sorry, something went wrong while processing your message.</p>'
            );


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

    function addAIMessage(providerName, html) {

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

        sender.textContent = providerName;


        const messageContent =
            document.createElement('div');

        messageContent.classList.add(
            'message-content'
        );

        /*
         * The HTML was already generated on the server
         * using MarkdownRenderer.
         *
         * This is what restores:
         * - Markdown
         * - Code blocks
         * - Copy button
         */

        messageContent.innerHTML = html;


        messageBox.appendChild(sender);
        messageBox.appendChild(messageContent);

        messagesContainer.appendChild(messageBox);

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
