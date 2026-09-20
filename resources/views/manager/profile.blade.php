<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Manager Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">

                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900">My Profile</h1>
                    <p class="text-xs md:text-sm text-slate-500">Update your account settings and personal details.</p>
                </div>

                <div class="bg-white p-4 md:p-8 rounded-3xl border border-slate-200/60 shadow-2xs">
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 text-xs font-semibold">
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Full
                                    Name</label>
                                <input type="text" value="{{ Auth::user()->name ?? 'Executive Manager' }}"
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none text-slate-800 font-bold text-xs md:text-sm">
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Email
                                    Address</label>
                                <input type="email" value="{{ Auth::user()->email ?? 'manager@aeroart.com' }}"
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none font-mono text-slate-600 text-xs md:text-sm">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-[#0f172a] hover:bg-slate-800 text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer">Save
                            Changes</button>
                    </form>
                </div>

                <div class="bg-white p-4 md:p-8 rounded-3xl border border-slate-200/60 shadow-2xs">
                    <h2 class="text-base md:text-lg font-bold text-slate-900 mb-4 md:mb-6">Security & Encryption</h2>
                    <form action="#" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-2 text-xs font-semibold">
                            <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">New
                                Secret Password</label>
                            <input type="password" placeholder="••••••••"
                                class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-xs md:text-sm font-mono">
                        </div>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer">Update
                            Password</button>
                    </form>
                </div>

            </div>
        </main>
    </div>
</body>

</html>