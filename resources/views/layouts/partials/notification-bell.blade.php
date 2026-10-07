<div class="relative" x-data="notificationBell()" x-init="init()" @click.away="isOpen = false">
    <!-- Bell Button -->
    <button type="button" @click="toggleDropdown()"
        class="relative w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 transition cursor-pointer shadow-3xs">
        <i class="fa-solid fa-bell text-sm"></i>

        <!-- Red Badge Counter -->
        <span x-show="unreadCount > 0" x-cloak
            class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white font-mono text-[9px] font-black rounded-full flex items-center justify-center animate-pulse"
            x-text="unreadCount > 9 ? '9+' : unreadCount">
        </span>
    </button>

    <!-- Notification Dropdown Menu -->
    <div x-show="isOpen" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-100 z-50 overflow-hidden">

        <!-- Header -->
        <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-inbox text-slate-400"></i> Notifications
            </span>
            <button type="button" @click="markAsRead()" x-show="unreadCount > 0"
                class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 cursor-pointer">
                Mark all read
            </button>
        </div>

        <!-- Manual Sync & Register Device Banner (Always Active) -->
        <div class="p-2.5 bg-blue-50/80 border-b border-blue-100 flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-mobile-screen text-blue-600"></i>
                <span class="text-[11px] font-bold text-blue-900">Phone Alert Sync</span>
            </div>
            <button type="button" @click="requestWebPushPermission()"
                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-[10px] shadow-3xs cursor-pointer transition flex items-center gap-1">
                <i class="fa-solid fa-rotate"></i> Sync Device
            </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-slate-50 text-xs">
            <template x-for="item in notifications" :key="item.id">
                <a :href="item.target_url || '#'" class="block p-3.5 hover:bg-slate-50/80 transition"
                    :class="!item.is_read ? 'bg-indigo-50/30' : ''">
                    <div class="flex items-start gap-2.5">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5" :class="{
                                'bg-emerald-100 text-emerald-600': item.type === 'success',
                                'bg-rose-100 text-rose-600': item.type === 'danger',
                                'bg-amber-100 text-amber-600': item.type === 'warning',
                                'bg-blue-100 text-blue-600': item.type === 'info'
                            }">
                            <i class="fa-solid text-[10px]" :class="{
                                    'fa-circle-check': item.type === 'success',
                                    'fa-circle-xmark': item.type === 'danger',
                                    'fa-triangle-exclamation': item.type === 'warning',
                                    'fa-circle-info': item.type === 'info'
                                }"></i>
                        </div>
                        <div class="flex-1 space-y-0.5">
                            <p class="font-bold text-slate-900 leading-tight" x-text="item.title"></p>
                            <p class="text-slate-500 text-[11px] leading-snug" x-text="item.message"></p>
                            <span class="text-[9px] text-slate-400 font-mono block pt-0.5"
                                x-text="new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                        </div>
                    </div>
                </a>
            </template>

            <template x-if="notifications.length === 0">
                <div class="p-8 text-center text-slate-400 font-medium">
                    <i class="fa-regular fa-bell-slash text-xl block mb-1 text-slate-300"></i>
                    No notifications yet.
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function notificationBell() {
        return {
            isOpen: false,
            unreadCount: 0,
            notifications: [],

            init() {
                this.loadNotifications();
                setInterval(() => this.loadNotifications(), 30000);

                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/sw.js').catch(err => {
                        console.warn('SW register fail:', err);
                    });
                }
            },

            loadNotifications() {
                fetch('{{ route("notifications.latest") }}')
                    .then(res => res.json())
                    .then(data => {
                        this.unreadCount = data.unread_count || 0;
                        this.notifications = data.notifications || [];
                    })
                    .catch(() => { });
            },

            toggleDropdown() {
                this.isOpen = !this.isOpen;
            },

            markAsRead() {
                fetch('{{ route("notifications.mark_read") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    this.unreadCount = 0;
                    this.notifications.forEach(n => n.is_read = true);
                });
            },

            // Direct sync with native alert support
            async requestWebPushPermission() {
                if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                    alert('Push notifications are not supported on this browser context.');
                    return;
                }

                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        alert('Permission not granted: ' + permission);
                        return;
                    }

                    // Register and wait for Service Worker activation
                    const reg = await navigator.serviceWorker.ready;

                    // Clear old invalid subscription
                    const existingSub = await reg.pushManager.getSubscription();
                    if (existingSub) {
                        await existingSub.unsubscribe();
                    }

                    const vapidPublicKey = "{{ config('webpush.vapid.public_key') ?? env('VAPID_PUBLIC_KEY') }}";
                    if (!vapidPublicKey || vapidPublicKey === '') {
                        alert('Error: VAPID_PUBLIC_KEY is missing in Laravel config.');
                        return;
                    }

                    const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);
                    const newSubscription = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: convertedVapidKey
                    });

                    // Send fresh token to server
                    const response = await fetch("{{ route('push.subscribe') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(newSubscription)
                    });

                    const resData = await response.json();
                    if (resData.success) {
                        alert('BERJAYA: Telefon anda sudah berdaftar untuk notifikasi skrin!');
                    } else {
                        alert('Gagal daftar ke server: ' + (resData.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Push registration error:', error);
                    alert('Ralat Pendaftaran Push: ' + error.message);
                }
            }
        };
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
</script>
