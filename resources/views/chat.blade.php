<!-- Highlight.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
<meta name="csrf-token" content="{{ csrf_token() }}">


<x-layouts.app class="w-full">
    <div class="max-w-4xl mx-auto p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-xl rounded-xl transition-all duration-300 my-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <span class="bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">AI Chatbot</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Model Selection -->
            <div class="md:col-span-2">
                <label for="model" class="block font-medium text-gray-700 dark:text-gray-200 mb-2">Select Model</label>
                <flux:select id="model" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                    <option value="google/gemma-3-1b-it:free">Google: Gemma 3 1B</option>
                    <option value="google/gemini-2.0-flash-exp:free">Google: Gemini Flash 2.0</option>
                    <option value="meta-llama/llama-3-8b-instruct:free">Meta: Llama 3 8B Instruct</option>
                    <option value="cognitivecomputations/dolphin3.0-r1-mistral-24b:free">Dolphin3.0 R1 Mistral 24B</option>
                    <option value="deepseek/deepseek-r1-distill-qwen-32b:free">DeepSeek: R1 Distill Qwen 32B</option>
                </flux:select>
            </div>

            <!-- Session Selection -->
            <div class="md:col-span-1">
                <label for="session" class="block font-medium text-gray-700 dark:text-gray-200 mb-2">Chat Session</label>
                <div class="flex space-x-2">
                  <flux:select id="session" onchange="loadChats()"></flux:select>
                    <flux:button onclick="createSession()" variant="primary" class="p-3 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </flux:button>
                </div>
                <flux:button onclick="deleteSession()" variant="danger" icon="trash" class="mt-2 p-3 flex items-center justify-center">
            <span class="mr-2">Delete Current Session</span>
        </flux:button>
            </div>
        </div>

        <!-- Chat Display -->
        <div id="chatbox" class="dark:border-gray-700 p-4 h-96 overflow-y-auto bg-gray-50 dark:bg-gray-900 rounded-xl mb-4 shadow-inner">
            <!-- Chat messages will be displayed here -->
        </div>

        <!-- Chat Input -->
        <div class="flex flex-col space-y-2">
            <textarea id="message" placeholder="Type your message" rows="3"
                class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all duration-200 font-mono"></textarea>
            <div class="flex justify-between items-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Press Shift+Enter for new line
                </div>
                <flux:button onclick="sendMessage()"  variant="primary" class="px-6 py-3 flex items-center justify-center">
                    <span class="mr-2">Send</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </flux:button>
            </div>
                <flux:checkbox  id="webSearchToggle" class="mr-2" label="Search Web" description="For realtime updates try searching the web"/>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadSessions();

            // Check for saved theme
            if (localStorage.getItem("theme") === "dark" ||
                (!localStorage.getItem("theme") && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                document.documentElement.classList.add("dark");
                document.getElementById("darkModeText").textContent = "☀️";
            } else {
                document.documentElement.classList.remove("dark");
                document.getElementById("darkModeText").textContent = "🌙";
            }

            // Add event listener for key combinations
            document.getElementById("message").addEventListener("keydown", function(event) {
                // If Enter is pressed without Shift, send the message
                if (event.key === "Enter" && !event.shiftKey) {
                    event.preventDefault();
                    sendMessage();
                }
                // If Shift+Enter, allow new line (default behavior)
            });
        });

        function toggleDarkMode() {
            document.documentElement.classList.toggle("dark");
            let isDark = document.documentElement.classList.contains("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            document.getElementById("darkModeText").textContent = isDark ? "☀️" : "🌙";
        }

        function loadSessions() {
            axios.get('/sessions')
                .then(response => {
                    let sessionSelect = document.getElementById("session");
                    sessionSelect.innerHTML = "";
                    response.data.forEach(session => {
                        sessionSelect.innerHTML += `<option value="${session.id}">${session.name}</option>`;
                    });
                    if (sessionSelect.options.length > 0) {
                        loadChats();
                    }
                })
                .catch(error => console.error("Error loading sessions:", error));
        }

        function createSession() {
            let name = prompt("Enter chat session name:");
            if (!name) return;
            axios.post('/sessions', { name }).then(() => loadSessions());
        }

        function loadChats() {
            let sessionId = document.getElementById("session").value;
            axios.get(`/chats/${sessionId}`)
                .then(response => {
                    let chatbox = document.getElementById("chatbox");
                    chatbox.innerHTML = "";
                    response.data.forEach(chat => {
                        // Format user message
                        chatbox.innerHTML += formatUserMessage(chat.message);

                        // Format AI response
                        chatbox.innerHTML += formatAIResponse(chat.response);
                        highlightCodeBlocks();
                    });
                    chatbox.scrollTop = chatbox.scrollHeight;
                });
        }

        function formatUserMessage(message) {
    const mightBeCode = message.includes('\n') || /[{}\[\]()=><;]/.test(message);
    let messageHTML = mightBeCode ? `
        <div class='flex justify-end mb-4'>
            <div class=' text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-md animate-fade-in'>
                <pre class="text-left whitespace-pre-wrap overflow-wrap-anywhere text-sm font-mono bg-blue-600 p-2 rounded"><code>${escapeHtml(message)}</code></pre>
            </div>
        </div>` : `
        <div class='flex justify-end mb-4'>
            <div class='bg-blue-500 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-md animate-fade-in'>
                <p class="break-words whitespace-pre-wrap overflow-wrap-anywhere">${escapeHtml(message)}</p>
            </div>
        </div>`;

    setTimeout(() => highlightCodeBlocks(), 100); // 🔹 Ensure DOM is updated first
    return messageHTML;
}


function formatAIResponse(response) {
    let trimmed = response.trim();

    // Preserve code blocks
    let codeRegex = /```(\w*)\n([\s\S]*?)```/g;
    let codeBlocks = [];
    let textWithoutCode = trimmed.replace(codeRegex, function (match, language, code) {
        codeBlocks.push({ language: language, code: code });
        return `[[CODEBLOCK${codeBlocks.length - 1}]]`;
    });

    // Convert section titles (e.g., "HTML:", "CSS:", "JavaScript:") into <h2>
    textWithoutCode = textWithoutCode.replace(/^(\w+):\s*$/gm, '<h2 class="text-lg font-bold mt-3">$1</h2>');

    // Convert bullet points (* Item) to <ul><li>
    textWithoutCode = textWithoutCode.replace(/(?:^|\n)[*-]\s(.+)/g, '<li>$1</li>');
    textWithoutCode = textWithoutCode.replace(/(<li>.*<\/li>)/g, '<ul>$1</ul>');

    // Convert inline code (`code`) to <code>
    textWithoutCode = textWithoutCode.replace(/`([^`]+)`/g, '<code class="bg-gray-300 dark:bg-gray-700 p-1 rounded">$1</code>');

    // Ensure bold text (**bold**) is <strong>
    textWithoutCode = textWithoutCode.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Ensure italic text (*italic*) is <em>
    textWithoutCode = textWithoutCode.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Convert URLs into clickable links, including DuckDuckGo search result links
    textWithoutCode = textWithoutCode.replace(/(https?:\/\/[^\s]+)/g, function (match) {
        let url = match;

        // Decode DuckDuckGo Redirected Links
        if (url.includes("duckduckgo.com/l/?uddg=")) {
            let decodedUrl = decodeURIComponent(url.split("uddg=")[1].split("&")[0]);
            return `<a href="${decodedUrl}" target="_blank" class="text-blue-500 hover:underline">${decodedUrl}</a>`;
        }

        return `<a href="${url}" target="_blank" class="text-blue-500 hover:underline">${url}</a>`;
    });

    // Handle URLs that start with "//" (relative URLs, like DuckDuckGo links)
    textWithoutCode = textWithoutCode.replace(/\/\/duckduckgo\.com\/l\/\?uddg=([^&]+)/g, function (match, encodedUrl) {
        let decodedUrl = decodeURIComponent(encodedUrl);
        return `<a href="${decodedUrl}" target="_blank" class="text-blue-500 hover:underline">${decodedUrl}</a>`;
    });

    // Split into paragraphs
    let paragraphs = textWithoutCode
        .split(/\n\s*\n/)
        .map(part => `<p>${part.trim().replace(/\n/g, "<br>")}</p>`)
        .join("");

    // Restore code blocks
    codeBlocks.forEach((block, index) => {
        let codeHtml = `<div class="bg-gray-800 dark:bg-gray-900 rounded p-2 my-2 overflow-x-auto">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-400">${block.language || 'code'}</span>
                                <button onclick="copyToClipboard(this)" class="text-xs text-gray-400 hover:text-white">Copy</button>
                            </div>
                            <pre><code class="language-${block.language || 'plaintext'}">${escapeHtml(block.code)}</code></pre>
                        </div>`;
        paragraphs = paragraphs.replace(`[[CODEBLOCK${index}]]`, codeHtml);
    });

    return `
        <div class='flex justify-start mb-4'>
            <div class='bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 p-3 rounded-2xl rounded-tl-none max-w-[80%] shadow-md animate-fade-in'>
                <div class="whitespace-pre-wrap break-words overflow-wrap-anywhere text-sm">
                    ${paragraphs}
                </div>
            </div>
        </div>
    `;
}








function escapeHtml(text) {
  return text
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}


function highlightCodeBlocks() {
    document.querySelectorAll("#chatbox pre code").forEach((block) => {
        hljs.highlightElement(block);
    });
}




function copyToClipboard(button) {
    // Find the closest code block within the same parent container
    const codeElement = button.closest('div').nextElementSibling.querySelector('code');

    if (codeElement) {
        const code = codeElement.textContent.trim(); // Extract full code

        navigator.clipboard.writeText(code).then(() => {
            button.textContent = 'Copied!';
            setTimeout(() => {
                button.textContent = 'Copy';
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    } else {
        console.error('Code block not found.');
    }
}


async function sendMessage() {
    let sessionId = document.getElementById("session").value;
    let messageInput = document.getElementById("message");
    let chatbox = document.getElementById("chatbox");
    let model = document.getElementById("model").value;
    let webSearchEnabled = document.getElementById("webSearchToggle").checked;
    let message = messageInput.value.trim();

    if (!message) return;

    messageInput.value = ""; // Clear input

    // Show user's message with proper formatting
    chatbox.innerHTML += formatUserMessage(message);
    chatbox.scrollTop = chatbox.scrollHeight;

    // Show typing animation
    let typingElement = document.createElement("div");
    typingElement.className = "flex justify-start mb-4";
    typingElement.innerHTML = `
        <div class='bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 p-3 rounded-2xl rounded-tl-none shadow-md flex items-center space-x-1'>
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
        </div>`;

    chatbox.appendChild(typingElement);
    chatbox.scrollTop = chatbox.scrollHeight;

    try {
        let response = await axios.post('/chat', {
            chat_session_id: sessionId,
            message,
            model,
            web_search: webSearchEnabled
        });

        typingElement.remove();

        let botMessage = response.data.response || "Error fetching response";

        // Format bot response with proper code highlighting
        let formattedResponse = formatAIResponse(botMessage);
        chatbox.insertAdjacentHTML("beforeend", formattedResponse);
        chatbox.scrollTop = chatbox.scrollHeight;
        chatbox.scrollTop = chatbox.scrollHeight;

        // Apply syntax highlighting if needed
        highlightCodeBlocks();

    } catch (error) {
        console.error("Error:", error);
        typingElement.remove();
        chatbox.innerHTML += `
            <div class='flex justify-start mb-4'>
                <div class='bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 p-3 rounded-2xl rounded-tl-none max-w-[80%] shadow-md'>
                    <p class="font-medium">Error fetching response</p>
                </div>
            </div>`;

        chatbox.scrollTop = chatbox.scrollHeight;
    }
}



        function deleteSession() {
    const sessionSelect = document.getElementById('session');
    const chatContainer = document.getElementById('chatbox'); // Ensure this exists
    const sessionId = sessionSelect?.value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!sessionId) {
        alert("Please select a session to delete.");
        return;
    }

    if (!csrfToken) {
        alert("Security token is missing. Please refresh the page.");
        return;
    }

    if (!confirm("Are you sure you want to delete this session?")) {
        return;
    }

    fetch(`/delete-session/${sessionId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Session deleted successfully.");

            // Remove deleted session from dropdown
            sessionSelect.remove(sessionSelect.selectedIndex);

            // Clear chat messages from the UI
            if (chatContainer) {
                chatContainer.innerHTML = ''; // Clear the chat display
            }

            loadChats(); // Reload chat list
        } else {
            alert(`Failed to delete session: ${data.message}`);
        }
    })
    .catch(error => {
        console.error("Error deleting session:", error);
        alert("An error occurred. Please try again.");
    });
}


    </script>

    <style>
        /* Typing animation */
        .typing-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            opacity: 0.7;
            animation: typing 1.4s infinite both;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 100% {
                transform: scale(0.7);
                opacity: 0.7;
            }
            50% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Fade in animation */
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced word breaking */
        .overflow-wrap-anywhere {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Code styling */
        pre {
            margin: 0;
        }

        pre code {
            display: block;
            padding: 0.5rem;
            overflow-x: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            line-height: 1.5;
            white-space: pre-wrap; /* Ensures long lines wrap */
            word-wrap: break-word; /* Ensures words break properly */
        }

        /* Ensure all content respects container width */
        #chatbox * {
            max-width: 100%;
        }

        /* Ensure long words break */
        #chatbox {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
    </style>
</x-layouts.app>
