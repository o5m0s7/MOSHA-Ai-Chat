import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


// ========================================
// Icons
// ========================================

const sendIcon = `
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
`;

const stopIcon = `
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <rect
            x="6"
            y="6"
            width="12"
            height="12"
            rx="2"
        />
    </svg>
`;

const textareaMaxHeight = 150;


// ========================================
// Helpers
// ========================================

function escapeHtml(value) {

    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


// ========================================
// Copy Code Button
// ========================================

document.addEventListener(
    'click',
    async (event) => {

        const button =
            event.target.closest('.copy-code-btn');

        if (!button) {
            return;
        }


        const codeBlock =
            button.closest('.code-block');

        const code =
            codeBlock?.querySelector('code');


        if (!code) {
            return;
        }


        try {

            await navigator.clipboard.writeText(
                code.innerText
            );

            button.textContent =
                'Copied!';

            button.classList.add(
                'copied'
            );


            setTimeout(() => {

                button.textContent =
                    'Copy';

                button.classList.remove(
                    'copied'
                );

            }, 1500);

        } catch (error) {

            console.error(
                'Failed to copy code:',
                error
            );

        }
    }
);


// ========================================
// Chat
// ========================================

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const chatForm =
            document.querySelector(
                '.chat-input-container'
            );

        const textarea =
            chatForm?.querySelector(
                '.chat-input'
            );

        const submitButton =
            chatForm?.querySelector(
                '.send-btn'
            );

        const messagesContainer =
            document.querySelector(
                '.chat-messages'
            );


        // ========================================
        // Current AI Request
        // ========================================

        let currentRequestController = null;


        if (
            !chatForm ||
            !textarea ||
            !submitButton ||
            !messagesContainer
        ) {
            return;
        }


        // ========================================
        // Textarea Auto Resize
        // ========================================

        function autoResizeTextarea() {

            textarea.style.height =
                'auto';


            const newHeight =
                Math.min(
                    textarea.scrollHeight,
                    textareaMaxHeight
                );


            textarea.style.height =
                `${newHeight}px`;


            textarea.style.overflowY =
                textarea.scrollHeight >
                textareaMaxHeight
                    ? 'auto'
                    : 'hidden';
        }


        // ========================================
        // Update Send Button
        // ========================================

        function updateSendButton() {

            /*
            |--------------------------------------------------------------------------
            | During AI generation:
            | The button is STOP, so keep it enabled.
            |--------------------------------------------------------------------------
            */

            if (currentRequestController) {

                submitButton.disabled =
                    false;

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Normal state:
            | Disable Send when textarea is empty.
            |--------------------------------------------------------------------------
            */

            submitButton.disabled =
                textarea.value.trim() === '';
        }


        // ========================================
        // Initial Input State
        // ========================================

        autoResizeTextarea();

        updateSendButton();


        // ========================================
        // Submit / Stop
        // ========================================

        chatForm.addEventListener(
            'submit',
            async (event) => {

                event.preventDefault();


                /*
                |--------------------------------------------------------------------------
                | If AI is generating:
                | Same button = STOP
                |--------------------------------------------------------------------------
                */

                if (currentRequestController) {

                    currentRequestController.abort();

                    return;
                }


                const content =
                    textarea.value.trim();


                if (!content) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Disable typing
                |--------------------------------------------------------------------------
                */

                textarea.disabled =
                    true;


                /*
                |--------------------------------------------------------------------------
                | Keep button enabled because
                | it is now the STOP button
                |--------------------------------------------------------------------------
                */

                submitButton.disabled =
                    false;


                /*
                |--------------------------------------------------------------------------
                | Create request controller
                |--------------------------------------------------------------------------
                */

                currentRequestController =
                    new AbortController();


                /*
                |--------------------------------------------------------------------------
                | Send icon → Stop icon
                |--------------------------------------------------------------------------
                */

                submitButton.innerHTML =
                    stopIcon;

                submitButton.title =
                    'Stop generating';


                /*
                |--------------------------------------------------------------------------
                | Show user message immediately
                |--------------------------------------------------------------------------
                */

                addUserMessage(
                    content
                );


                /*
                |--------------------------------------------------------------------------
                | Clear textarea
                |--------------------------------------------------------------------------
                */

                textarea.value =
                    '';

                autoResizeTextarea();

                updateSendButton();


                /*
                |--------------------------------------------------------------------------
                | Show thinking message
                |--------------------------------------------------------------------------
                */

                const thinkingMessage =
                    addThinkingMessage();


                try {

                    const formData =
                        new FormData(
                            chatForm
                        );


                    formData.set(
                        'content',
                        content
                    );


                    const response =
                        await fetch(
                            chatForm.action,
                            {
                                method: 'POST',

                                body:
                                    formData,

                                signal:
                                    currentRequestController
                                        .signal,

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },
                            }
                        );


                    const data =
                        await response.json();


                    /*
                    |--------------------------------------------------------------------------
                    | Remove thinking message
                    |--------------------------------------------------------------------------
                    */

                    if (
                        thinkingMessage &&
                        thinkingMessage.isConnected
                    ) {

                        thinkingMessage.remove();
                    }


                    if (!response.ok) {

                        throw new Error(
                            data.message ||
                            'Something went wrong.'
                        );
                    }


                    if (
                        !data.success ||
                        !Array.isArray(
                            data.messages
                        )
                    ) {

                        throw new Error(
                            'Invalid response from server.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Add AI responses
                    |--------------------------------------------------------------------------
                    */

                    data.messages.forEach(
                        (message) => {

                            addAIMessage(
                                message
                            );

                        }
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Update chat title
                    |--------------------------------------------------------------------------
                    */

                    if (
                        data.chat?.title
                    ) {

                        document.title =
                            `${data.chat.title} - MOSHA AI`;


                        const activeChatLink =
                            document.querySelector(
                                `.recent-list a[href$="/chats/${data.chat.id}"]`
                            );


                        if (
                            activeChatLink
                        ) {

                            activeChatLink.textContent =
                                data.chat.title;
                        }
                    }


                    scrollToBottom();


                } catch (error) {

                    /*
                    |--------------------------------------------------------------------------
                    | User clicked STOP
                    |--------------------------------------------------------------------------
                    */

                    if (
                        error.name ===
                        'AbortError'
                    ) {

                        if (
                            thinkingMessage &&
                            thinkingMessage.isConnected
                        ) {

                            thinkingMessage.remove();
                        }


                        scrollToBottom();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Real error
                    |--------------------------------------------------------------------------
                    */

                    console.error(
                        'Chat error:',
                        error
                    );


                    if (
                        thinkingMessage &&
                        thinkingMessage.isConnected
                    ) {

                        thinkingMessage.remove();
                    }


                    addAIMessage({

                        provider:
                            'MOSHA AI',

                        status:
                            'completed',

                        html:
                            '<p>Sorry, something went wrong while processing your message.</p>',

                        id:
                            null,

                        error:
                            null,
                    });


                    scrollToBottom();

                } finally {

                    /*
                    |--------------------------------------------------------------------------
                    | Reset request state
                    |--------------------------------------------------------------------------
                    */

                    currentRequestController =
                        null;


                    /*
                    |--------------------------------------------------------------------------
                    | Restore Send button
                    |--------------------------------------------------------------------------
                    */

                    submitButton.innerHTML =
                        sendIcon;

                    submitButton.title =
                        'Send Message';


                    /*
                    |--------------------------------------------------------------------------
                    | Restore textarea
                    |--------------------------------------------------------------------------
                    */

                    textarea.disabled =
                        false;


                    autoResizeTextarea();

                    updateSendButton();

                    textarea.focus();
                }

            }
        );


        // ========================================
        // Enter / Shift + Enter
        // ========================================

        textarea.addEventListener(
            'keydown',
            (event) => {

                if (
                    event.key === 'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    chatForm.requestSubmit();
                }

            }
        );


        // ========================================
        // Textarea Input
        // ========================================

        textarea.addEventListener(
            'input',
            () => {

                autoResizeTextarea();

                updateSendButton();
            }
        );


        // ========================================
        // User Message
        // ========================================

        function addUserMessage(
            content
        ) {

            const messageBox =
                document.createElement(
                    'div'
                );


            messageBox.classList.add(
                'message-box',
                'user-message'
            );


            const sender =
                document.createElement(
                    'h2'
                );


            sender.classList.add(
                'sender-name',
                'user-name'
            );


            sender.textContent =
                'You';


            const messageContent =
                document.createElement(
                    'div'
                );


            messageContent.classList.add(
                'message-content'
            );


            const paragraph =
                document.createElement(
                    'p'
                );


            paragraph.textContent =
                content;


            messageContent.appendChild(
                paragraph
            );


            messageBox.appendChild(
                sender
            );


            messageBox.appendChild(
                messageContent
            );


            messagesContainer.appendChild(
                messageBox
            );
        }


        // ========================================
        // AI Message
        // ========================================

        function addAIMessage(
            message
        ) {

            const messageBox =
                document.createElement(
                    'div'
                );


            messageBox.classList.add(
                'message-box',
                'ai-message'
            );


            if (message.id) {

                messageBox.dataset.messageId =
                    message.id;
            }


            const sender =
                document.createElement(
                    'h2'
                );


            sender.classList.add(
                'sender-name',
                'ai-name'
            );


            sender.textContent =
                message.provider ||
                'AI';


            const messageContent =
                document.createElement(
                    'div'
                );


            messageContent.classList.add(
                'message-content'
            );


            /*
            |--------------------------------------------------------------------------
            | Failed response
            |--------------------------------------------------------------------------
            */

            if (
                message.status ===
                'failed'
            ) {

                renderFailedMessage(
                    messageContent,
                    message
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Successful response
            |--------------------------------------------------------------------------
            */

            else {

                messageContent.innerHTML =
                    message.html || '';


                addRegenerateButton(
                    messageContent,
                    message.id
                );
            }


            messageBox.appendChild(
                sender
            );


            messageBox.appendChild(
                messageContent
            );


            messagesContainer.appendChild(
                messageBox
            );
        }


        // ========================================
        // Failed Message
        // ========================================

        function renderFailedMessage(
            container,
            message
        ) {

            const errorContainer =
                document.createElement(
                    'div'
                );


            errorContainer.classList.add(
                'message-error'
            );


            const errorText =
                document.createElement(
                    'p'
                );


            errorText.textContent =
                message.error ||
                'This provider is currently unavailable.';


            const retryButton =
                document.createElement(
                    'button'
                );


            retryButton.type =
                'button';


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


            container.appendChild(
                errorContainer
            );
        }


        // ========================================
        // Regenerate Button
        // ========================================

        function addRegenerateButton(
            container,
            messageId
        ) {

            if (!messageId) {
                return;
            }


            const actions =
                document.createElement(
                    'div'
                );


            actions.classList.add(
                'message-actions'
            );


            const regenerateButton =
                document.createElement(
                    'button'
                );


            regenerateButton.type =
                'button';


            regenerateButton.classList.add(
                'regenerate-btn'
            );


            regenerateButton.dataset.messageId =
                messageId;


            regenerateButton.textContent =
                'Regenerate';


            actions.appendChild(
                regenerateButton
            );


            container.appendChild(
                actions
            );
        }


        // ========================================
        // Thinking Message
        // ========================================

        function addThinkingMessage() {

            const messageBox =
                document.createElement(
                    'div'
                );


            messageBox.classList.add(
                'message-box',
                'ai-message'
            );


            const sender =
                document.createElement(
                    'h2'
                );


            sender.classList.add(
                'sender-name',
                'ai-name'
            );


            sender.textContent =
                'MOSHA AI';


            const messageContent =
                document.createElement(
                    'div'
                );


            messageContent.classList.add(
                'message-content'
            );


            messageContent.innerHTML =
                '<p>Thinking...</p>';


            messageBox.appendChild(
                sender
            );


            messageBox.appendChild(
                messageContent
            );


            messagesContainer.appendChild(
                messageBox
            );


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

    }
);


// ========================================
// Retry Button
// ========================================

document.addEventListener(
    'click',
    async (event) => {

        const button =
            event.target.closest(
                '.retry-btn'
            );


        if (!button) {
            return;
        }


        const messageId =
            button.dataset.messageId;


        if (!messageId) {
            return;
        }


        button.disabled =
            true;


        button.textContent =
            'Retrying...';


        try {

            const response =
                await fetch(
                    `/messages/${messageId}/retry`,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute(
                                        'content'
                                    ),
                        },
                    }
                );


            const data =
                await response.json();


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Retry failed.'
                );
            }


            const message =
                data.message;


            const messageBox =
                button.closest(
                    '.message-box'
                );


            if (!messageBox) {
                return;
            }


            const messageContent =
                messageBox.querySelector(
                    '.message-content'
                );


            if (!messageContent) {
                return;
            }


            if (
                data.success &&
                message.status ===
                    'completed'
            ) {

                messageContent.innerHTML =
                    message.html || '';


                addRegenerateButtonToExistingMessage(
                    messageContent,
                    message.id
                );


                return;
            }


            messageContent.innerHTML =
                '';


            renderFailedMessageOutsideChat(
                messageContent,
                message
            );

        } catch (error) {

            console.error(
                'Retry error:',
                error
            );


            button.disabled =
                false;


            button.textContent =
                'Retry';
        }
    }
);


// ========================================
// Regenerate Button
// ========================================

document.addEventListener(
    'click',
    async (event) => {

        const button =
            event.target.closest(
                '.regenerate-btn'
            );


        if (!button) {
            return;
        }


        const messageId =
            button.dataset.messageId;


        if (!messageId) {
            return;
        }


        const messageBox =
            button.closest(
                '.message-box'
            );


        const messageContent =
            messageBox?.querySelector(
                '.message-content'
            );


        if (
            !messageBox ||
            !messageContent
        ) {
            return;
        }


        button.disabled =
            true;


        button.textContent =
            'Regenerating...';


        try {

            const response =
                await fetch(
                    `/messages/${messageId}/retry`,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute(
                                        'content'
                                    ),
                        },
                    }
                );


            const data =
                await response.json();


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Regeneration failed.'
                );
            }


            const message =
                data.message;


            if (
                data.success &&
                message.status ===
                    'completed'
            ) {

                messageContent.innerHTML =
                    message.html || '';


                addRegenerateButtonToExistingMessage(
                    messageContent,
                    message.id
                );


                return;
            }


            messageContent.innerHTML =
                '';


            renderFailedMessageOutsideChat(
                messageContent,
                message
            );

        } catch (error) {

            console.error(
                'Regenerate error:',
                error
            );


            button.disabled =
                false;


            button.textContent =
                'Regenerate';
        }
    }
);


