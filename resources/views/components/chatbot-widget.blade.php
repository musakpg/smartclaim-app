@props([])

@php
    $currentUser = auth()->user();
    $role = strtolower(trim($currentUser->role ?? 'staff'));
    $userName = $currentUser->name ?? 'User';
    $roleBadge = match ($role) {
        'manager' => 'Manager Desk',
        'finance', 'fin' => 'Finance Auditor',
        default => 'Staff Assistant',
    };
@endphp

<div 
    x-data="chatbotWidget({
        role: '{{ $role }}',
        userName: '{{ addslashes($userName) }}',
        roleBadge: '{{ $roleBadge }}',
        csrfToken: '{{ csrf_token() }}',
        messageUrl: '{{ route('chatbot.message') }}',
        resetUrl: '{{ route('chatbot.reset') }}'
    })"
    class="relative z-50"
    id="smartclaim-ai-chatbot-root"
>
    <!-- Floating Action Button (FAB) -->
    <div class="fixed bottom-20 right-4 sm:bottom-6 sm:right-6 z-40">
        <button 
            type="button"
            @click="toggleOpen()"
            class="group relative flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-600 text-white shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800"
            :class="{ 'rotate-90': isOpen }"
            aria-label="SmartClaim AI Assistant"
            title="SmartClaim AI Assistant"
        >
            <!-- Chat / Sparkle Icon when Closed -->
            <span x-show="!isOpen" class="flex items-center justify-center transition-transform duration-300">
                <i class="fa-solid fa-wand-magic-sparkles text-xl"></i>
            </span>

            <!-- Close Icon when Open -->
            <span x-show="isOpen" x-cloak class="flex items-center justify-center transition-transform duration-300">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </span>

            <!-- Unread Badge Indicator -->
            <span 
                x-show="unreadCount > 0 && !isOpen" 
                x-cloak 
                x-text="unreadCount"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-rose-500 text-white text-[11px] font-bold shadow-md animate-pulse"
            ></span>

            <!-- Subtle Online Ping Dot -->
            <span 
                x-show="!isOpen && unreadCount === 0" 
                class="absolute top-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900"
            ></span>
        </button>
    </div>

    <!-- Chat Modal Window Drawer -->
    <div 
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        @keydown.escape.window="isOpen = false"
        class="fixed bottom-20 left-3 right-3 sm:left-auto sm:right-6 sm:bottom-22 z-50 w-auto sm:w-[420px] max-h-[82vh] h-[580px] flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/90 dark:border-slate-800 overflow-hidden"
    >
        <!-- Header Bar -->
        <div class="px-4 py-3.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-indigo-700 text-white flex items-center justify-between shrink-0 shadow-sm">
            <div class="flex items-center space-x-2.5">
                <div class="relative w-9 h-9 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center text-white shadow-inner">
                    <i class="fa-solid fa-robot text-lg"></i>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border border-indigo-700"></span>
                </div>
                <div>
                    <div class="flex items-center space-x-1.5">
                        <h3 class="text-sm font-bold tracking-tight text-white leading-none">SmartClaim AI</h3>
                        <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-white/20 text-blue-100">
                            {{ $roleBadge }}
                        </span>
                    </div>
                    <p class="text-[11px] text-blue-100/90 flex items-center space-x-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>Gemini 2.5 Flash • Multimodal</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-1">
                <!-- Reset Conversation Button -->
                <button 
                    type="button" 
                    @click="resetChat()" 
                    title="Reset Conversation"
                    class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-colors focus:outline-none"
                    aria-label="Reset Conversation"
                >
                    <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                </button>

                <!-- Close Modal Button -->
                <button 
                    type="button" 
                    @click="isOpen = false" 
                    title="Close Chat"
                    class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-colors focus:outline-none"
                    aria-label="Close Chat"
                >
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Conversation Scroll Area -->
        <div 
            x-ref="chatScroll" 
            class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50/60 dark:bg-slate-950/40 scroll-smooth"
        >
            <!-- Message Items Feed -->
            <template x-for="(msg, index) in messages" :key="msg.id || index">
                <div>
                    <!-- User Message Bubble (Right Aligned) -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex flex-col items-end space-y-1">
                            <div class="max-w-[85%] rounded-2xl rounded-tr-xs bg-blue-600 text-white px-3.5 py-2.5 shadow-sm text-xs sm:text-sm">
                                <!-- Uploaded Image Thumbnail Preview -->
                                <template x-if="msg.image_preview">
                                    <div class="mb-2 rounded-lg overflow-hidden border border-white/20 bg-black/10">
                                        <img :src="msg.image_preview" alt="Receipt preview" class="max-h-36 w-full object-cover rounded-md" />
                                    </div>
                                </template>
                                <p class="whitespace-pre-wrap leading-relaxed" x-text="msg.text"></p>
                            </div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 pr-1" x-text="msg.timestamp"></span>
                        </div>
                    </template>

                    <!-- Bot Message Bubble (Left Aligned) -->
                    <template x-if="msg.sender === 'bot'">
                        <div class="flex items-start space-x-2">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300 flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                <i class="fa-solid fa-robot text-xs"></i>
                            </div>
                            <div class="flex flex-col items-start space-y-1 max-w-[85%]">
                                <div 
                                    class="rounded-2xl rounded-tl-xs px-3.5 py-2.5 text-xs sm:text-sm shadow-sm border whitespace-pre-wrap leading-relaxed"
                                    :class="msg.is_error 
                                        ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800' 
                                        : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border-slate-200/80 dark:border-slate-700/80'"
                                >
                                    <p x-text="msg.text"></p>

                                    <!-- Distinct Tool Execution Badge Chip -->
                                    <template x-if="msg.action">
                                        <div 
                                            class="mt-2.5 p-2 rounded-xl text-xs flex items-center space-x-2 shadow-2xs border"
                                            :class="msg.action.status === 'success' 
                                                ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-200' 
                                                : 'bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-200'"
                                        >
                                            <i 
                                                class="text-sm shrink-0" 
                                                :class="msg.action.status === 'success' ? 'fa-solid fa-circle-check text-emerald-500' : 'fa-solid fa-triangle-exclamation text-rose-500'"
                                            ></i>
                                            <span class="font-semibold" x-text="msg.action.label"></span>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 pl-1" x-text="msg.timestamp"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Loading / Thinking Wave Pulse State -->
            <div x-show="isLoading" x-cloak class="flex items-start space-x-2">
                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300 flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid fa-robot text-xs"></i>
                </div>
                <div class="rounded-2xl rounded-tl-xs px-3.5 py-3 bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex items-center space-x-1.5">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium mr-1">Thinking</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-bounce [animation-delay:-0.3s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-bounce [animation-delay:-0.15s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-bounce"></span>
                </div>
            </div>

            <!-- Role-Specific Quick Suggestion Chips (shown when only initial message exists) -->
            <template x-if="messages.length <= 1">
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <template x-for="chip in quickChips" :key="chip.label">
                        <button 
                            type="button"
                            @click="sendQuickPrompt(chip.prompt)"
                            class="text-[11px] font-medium px-2.5 py-1 rounded-full bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors shadow-2xs"
                        >
                            <i :class="chip.icon" class="mr-1 text-indigo-500"></i>
                            <span x-text="chip.label"></span>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Receipt Image Preview Chip (if selected) -->
        <template x-if="selectedFile">
            <div class="px-4 py-2 bg-indigo-50 dark:bg-slate-800 border-t border-indigo-100 dark:border-slate-700 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2 truncate">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" alt="Attachment" class="w-8 h-8 rounded object-cover border border-slate-200 dark:border-slate-700 shrink-0" />
                    </template>
                    <div class="truncate">
                        <p class="font-semibold text-slate-800 dark:text-slate-200 truncate" x-text="selectedFile.name"></p>
                        <p class="text-[10px] text-slate-500" x-text="(selectedFile.size / 1024).toFixed(1) + ' KB'"></p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="removeSelectedFile()" 
                    class="p-1 rounded text-slate-400 hover:text-rose-500 transition-colors"
                    title="Remove receipt"
                >
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </template>

        <!-- Message Input Footer -->
        <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
            <form @submit.prevent="sendMessage()" class="flex items-end space-x-2">
                <!-- Hidden Receipt File Input -->
                <input 
                    type="file" 
                    x-ref="fileInput" 
                    @change="onFileSelected($event)" 
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    class="hidden"
                />

                <!-- File Attachment Trigger Button -->
                <button 
                    type="button" 
                    @click="$refs.fileInput.click()" 
                    title="Attach Receipt Image or PDF"
                    class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none shrink-0"
                    aria-label="Attach Receipt"
                >
                    <i class="fa-solid fa-paperclip text-base"></i>
                </button>

                <!-- Textarea / Input -->
                <div class="relative flex-1">
                    <textarea 
                        x-ref="messageInput"
                        x-model="userInput"
                        @keydown.enter.exact.prevent="sendMessage()"
                        rows="1"
                        placeholder="Ask AI or attach receipt..."
                        class="w-full resize-none max-h-24 py-2 px-3 text-xs sm:text-sm rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white border border-transparent focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-400"
                    ></textarea>
                </div>

                <!-- Send Button -->
                <button 
                    type="submit" 
                    :disabled="isLoading || (!userInput.trim() && !selectedFile)"
                    class="p-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400 shrink-0"
                    aria-label="Send Message"
                >
                    <i class="fa-solid fa-paper-plane text-xs sm:text-sm"></i>
                </button>
            </form>
            <div class="mt-1 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 px-1">
                <span>Enter to send • Shift+Enter for newline</span>
                <span>Powered by Gemini</span>
            </div>
        </div>
    </div>
