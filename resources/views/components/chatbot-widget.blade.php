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

    $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
    if (empty($categories)) {
        $categories = [
            'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'Staff Operational Meals',
            'Office Pantry & Amenities',
            'Office Supplies & Stationery',
            'Site Tools & Hardware',
            'Transportation & Toll',
            'Travel & Lodging',
        ];
    }
    $companyFleetVehicles = \App\Models\Vehicle::companyFleet()->get()->map(fn($v) => [
        'vehicle_id' => $v->vehicle_id,
        'plate_number' => $v->plate_number,
        'brand_model' => $v->brand_model,
    ])->toArray();
    $personalVehicles = ($currentUser && method_exists($currentUser, 'vehicles'))
        ? $currentUser->vehicles()->personal()->get()->map(fn($v) => [
            'vehicle_id' => $v->vehicle_id,
            'plate_number' => $v->plate_number,
            'brand_model' => $v->brand_model,
        ])->toArray()
        : [];
@endphp

<div 
    x-data="chatbotWidget({
        role: '{{ $role }}',
        userName: '{{ addslashes($userName) }}',
        roleBadge: '{{ $roleBadge }}',
        csrfToken: '{{ csrf_token() }}',
        messageUrl: '{{ route('chatbot.message') }}',
        historyUrl: '{{ route('chatbot.history') }}',
        resetUrl: '{{ route('chatbot.reset') }}',
        categories: {{ \Illuminate\Support\Js::from($categories) }},
        companyFleet: {{ \Illuminate\Support\Js::from($companyFleetVehicles) }},
        personalVehicles: {{ \Illuminate\Support\Js::from($personalVehicles) }}
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
        /* Custom thin scrollbar for textarea */
        .chatbot-input-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.4) transparent;
        }
        .chatbot-input-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .chatbot-input-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .chatbot-input-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.4);
            border-radius: 9999px;
        }
        .chatbot-input-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.7);
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
        @keydown.escape.window="closeChat()"
        class="fixed bottom-20 left-3 right-3 sm:left-auto sm:right-6 sm:bottom-22 z-50 w-auto sm:w-[420px] max-h-[82vh] h-[580px] flex flex-col bg-white dark:bg-slate-950 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
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
                    @click="closeChat()" 
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
            class="smartclaim-chat-scroll flex-1 overflow-y-auto p-3.5 space-y-3 bg-slate-50/50 dark:bg-slate-950/60 scroll-smooth"
        >
            <!-- Message Items Feed -->
            <template x-for="(msg, index) in messages" :key="msg.id || index">
                <div>
                    <!-- User Message Bubble (Right Aligned) -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex flex-col items-end space-y-1">
                            <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-slate-900 dark:bg-blue-600 text-white py-2 px-3 shadow-2xs text-xs sm:text-[13px] leading-normal">
                                <!-- Uploaded Image Thumbnail Preview -->
                                <template x-if="msg.image_preview">
                                    <div class="mb-1.5 rounded-lg overflow-hidden border border-white/20 bg-black/10">
                                        <img :src="msg.image_preview" alt="Receipt preview" class="max-h-32 w-full object-cover rounded-md" />
                                    </div>
                                </template>
                                <div class="leading-normal" x-html="formatMarkdown(msg.text)"></div>
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
                            <div class="flex flex-col items-start space-y-1 max-w-[88%]">
                                <div 
                                    class="rounded-2xl rounded-tl-sm py-2 px-3 text-xs sm:text-[13px] shadow-2xs border leading-normal w-full"
                                    :class="msg.is_error 
                                        ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 border-rose-200 dark:border-rose-800/80' 
                                        : 'bg-slate-100 dark:bg-slate-800/80 text-slate-800 dark:text-slate-200 border-slate-200/60 dark:border-slate-700/60'"
                                >
                                    <div class="leading-normal" x-html="formatMarkdown(msg.text)"></div>

                                    <!-- Interactive Review: Shortcut Confirmation & Edit Details Buttons -->
                                    <template x-if="hasStructuredDetails(msg.text) && (!msg.action || msg.action.status !== 'success')">
                                        <div class="mt-2.5 pt-2 border-t border-slate-200/70 dark:border-slate-700/70 flex flex-wrap items-center gap-1.5">
                                            <!-- Fuel & Fleet Compliance Lock: When plate is missing or unspecified, hide Proceed and show Select Vehicle / Edit Details -->
                                            <template x-if="isFuelMissingPlate(msg.text)">
                                                <button 
                                                    type="button"
                                                    @click="openEditModal(msg.text)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-semibold shadow-xs transition cursor-pointer"
                                                >
                                                    <i class="fa-solid fa-car text-[10px]"></i>
                                                    <span>Select Vehicle / Edit Details</span>
                                                </button>
                                            </template>

                                            <!-- Proceed & Submit Claim (Only shown when not missing vehicle plate) -->
                                            <template x-if="!isFuelMissingPlate(msg.text)">
                                                <button 
                                                    type="button"
                                                    @click="sendQuickPrompt('Yes, proceed and submit this claim')"
                                                    :disabled="isLoading"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold shadow-xs transition cursor-pointer disabled:opacity-40"
                                                >
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                    <span>Proceed & Submit Claim</span>
                                                </button>
                                            </template>

                                            <template x-if="!isFuelMissingPlate(msg.text)">
                                                <button 
                                                    type="button"
                                                    @click="openEditModal(msg.text)"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-medium bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs"
                                                    title="Edit claim details"
                                                >
                                                    <i class="fa-solid fa-pen-to-square text-[10px] text-slate-500"></i>
                                                    <span>Edit Details</span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Post-Submission Rich Voucher Card -->
                                    <template x-if="msg.action && msg.action.tool === 'submit_claim' && msg.action.status === 'success'">
                                        <div class="mt-2.5 p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-2.5 w-full">
                                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded">
                                                        Pending Audit
                                                    </span>
                                                </div>
                                                <span class="text-[11px] font-mono font-bold text-slate-800 dark:text-slate-200" x-text="'#' + (msg.action.data.voucher_no || ('CLM-' + msg.action.data.claim_id))"></span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2.5">
                                                <!-- Receipt Thumbnail -->
                                                <template x-if="msg.action.data.receipt_preview || sessionReceiptPreview">
                                                    <div class="w-12 h-12 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                                        <img :src="msg.action.data.receipt_preview || sessionReceiptPreview" alt="Receipt Thumbnail" class="w-full h-full object-cover" />
                                                    </div>
                                                </template>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate" x-text="msg.action.data.merchant_name || 'Expense Claim'"></p>
                                                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="'RM ' + (parseFloat(msg.action.data.amount || 0).toFixed(2))"></p>
                                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate" x-text="msg.action.data.category || ''"></p>
                                                </div>
                                            </div>

                                            <div class="pt-1.5 flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80">
                                                <span class="text-[10px] text-slate-400">Submitted for Finance audit</span>
                                                <a 
                                                    :href="'{{ route('claims.history') }}?claim_id=' + (msg.action.data.claim_id || '')" 
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition"
                                                >
                                                    <span>View Claims History</span>
                                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Standard Action Pill Tag (for check_claim_status, pre_approve, final_approve) -->
                                    <template x-if="msg.action && msg.action.tool !== 'submit_claim'">
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

                                <!-- First-Person User Action Command Chips -->
                                <template x-if="getActions(msg.text).length > 0">
                                    <div class="mt-1.5 flex flex-wrap gap-1.5 pl-0.5">
                                        <template x-for="(actionItem, aIdx) in getActions(msg.text)" :key="aIdx">
                                            <button 
                                                type="button" 
                                                @click="sendQuickPrompt(actionItem)"
                                                :disabled="isLoading"
                                                class="text-[11px] font-medium px-2.5 py-1 rounded-full bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border border-slate-200/90 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 transition shadow-2xs flex items-center gap-1.5 cursor-pointer disabled:opacity-40 group"
                                            >
                                                <i class="fa-solid fa-bolt text-[10px] text-indigo-500 dark:text-indigo-400 group-hover:scale-110 transition-transform"></i>
                                                <span x-text="actionItem"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

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
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors focus:outline-none shrink-0 cursor-pointer"
                    aria-label="Attach Receipt"
                >
                    <i class="fa-solid fa-paperclip text-sm"></i>
                </button>

                <!-- Textarea Input (Dynamic Auto-Resize with Custom Scrollbar) -->
                <div class="relative flex-1">
                    <textarea 
                        x-ref="messageInput"
                        x-model="userInput"
                        @input="autoResizeInput($el)"
                        @keydown.enter.prevent="if (!$event.shiftKey) { sendMessage(); resetInputHeight($el); }"
                        rows="1"
                        placeholder="Ask assistant or upload a receipt..."
                        class="w-full resize-none overflow-y-auto max-h-[130px] py-2 px-3 text-xs sm:text-[13px] rounded-xl bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 border border-slate-200 dark:border-slate-800 focus:border-slate-400 dark:focus:border-slate-600 focus:bg-white dark:focus:bg-slate-950 focus:outline-none transition-all placeholder-slate-400 dark:placeholder-slate-500 scrollbar-thin chatbot-input-scroll"
                    ></textarea>
                </div>

                <!-- Send Button -->
                <button 
                    type="submit" 
                    :disabled="isLoading || (!userInput.trim() && !selectedFile)"
                    class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-2xs focus:outline-none shrink-0 cursor-pointer"
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

        <!-- Interactive Edit Details Modal (Option A: Clean Dual Dropdowns) -->
        <div 
            x-show="editModalOpen"
            x-cloak
            class="absolute inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex flex-col justify-end sm:justify-center p-3 sm:p-4"
        >
            <div 
                x-show="editModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[92%] overflow-hidden"
                @click.away="editModalOpen = false"
            >
                <!-- Modal Header -->
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </span>
                        <h4 class="text-xs font-semibold text-slate-900 dark:text-white">Edit Claim Details</h4>
                    </div>
                    <button 
                        type="button" 
                        @click="editModalOpen = false" 
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Modal Form Body -->
                <div class="p-3.5 space-y-2.5 overflow-y-auto smartclaim-chat-scroll text-xs">
                    <!-- Merchant -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Merchant / Peniaga</label>
                        <input 
                            type="text" 
                            x-model="draftClaim.merchant_name" 
                            placeholder="e.g. Petronas, McDonald's"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        >
                    </div>

                    <!-- Date & Amount (Row) -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Date / Tarikh</label>
                            <input 
                                type="date" 
                                x-model="draftClaim.date" 
                                class="w-full px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (RM)</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                x-model="draftClaim.amount" 
                                placeholder="0.00"
                                class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            >
                        </div>
                    </div>

                    <!-- Expense Category (Dropdown) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Expense Category</label>
                        <select 
                            x-model="draftClaim.category"
                            class="w-full px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        >
                            <template x-for="cat in categories" :key="cat">
                                <option :value="cat" x-text="cat" :selected="draftClaim.category === cat"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Vehicle Plate (Conditional on Fuel Category) -->
                    <div x-show="isFuelCategory(draftClaim.category)" x-cloak class="space-y-1.5 p-2 rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-semibold text-amber-900 dark:text-amber-300">
                                <i class="fa-solid fa-gas-pump mr-1"></i> Company Fleet Vehicle <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[9px] uppercase tracking-wider text-amber-700 dark:text-amber-400 font-bold bg-amber-100 dark:bg-amber-900/60 px-1.5 py-0.2 rounded">Required</span>
                        </div>
                        <select 
                            x-model="draftClaim.vehicle_plate"
                            class="w-full px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none"
                        >
                            <option value="">-- Select Company Fleet Vehicle --</option>
                            <template x-for="v in companyFleet" :key="v.plate_number">
                                <option :value="v.plate_number" x-text="v.plate_number + ' - ' + v.brand_model"></option>
                            </template>
                            <template x-for="v in personalVehicles" :key="'pers-' + v.plate_number">
                                <option :value="v.plate_number" x-text="'Personal: ' + v.plate_number + ' - ' + v.brand_model"></option>
                            </template>
                            <option value="__custom__">Other / Specify manual plate</option>
                        </select>
                        
                        <div x-show="draftClaim.vehicle_plate === '__custom__'" x-cloak class="pt-1">
                            <input 
                                type="text" 
                                x-model="draftClaim.custom_plate" 
                                placeholder="Enter plate number (e.g. JKE 1234)"
                                class="w-full px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs uppercase"
                            >
                        </div>
                    </div>

                    <!-- Purpose -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Business Purpose / Description</label>
                        <textarea 
                            x-model="draftClaim.description" 
                            rows="2"
                            placeholder="Brief reason for expense..."
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-3.5 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2 bg-slate-50 dark:bg-slate-950 shrink-0">
                    <button 
                        type="button" 
                        @click="editModalOpen = false" 
                        class="px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 text-xs font-medium transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="saveEditedDetails()" 
                        class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-check text-[10px]"></i>
                        <span>Save & Update Details</span>
                    </button>
                </div>
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
        sessionReceiptPreview: null,
        isLoading: false,
        role: config.role || 'staff',
        userName: config.userName || 'User',
        roleBadge: config.roleBadge || 'Assistant',
        csrfToken: config.csrfToken,
        messageUrl: config.messageUrl,
        historyUrl: config.historyUrl,
        resetUrl: config.resetUrl,
        quickChips: [],
        categories: config.categories || [],
        companyFleet: config.companyFleet || [],
        personalVehicles: config.personalVehicles || [],
        editModalOpen: false,
        draftClaim: {
            merchant_name: '',
            date: '',
            amount: '',
            category: '',
            vehicle_plate: '',
            custom_plate: '',
            description: ''
        },

        init() {
            this.setupQuickChips();

            const savedIsOpen = sessionStorage.getItem('smartclaim_chatbot_open');
            if (savedIsOpen === 'true') {
                this.isOpen = true;
            }

            const savedUnread = parseInt(sessionStorage.getItem('smartclaim_chatbot_unread') || '0', 10);
            this.unreadCount = isNaN(savedUnread) ? 0 : savedUnread;

            this.loadHistory();

            this.$watch('userInput', () => {
                this.$nextTick(() => {
                    this.autoResizeInput(this.$refs.messageInput);
                });
            });
        },

        async loadHistory() {
            try {
                if (!this.historyUrl) {
                    this.setupInitialGreeting();
                    return;
                }

                const res = await fetch(this.historyUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    this.setupInitialGreeting();
                    return;
                }

                const data = await res.json();
                if (data && data.status === 'success' && Array.isArray(data.messages) && data.messages.length > 0) {
                    this.messages = data.messages;
                    if (data.session_receipt_preview) {
                        this.sessionReceiptPreview = data.session_receipt_preview;
                    }
                    this.scrollToBottom();
                } else {
                    this.setupInitialGreeting();
                }
            } catch (e) {
                this.setupInitialGreeting();
            }
        },

        setupInitialGreeting() {
            let greetingText = '';
            if (this.role === 'manager') {
                greetingText = `Hello ${this.userName}. I can evaluate budget limits and grant executive approvals on audited claims.\n\n⚡ Action: List recent claims awaiting manager approval\n⚡ Action: Check claim status for #CLM-1`;
            } else if (this.role === 'finance' || this.role === 'fin') {
                greetingText = `Hello ${this.userName}. I can inspect claim vouchers, verify receipt hashes, and pre-approve pending claims.\n\n⚡ Action: Show recent pending claims awaiting audit\n⚡ Action: Check details for claim #CLM-1`;
            } else {
                greetingText = `Hello ${this.userName}. You can attach an expense receipt image, calculate travel mileage, or check your claim status. What would you like to do?\n\n⚡ Action: What is the status of my recent claims?\n⚡ Action: I want to submit an expense receipt claim\n⚡ Action: I want to claim mileage allowance`;
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
                    { label: 'Receipt Claim', prompt: 'I want to submit an expense receipt claim', icon: 'fa-solid fa-receipt' },
                    { label: 'Mileage Claim', prompt: 'I want to claim mileage allowance', icon: 'fa-solid fa-route' }
                ];
            }
        },

        autoResizeInput(el) {
            if (!el) return;
            el.style.height = 'auto';
            const maxHeight = 130; // Maximum height in pixels (~5 lines ceiling)
            el.style.height = Math.min(el.scrollHeight, maxHeight) + 'px';
        },

        resetInputHeight(el) {
            if (el) {
                el.style.height = 'auto';
            }
        },

        toggleOpen() {
            this.isOpen = !this.isOpen;
            sessionStorage.setItem('smartclaim_chatbot_open', this.isOpen ? 'true' : 'false');
            if (this.isOpen) {
                this.unreadCount = 0;
                sessionStorage.setItem('smartclaim_chatbot_unread', '0');
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.messageInput) {
                        this.$refs.messageInput.focus();
                    }
                });
            }
        },

        closeChat() {
            this.isOpen = false;
            sessionStorage.setItem('smartclaim_chatbot_open', 'false');
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
                    this.sessionReceiptPreview = e.target.result;
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
            if (previewSnapshot) {
                this.sessionReceiptPreview = previewSnapshot;
            }

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
            this.resetInputHeight(this.$refs.messageInput);
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
                    sessionStorage.setItem('smartclaim_chatbot_unread', String(this.unreadCount));
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

            sessionStorage.removeItem('smartclaim_chatbot_open');
            sessionStorage.removeItem('smartclaim_chatbot_unread');
            this.setupInitialGreeting();
            this.removeSelectedFile();
            this.sessionReceiptPreview = null;
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
        },

        formatMarkdown(rawText) {
            if (!rawText) return '';

            let safe = String(rawText)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Linkify Claim IDs (e.g. CLM-298374, #CLM-3) into clickable badges
            safe = safe.replace(/(?:#|\b)CLM-?(\d+)\b/gi, (match, id) => {
                return `<a href="{{ route('claims.history') }}?claim_id=${id}" class="inline-flex items-center gap-1 font-mono font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/40 px-1.5 py-0.5 rounded border border-blue-200/80 dark:border-blue-800 transition text-[11px] underline underline-offset-2" title="Click to view voucher details in history"><span>CLM-${id}</span> <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a>`;
            });

            // Bold
            safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Italic
            safe = safe.replace(/(^|[^\*])\*([^\*]+)\*([^\*]|$)/g, '$1<em>$2</em>$3');

            // Headings
            safe = safe.replace(/^### (.*$)/gim, '<p class="font-bold text-slate-900 dark:text-white mt-1.5 mb-0.5">$1</p>');
            safe = safe.replace(/^## (.*$)/gim, '<p class="font-bold text-slate-900 dark:text-white mt-2 mb-1">$1</p>');
            safe = safe.replace(/^# (.*$)/gim, '<p class="font-bold text-slate-900 dark:text-white mt-2 mb-1">$1</p>');

            let lines = safe.split('\n');
            let inList = false;
            let formatted = [];

            for (let line of lines) {
                let trimmed = line.trim();

                // Actions and suggestions are extracted and rendered as interactive buttons below
                if (/^(?:⚡\s*(?:Action|Tindakan):|💡\s*(?:Suggestion|Cadangan):)/i.test(trimmed)) {
                    continue;
                }

                // Sanitize and flatten any Markdown pipe table lines (| Col | Col |)
                if (/^\|(?:\s*:?-+:?\s*\|)+$/.test(trimmed)) {
                    continue; // Skip table header separator |---|---|
                }
                if (trimmed.startsWith('|') && trimmed.endsWith('|') && trimmed.length > 2) {
                    let cells = trimmed.slice(1, -1).split('|').map(c => c.trim()).filter(c => c.length > 0);
                    // Skip table header label rows
                    if (cells.length > 0 && /^(?:id|no|claim|amount|jumlah|date|tarikh|merchant|peniaga|status)/i.test(cells[0])) {
                        continue;
                    }
                    if (cells.length > 0) {
                        trimmed = '* ' + cells.join(' — ');
                    }
                }

                if (trimmed.startsWith('* ') || trimmed.startsWith('- ')) {
                    if (!inList) {
                        inList = true;
                        formatted.push('<ul class="my-1 space-y-0.5 pl-4 list-disc list-outside">');
                    }
                    formatted.push('<li class="leading-snug">' + trimmed.substring(2) + '</li>');
                } else {
                    if (inList) {
                        inList = false;
                        formatted.push('</ul>');
                    }
                    if (trimmed.length > 0) {
                        if (/^(Merchant|Date|Amount|Category|Purpose|Vehicle Plate|Peniaga|Tarikh|Jumlah|Kategori|Tujuan|Plat Kenderaan):/i.test(trimmed)) {
                            formatted.push('<p class="font-mono text-[11px] leading-tight py-0.5 text-slate-700 dark:text-slate-300 font-medium">' + trimmed + '</p>');
                        } else {
                            formatted.push('<p class="mb-1 last:mb-0 leading-normal">' + trimmed + '</p>');
                        }
                    }
                }
            }

            if (inList) {
                formatted.push('</ul>');
            }

            return formatted.join('');
        },

        getActions(text) {
            if (!text) return [];
            const actions = [];
            const lines = String(text).split('\n');
            for (let line of lines) {
                const trimmed = line.trim();
                const actionMatch = trimmed.match(/^⚡\s*(?:Action|Tindakan):\s*(.+)$/i);
                if (actionMatch) {
                    let act = this.normalizeActionText(actionMatch[1].trim());
                    if (act) actions.push(act);
                    continue;
                }

                const suggMatch = trimmed.match(/^💡\s*(?:Suggestion|Cadangan):\s*(.+)$/i);
                if (suggMatch) {
                    let act = this.normalizeActionText(suggMatch[1].trim());
                    if (act) actions.push(act);
                }
            }
            return actions;
        },

        normalizeActionText(text) {
            if (!text) return '';
            let str = text.trim();

            if (str.endsWith('?')) {
                if (/^would you like (?:me )?to submit a new (?:receipt )?claim/i.test(str) || /^do you want to submit (?:a )?new claim/i.test(str) || /^do you have a receipt ready to submit/i.test(str)) {
                    return 'I want to submit a new receipt claim';
                }
                if (/^would you like (?:me )?to check (?:the status of )?(?:your )?recent claims/i.test(str)) {
                    return 'What is the status of my recent claims?';
                }
                if (/^would you like (?:me )?to check your (?:remaining )?monthly budget/i.test(str)) {
                    return 'Check my monthly budget balance';
                }
                if (/^adakah anda (?:ingin|mahu) (?:membuat|menghantar) tuntutan baharu/i.test(str) || /^adakah anda mempunyai resit/i.test(str)) {
                    return 'Saya mahu hantar tuntutan resit baharu';
                }
                if (/^adakah anda (?:ingin|mahu) (?:saya )?menyemak (?:status )?tuntutan/i.test(str)) {
                    return 'Semak status tuntutan terkini saya';
                }
                if (/^adakah anda (?:ingin|mahu) (?:saya )?menyemak (?:baki )?bajet/i.test(str)) {
                    return 'Semak baki had bajet bulanan saya';
                }

                str = str.replace(/^(Would you like to |Do you want to |Would you like me to )/i, '')
                         .replace(/^(Adakah anda ingin |Adakah anda mahu )/i, '')
                         .replace(/\?+$/, '')
                         .trim();
                if (str.length > 0) {
                    str = str.charAt(0).toUpperCase() + str.slice(1);
                }
            }

            return str;
        },

        hasStructuredDetails(text) {
            if (!text) return false;
            return /(?:Merchant|Peniaga|Kedai):\s*[^\n]+/i.test(text) &&
                   /(?:Amount|Jumlah):\s*RM\s*[\d\.]+/i.test(text);
        },

        isFuelCategory(cat) {
            return /fuel|petrol/i.test(cat || '');
        },

        isFuelMissingPlate(text) {
            if (!text) return false;
            const isFuel = /Category:\s*Fuel/i.test(text) || /Kategori:\s*(?:Fuel|Petrol)/i.test(text);
            if (!isFuel) return false;
            const plateMatch = text.match(/(?:Vehicle Plate|Plat Kenderaan):\s*([^\n\r]+)/i);
            if (!plateMatch) return true;
            const plateVal = plateMatch[1].trim();
            return plateVal.toLowerCase() === 'unspecified' || plateVal === '-' || plateVal === '';
        },

        openEditModal(text) {
            let merchant = '';
            let date = '';
            let amount = '';
            let category = '';
            let description = '';
            let vehiclePlate = '';

            const lines = String(text).split('\n');
            for (const line of lines) {
                const mMerch = line.match(/^(?:Merchant|Peniaga):\s*(.+)$/i);
                if (mMerch) merchant = mMerch[1].trim();

                const mDate = line.match(/^(?:Date|Tarikh):\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i);
                if (mDate) date = mDate[1].trim();

                const mAmt = line.match(/^(?:Amount|Jumlah):\s*(?:RM\s*)?([0-9]+(?:\.[0-9]+)?)/i);
                if (mAmt) amount = mAmt[1].trim();

                const mCat = line.match(/^(?:Category|Kategori):\s*(.+)$/i);
                if (mCat) category = mCat[1].trim();

                const mDesc = line.match(/^(?:Purpose|Tujuan):\s*(.+)$/i);
                if (mDesc) description = mDesc[1].trim();

                const mPlate = line.match(/^(?:Vehicle Plate|Plat Kenderaan):\s*(.+)$/i);
                if (mPlate) vehiclePlate = mPlate[1].trim();
            }

            let matchedCat = this.categories[0] || 'Staff Operational Meals';
            if (category) {
                const lowerCat = category.toLowerCase();
                const found = this.categories.find(c => c.toLowerCase() === lowerCat || c.toLowerCase().includes(lowerCat) || lowerCat.includes(c.toLowerCase()));
                if (found) matchedCat = found;
                else if (lowerCat.includes('fuel') || lowerCat.includes('petrol')) matchedCat = 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)';
                else if (lowerCat.includes('meal') || lowerCat.includes('food')) matchedCat = 'Staff Operational Meals';
                else if (lowerCat.includes('pantry') || lowerCat.includes('grocer')) matchedCat = 'Office Pantry & Amenities (Groceries, Supplies)';
                else if (lowerCat.includes('supplies') || lowerCat.includes('stationery')) matchedCat = 'Office Supplies & Stationery';
                else if (lowerCat.includes('hardware') || lowerCat.includes('tools')) matchedCat = 'Site Tools & Hardware (Mr. DIY, Tools, Repairs)';
                else if (lowerCat.includes('transport') || lowerCat.includes('toll')) matchedCat = 'Transportation & Toll';
                else if (lowerCat.includes('travel') || lowerCat.includes('lodging')) matchedCat = 'Travel & Lodging';
            }

            let isCustom = false;
            let customPlate = '';
            if (vehiclePlate && vehiclePlate.toLowerCase() !== 'unspecified' && vehiclePlate !== '-') {
                const inFleet = this.companyFleet.some(v => v.plate_number.toUpperCase() === vehiclePlate.toUpperCase());
                const inPersonal = this.personalVehicles.some(v => v.plate_number.toUpperCase() === vehiclePlate.toUpperCase());
                if (!inFleet && !inPersonal) {
                    isCustom = true;
                    customPlate = vehiclePlate;
                    vehiclePlate = '__custom__';
                }
            } else {
                vehiclePlate = '';
            }

            this.draftClaim = {
                merchant_name: merchant,
                date: date || new Date().toISOString().split('T')[0],
                amount: amount,
                category: matchedCat,
                vehicle_plate: vehiclePlate,
                custom_plate: customPlate,
                description: description || 'Expense submitted via SmartClaim AI Assistant',
            };

            this.editModalOpen = true;
        },

        editDetails(text) {
            this.openEditModal(text);
        },

        saveEditedDetails() {
            this.editModalOpen = false;
            let plate = this.draftClaim.vehicle_plate;
            if (plate === '__custom__') {
                plate = (this.draftClaim.custom_plate || '').trim();
            }
            let message = `Here are my updated claim details for review:\n` +
                `Merchant: ${this.draftClaim.merchant_name || 'Merchant'}\n` +
                `Date: ${this.draftClaim.date || ''}\n` +
                `Amount: RM ${parseFloat(this.draftClaim.amount || 0).toFixed(2)}\n` +
                `Category: ${this.draftClaim.category || 'Office Supplies'}\n` +
                `Purpose: ${this.draftClaim.description || 'Expense claim'}`;
            if (this.isFuelCategory(this.draftClaim.category)) {
                message += `\nVehicle Plate: ${plate || 'Unspecified'}`;
            }
            this.userInput = message;
            this.sendMessage();
        }
    };
}
</script>
