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
    $activeModel = config('services.gemini.model', 'gemini-3.8-flash');
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
    class="relative z-50 font-sans"
    id="smartclaim-ai-chatbot-root"
>
    <!-- Scoped Custom Minimalist Scrollbars (Linear / Stripe aesthetic) -->
    <style>
        .smartclaim-chat-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.28) transparent;
        }
        .smartclaim-chat-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .smartclaim-chat-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .smartclaim-chat-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.28);
            border-radius: 9999px;
            transition: background-color 0.2s ease-in-out;
        }
        .smartclaim-chat-scroll:hover::-webkit-scrollbar-thumb {
            background-color: rgba(100, 116, 139, 0.55);
        }
        .dark .smartclaim-chat-scroll {
            scrollbar-color: rgba(71, 85, 105, 0.4) transparent;
        }
        .dark .smartclaim-chat-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(71, 85, 105, 0.4);
        }
        .dark .smartclaim-chat-scroll:hover::-webkit-scrollbar-thumb {
            background-color: rgba(100, 116, 139, 0.7);
        }
    </style>

    <!-- Floating Action Button (FAB) - Utilitarian & Solid Minimalist Design -->
    <div class="fixed bottom-20 right-4 sm:bottom-6 sm:right-6 z-40">
        <button 
            type="button"
            @click="toggleOpen()"
            class="group relative flex items-center justify-center w-13 h-13 rounded-full bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 shadow-lg border border-slate-800 dark:border-slate-200 hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-600"
            :class="{ 'rotate-90': isOpen }"
            aria-label="SmartClaim Assistant"
            title="SmartClaim Assistant"
        >
            <!-- Chat icon when closed -->
            <span x-show="!isOpen" class="flex items-center justify-center transition-transform duration-200">
                <i class="fa-regular fa-comment-dots text-lg"></i>
            </span>

            <!-- Close icon when open -->
            <span x-show="isOpen" x-cloak class="flex items-center justify-center transition-transform duration-200">
                <i class="fa-solid fa-xmark text-xl"></i>
            </span>

            <!-- Minimalist Unread Badge -->
            <span 
                x-show="unreadCount > 0 && !isOpen" 
                x-cloak 
                x-text="unreadCount"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-4.5 h-4.5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-semibold shadow-sm"
            ></span>

            <!-- Active Status Ping Dot -->
            <span 
                x-show="!isOpen && unreadCount === 0" 
                class="absolute top-0.5 right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"
            ></span>
        </button>
    </div>

    <!-- Chat Modal Window Drawer (Stripe / Linear Utilitarian Styling) -->
    <div 
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-250 transform"
        x-transition:enter-start="opacity-0 translate-y-4 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-98"
        @keydown.escape.window="isOpen = false"
        class="fixed bottom-20 left-3 right-3 sm:left-auto sm:right-6 sm:bottom-22 z-50 w-auto sm:w-[410px] max-h-[80vh] h-[560px] flex flex-col bg-white dark:bg-slate-950 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
    >
        <!-- Clean Slate Header Bar -->
        <div class="px-4 py-3 bg-slate-900 dark:bg-slate-950 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center text-slate-300">
                    <i class="fa-solid fa-bolt-lightning text-xs"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-xs font-semibold tracking-tight text-white">SmartClaim Assistant</h3>
                        <!-- Role Badge in soft muted tones -->
                        <span class="text-[9px] font-medium tracking-wide uppercase px-1.5 py-0.5 rounded bg-slate-800/80 text-slate-300 border border-slate-700/60">
                            {{ $roleBadge }}
                        </span>
                    </div>
                    <!-- Dynamic Model Tag with Green Indicator -->
                    <p class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span>
                        <span>{{ $activeModel }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-1">
                <!-- Reset Chat -->
                <button 
                    type="button" 
                    @click="resetChat()" 
                    title="Reset Session"
                    class="p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none"
                    aria-label="Reset Conversation"
                >
                    <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                </button>

                <!-- Close Modal -->
                <button 
                    type="button" 
                    @click="isOpen = false" 
                    title="Close"
                    class="p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none"
                    aria-label="Close Chat"
                >
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Conversation Scroll Area with Minimalist Scrollbar -->
        <div 
            x-ref="chatScroll" 
            class="smartclaim-chat-scroll flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50/50 dark:bg-slate-950/60 scroll-smooth"
        >
            <!-- Message Items Feed -->
            <template x-for="(msg, index) in messages" :key="msg.id || index">
                <div>
                    <!-- User Message Bubble (Right Aligned) -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex flex-col items-end space-y-1">
                            <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-slate-900 dark:bg-blue-600 text-white px-3.5 py-2.5 shadow-2xs text-xs sm:text-[13px] leading-relaxed">
                                <!-- Uploaded Image Thumbnail Preview -->
                                <template x-if="msg.image_preview">
                                    <div class="mb-2 rounded-lg overflow-hidden border border-white/20 bg-black/10">
                                        <img :src="msg.image_preview" alt="Receipt preview" class="max-h-36 w-full object-cover rounded-md" />
                                    </div>
                                </template>
                                <p class="whitespace-pre-wrap" x-text="msg.text"></p>
                            </div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 pr-1 font-mono" x-text="msg.timestamp"></span>
                        </div>
                    </template>

                    <!-- Assistant Message Bubble (Left Aligned) -->
                    <template x-if="msg.sender === 'bot'">
                        <div class="flex items-start space-x-2">
                            <div class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0 mt-0.5 text-[11px] font-semibold border border-slate-300/40 dark:border-slate-700/50">
                                <i class="fa-solid fa-microchip"></i>
                            </div>
                            <div class="flex flex-col items-start space-y-1 max-w-[85%]">
                                <div 
                                    class="rounded-2xl rounded-tl-sm px-3.5 py-2.5 text-xs sm:text-[13px] shadow-2xs border whitespace-pre-wrap leading-relaxed"
                                    :class="msg.is_error 
                                        ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800/80' 
                                        : 'bg-slate-100 dark:bg-slate-800/80 text-slate-800 dark:text-slate-200 border-slate-200/60 dark:border-slate-700/60'"
                                >
                                    <p x-text="msg.text"></p>

                                    <!-- Minimalist Compact Status Pill Tag -->
                                    <template x-if="msg.action">
                                        <div class="mt-2.5 flex items-center">
                                            <span 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium border"
                                                :class="msg.action.status === 'success' 
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' 
                                                    : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60'"
                                            >
                                                <i :class="msg.action.status === 'success' ? 'fa-solid fa-check text-emerald-600 dark:text-emerald-400' : 'fa-solid fa-xmark text-rose-600 dark:text-rose-400'"></i>
                                                <span x-text="msg.action.label"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 pl-1 font-mono" x-text="msg.timestamp"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Loading Thinking State -->
            <div x-show="isLoading" x-cloak class="flex items-start space-x-2">
                <div class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0 mt-0.5 text-[11px] border border-slate-300/40 dark:border-slate-700/50">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div class="rounded-2xl rounded-tl-sm px-3 py-2 bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 shadow-2xs flex items-center space-x-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500 animate-bounce [animation-delay:-0.3s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500 animate-bounce [animation-delay:-0.15s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500 animate-bounce"></span>
                </div>
            </div>

            <!-- Role-Specific Quick Suggestion Chips (Minimalist Border Buttons) -->
            <template x-if="messages.length <= 1">
                <div class="pt-2 flex flex-wrap gap-1.5">
                    <template x-for="chip in quickChips" :key="chip.label">
                        <button 
                            type="button"
                            @click="sendQuickPrompt(chip.prompt)"
                            class="text-[11px] font-medium px-2.5 py-1 rounded-md bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:border-slate-400 dark:hover:border-slate-600 transition-colors shadow-2xs flex items-center gap-1.5"
                        >
                            <i :class="chip.icon" class="text-slate-400 dark:text-slate-500 text-[10px]"></i>
                            <span x-text="chip.label"></span>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Receipt Image Preview Chip (if selected) -->
        <template x-if="selectedFile">
            <div class="px-3.5 py-2 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2 truncate">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" alt="Attachment" class="w-7 h-7 rounded object-cover border border-slate-200 dark:border-slate-700 shrink-0" />
                    </template>
                    <div class="truncate">
                        <p class="font-medium text-slate-800 dark:text-slate-200 truncate text-[11px]" x-text="selectedFile.name"></p>
                        <p class="text-[9px] text-slate-400 font-mono" x-text="(selectedFile.size / 1024).toFixed(1) + ' KB'"></p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="removeSelectedFile()" 
                    class="p-1 rounded text-slate-400 hover:text-rose-500 transition-colors"
                    title="Remove receipt"
                >
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </template>

        <!-- Message Input Footer -->
        <div class="p-3 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 shrink-0">
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
                    title="Attach Receipt (Image / PDF)"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors focus:outline-none shrink-0"
                    aria-label="Attach Receipt"
                >
                    <i class="fa-solid fa-paperclip text-sm"></i>
                </button>

                <!-- Textarea Input -->
                <div class="relative flex-1">
                    <textarea 
                        x-ref="messageInput"
                        x-model="userInput"
                        @keydown.enter.exact.prevent="sendMessage()"
                        rows="1"
                        placeholder="Ask assistant or upload a receipt..."
                        class="w-full resize-none max-h-24 py-2 px-3 text-xs sm:text-[13px] rounded-xl bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 border border-slate-200 dark:border-slate-800 focus:border-slate-400 dark:focus:border-slate-600 focus:bg-white dark:focus:bg-slate-950 focus:outline-none transition-all placeholder-slate-400 dark:placeholder-slate-500"
                    ></textarea>
                </div>

                <!-- Send Button -->
                <button 
                    type="submit" 
                    :disabled="isLoading || (!userInput.trim() && !selectedFile)"
                    class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-2xs focus:outline-none shrink-0"
                    aria-label="Send Message"
                >
                    <i class="fa-solid fa-arrow-up text-xs sm:text-sm"></i>
                </button>
            </form>
            <div class="mt-1 flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 px-1 font-mono">
                <span>Enter ↵ to send</span>
                <span>{{ $activeModel }}</span>
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
                greetingText = `Hello ${this.userName}. I can summarize pending claims, evaluate budget caps, and execute executive approvals on verified vouchers.`;
            } else if (this.role === 'finance' || this.role === 'fin') {
                greetingText = `Hello ${this.userName}. I can query claim vouchers, inspect receipt hashes for duplicates, and execute claim pre-approvals.`;
            } else {
                greetingText = `Hello ${this.userName}. You can attach an expense receipt or ask me to check your claim submission status.`;
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
                    { label: 'Pending Approvals', prompt: 'List recent claims awaiting manager approval.', icon: 'fa-regular fa-clock' },
                    { label: 'Check Claim #CLM-1', prompt: 'Check the status and details of claim #CLM-1.', icon: 'fa-solid fa-magnifying-glass' }
                ];
            } else if (this.role === 'finance' || this.role === 'fin') {
                this.quickChips = [
                    { label: 'Pending Audits', prompt: 'Show recent pending claims awaiting audit.', icon: 'fa-solid fa-list-check' },
                    { label: 'Check Claim #CLM-1', prompt: 'Check the details and risk score for claim #CLM-1.', icon: 'fa-solid fa-magnifying-glass' }
                ];
            } else {
                this.quickChips = [
                    { label: 'My Recent Claims', prompt: 'What is the status of my recent claims?', icon: 'fa-solid fa-list-ul' },
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
                text: text || (file ? `[Attached: ${file.name}]` : ''),
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
                        text: data.message || (data.reply ?? 'Unable to process the request at this time.'),
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
                    text: 'Connection error while communicating with the assistant. Please try again.',
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
            if (!confirm('Clear chatbot history and reset the session?')) {
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