</div>

<script>
function chatbotWidget(config) {
    return {
        isOpen: false,
        unreadCount: 0,
        messages: [],
        userInput: '',
        selectedFile: null,
        imagePreview: null,
        isLoading: false,
        role: config.role || 'staff',
        userName: config.userName || 'User',
        roleBadge: config.roleBadge || 'Assistant',
        csrfToken: config.csrfToken,
        messageUrl: config.messageUrl,
        resetUrl: config.resetUrl,
        quickChips: [],

        init() {
            this.setupInitialGreeting();
            this.setupQuickChips();
        },

        setupInitialGreeting() {
            let greetingText = '';
            if (this.role === 'manager') {
                greetingText = `👋 Hello Manager ${this.userName}! I am your SmartClaim Executive Assistant. I can check pending claim submissions, highlight budget violations, and execute final approvals on verified claims.`;
            } else if (this.role === 'finance' || this.role === 'fin') {
                greetingText = `👋 Hello ${this.userName}! I am your SmartClaim Finance Auditor. I can verify receipt hashes, search claims by voucher ID, and execute claim pre-approvals directly for you.`;
            } else {
                greetingText = `👋 Hi ${this.userName}! I am your SmartClaim AI Assistant. Upload a receipt image or let me know what you'd like to claim, and I'll extract the details, check policies, and submit the voucher for you.`;
            }

            this.messages = [
                {
                    id: Date.now(),
                    sender: 'bot',
                    text: greetingText,
                    timestamp: this.formatTime(new Date()),
                    action: null
                }
            ];
        },

        setupQuickChips() {
            if (this.role === 'manager') {
                this.quickChips = [
                    { label: 'Pending Approvals', prompt: 'List recent claims awaiting manager approval.', icon: 'fa-solid fa-clock' },
                    { label: 'Check Claim #CLM-1', prompt: 'Check the status and details of claim #CLM-1.', icon: 'fa-solid fa-magnifying-glass' }
                ];
            } else if (this.role === 'finance' || this.role === 'fin') {
                this.quickChips = [
                    { label: 'Pending Audits', prompt: 'Show recent pending claims awaiting audit.', icon: 'fa-solid fa-list-check' },
                    { label: 'Check Claim #CLM-1', prompt: 'Check the details and risk score for claim #CLM-1.', icon: 'fa-solid fa-magnifying-glass' }
                ];
            } else {
                this.quickChips = [
                    { label: 'My Recent Claims', prompt: 'What is the status of my recent claims?', icon: 'fa-solid fa-list' },
                    { label: 'Check Claim #CLM-1', prompt: 'Check the status of claim #CLM-1.', icon: 'fa-solid fa-magnifying-glass' }
                ];
            }
        },

        toggleOpen() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.unreadCount = 0;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.messageInput) {
                        this.$refs.messageInput.focus();
                    }
                });
            }
        },

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds the 5MB limit. Please upload a smaller receipt image.');
                event.target.value = '';
                return;
            }

            this.selectedFile = file;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                this.imagePreview = null;
            }
        },

        removeSelectedFile() {
            this.selectedFile = null;
            this.imagePreview = null;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        sendQuickPrompt(prompt) {
            this.userInput = prompt;
            this.sendMessage();
        },

        async sendMessage() {
            const text = this.userInput.trim();
            const file = this.selectedFile;

            if (!text && !file) return;
            if (this.isLoading) return;

            const previewSnapshot = this.imagePreview;

            // Push User Message to Feed
            this.messages.push({
                id: Date.now(),
                sender: 'user',
                text: text || (file ? `[Attached Receipt: ${file.name}]` : ''),
                timestamp: this.formatTime(new Date()),
                image_preview: previewSnapshot
            });

            // Reset input fields
            this.userInput = '';
            this.removeSelectedFile();
            this.isLoading = true;
            this.scrollToBottom();

            // Prepare Form Data payload
            const formData = new FormData();
            if (text) formData.append('message', text);
            if (file) formData.append('receipt', file);

            try {
                const response = await fetch(this.messageUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    this.messages.push({
                        id: Date.now() + 1,
                        sender: 'bot',
                        text: data.reply || 'Request processed.',
                        timestamp: this.formatTime(new Date()),
                        action: data.action_executed || null,
                        is_error: false
                    });
                } else {
                    this.messages.push({
                        id: Date.now() + 1,
                        sender: 'bot',
                        text: data.message || 'I encountered an error processing your request.',
                        timestamp: this.formatTime(new Date()),
                        action: null,
                        is_error: true
                    });
                }

                if (!this.isOpen) {
                    this.unreadCount++;
                }

            } catch (err) {
                this.messages.push({
                    id: Date.now() + 1,
                    sender: 'bot',
                    text: 'Unable to connect to SmartClaim AI service. Please check your network connection.',
                    timestamp: this.formatTime(new Date()),
                    action: null,
                    is_error: true
                });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        async resetChat() {
            if (!confirm('Clear chatbot history and start a fresh conversation?')) {
                return;
            }

            try {
                await fetch(this.resetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });
            } catch (e) {
                // Ignore failure on reset
            }

            this.setupInitialGreeting();
            this.removeSelectedFile();
            this.unreadCount = 0;
            this.scrollToBottom();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatScroll;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        formatTime(date) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>
