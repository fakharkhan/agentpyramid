<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Chat with YouTube Agent - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen flex flex-col">
        <div class="flex-1 flex flex-col max-w-4xl mx-auto w-full p-4 lg:p-6">
            <!-- Header -->
            <header class="mb-6">
                <h1 class="text-2xl lg:text-3xl font-semibold mb-2">Chat with YouTube Agent</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Powered by NeuronAI and Anthropic Claude</p>
            </header>

            <!-- Chat Messages Container -->
            <div id="chat-messages" class="flex-1 overflow-y-auto mb-4 space-y-4 pb-4">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                        AI
                    </div>
                    <div class="flex-1">
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-4 shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <p class="text-sm">Hello! I'm your YouTube Agent. How can I help you today?</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input Form -->
            <form id="chat-form" class="flex gap-2">
                <input
                    type="text"
                    id="message-input"
                    name="message"
                    placeholder="Type your message here..."
                    autocomplete="off"
                    class="flex-1 px-4 py-3 rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#706f6c] dark:placeholder:text-[#A1A09A] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                >
                <button
                    type="submit"
                    id="send-button"
                    class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Send
                </button>
            </form>
        </div>

        <script>
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const chatMessages = document.getElementById('chat-messages');
            const sendButton = document.getElementById('send-button');

            // Set up CSRF token for all requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const message = messageInput.value.trim();
                if (!message) {
                    return;
                }

                // Disable form while processing
                messageInput.disabled = true;
                sendButton.disabled = true;
                sendButton.textContent = 'Sending...';

                // Add user message to chat
                addMessage(message, 'user');

                // Clear input
                messageInput.value = '';

                try {
                    const response = await fetch('{{ route("chat.send") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        addMessage(data.message, 'assistant');
                    } else {
                        addMessage(data.message || 'An error occurred. Please try again.', 'assistant', true);
                    }
                } catch (error) {
                    addMessage('Failed to send message. Please check your connection and try again.', 'assistant', true);
                } finally {
                    // Re-enable form
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    sendButton.textContent = 'Send';
                    messageInput.focus();
                }
            });

            function addMessage(text, role, isError = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex gap-3';

                if (role === 'user') {
                    messageDiv.innerHTML = `
                        <div class="flex-1"></div>
                        <div class="flex gap-3 flex-row-reverse">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm font-medium">
                                You
                            </div>
                            <div class="flex-1 max-w-[80%]">
                                <div class="bg-blue-500 text-white rounded-lg p-4 shadow-sm">
                                    <p class="text-sm whitespace-pre-wrap">${escapeHtml(text)}</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    const errorClass = isError ? 'border-red-500' : '';
                    messageDiv.innerHTML = `
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                            AI
                        </div>
                        <div class="flex-1">
                            <div class="bg-white dark:bg-[#161615] rounded-lg p-4 shadow-sm border border-[#e3e3e0] dark:border-[#3E3E3A] ${errorClass}">
                                <p class="text-sm whitespace-pre-wrap">${escapeHtml(text)}</p>
                            </div>
                        </div>
                    `;
                }

                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Focus input on load
            messageInput.focus();
        </script>
    </body>
</html>