// ========================================
// Retry / Regenerate UI Helpers
// ========================================

function renderFailedMessageOutsideChat(
    container,
    message
) {

    const errorContainer =
        document.createElement(
            'div'
        );


    errorContainer.classList.add(
        'message-error'
    );


    const errorText =
        document.createElement(
            'p'
        );


    errorText.textContent =
        message.error ||
        'This provider is currently unavailable.';


    const retryButton =
        document.createElement(
            'button'
        );


    retryButton.type =
        'button';


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


    container.appendChild(
        errorContainer
    );
}


function addRegenerateButtonToExistingMessage(
    container,
    messageId
) {

    if (!messageId) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent duplicate Regenerate buttons
    |--------------------------------------------------------------------------
    */

    const existingButton =
        container.querySelector(
            '.regenerate-btn'
        );


    if (existingButton) {
        existingButton.closest(
            '.message-actions'
        )?.remove();
    }


    const actions =
        document.createElement(
            'div'
        );


    actions.classList.add(
        'message-actions'
    );


    const regenerateButton =
        document.createElement(
            'button'
        );


    regenerateButton.type =
        'button';


    regenerateButton.classList.add(
        'regenerate-btn'
    );


    regenerateButton.dataset.messageId =
        messageId;


    regenerateButton.textContent =
        'Regenerate';


    actions.appendChild(
        regenerateButton
    );


    container.appendChild(
        actions
    );
}
