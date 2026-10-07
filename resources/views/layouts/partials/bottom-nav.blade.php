@auth
@php
    $role = strtolower(trim(auth()->user()->role ?? ''));
    if ($role === 'manager') {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'manager.dashboard', 'icon' => 'fa-solid fa-chart-pie', 'active' => request()->is('manager/dashboard*') || request()->routeIs('manager.dashboard*')],
            ['label' => 'Verification', 'route' => 'manager.verification', 'icon' => 'fa-solid fa-clipboard-check', 'active' => request()->is('manager/verification*') || request()->routeIs('manager.verification*')],
            ['label' => 'Fleet', 'route' => 'manager.vehicles', 'icon' => 'fa-solid fa-car', 'active' => request()->is('manager/vehicles*') || request()->is('manager/vehicle*') || request()->routeIs('manager.vehicles*') || request()->routeIs('manager.vehicle_history*')],
            ['label' => 'Advances', 'route' => 'manager.advances', 'icon' => 'fa-solid fa-money-bill-transfer', 'active' => request()->is('manager/cash-advances*') || request()->is('manager/advances*') || request()->routeIs('manager.advances*')],
            ['label' => 'Profile', 'route' => 'profile.index', 'icon' => 'fa-solid fa-user-gear', 'active' => request()->is('profile*') || request()->is('manager/profile*') || request()->routeIs('manager.profile*') || request()->routeIs('profile*')],
        ];
    } elseif (in_array($role, ['finance', 'fin'])) {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'finance.dashboard', 'icon' => 'fa-solid fa-chart-line', 'active' => request()->is('finance/dashboard*') || request()->routeIs('finance.dashboard*')],
            ['label' => 'Auditing', 'route' => 'finance.auditing', 'icon' => 'fa-solid fa-file-invoice-dollar', 'active' => request()->is('finance/auditing*') || request()->routeIs('finance.auditing*')],
            ['label' => 'Disbursement', 'route' => 'finance.disbursement', 'icon' => 'fa-solid fa-wallet', 'active' => request()->is('finance/disbursement*') || request()->routeIs('finance.disbursement*')],
            ['label' => 'Settlement', 'route' => 'finance.cash-advances.index', 'icon' => 'fa-solid fa-scale-balanced', 'active' => request()->is('finance/cash-advances*') || request()->routeIs('finance.cash-advances*')],
            ['label' => 'Profile', 'route' => 'profile.index', 'icon' => 'fa-solid fa-user-shield', 'active' => request()->is('profile*') || request()->is('finance/profile*') || request()->routeIs('finance.profile*') || request()->routeIs('profile*')],
        ];
    } else {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-solid fa-gauge-high', 'active' => request()->is('dashboard*') || request()->routeIs('dashboard*')],
            ['label' => 'New Claim', 'route' => 'claims.create', 'icon' => 'fa-solid fa-plus-circle', 'active' => request()->is('claims/create*') || request()->routeIs('claims.create*'), 'highlight' => true],
            ['label' => 'History', 'route' => 'claims.history', 'icon' => 'fa-solid fa-clock-rotate-left', 'active' => request()->is('claims/history*') || request()->is('claims/*/edit') || request()->routeIs('claims.history*') || request()->routeIs('claims.edit*')],
            ['label' => 'Advances', 'route' => 'advances.index', 'icon' => 'fa-solid fa-hand-holding-dollar', 'active' => request()->is('advances*') || request()->routeIs('advances*')],
            ['label' => 'Profile', 'route' => 'profile.index', 'icon' => 'fa-solid fa-user', 'active' => request()->is('profile*') || request()->routeIs('profile*')],
        ];
    }
@endphp

<!-- Reusable Role-Based Mobile Bottom Navigation Bar -->
<nav aria-label="Mobile Navigation" class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-2 flex items-center justify-around shadow-lg">
    @foreach ($navItems as $item)
        <a href="{{ route($item['route']) }}" 
           class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-150 {{ $item['active'] ? 'text-blue-600 font-bold' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
            <div class="relative flex items-center justify-center">
                @if(!empty($item['highlight']) && !$item['active'])
                    <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-2xs">
                        <i class="{{ $item['icon'] }} text-base"></i>
                    </span>
                @elseif(!empty($item['highlight']) && $item['active'])
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-xs">
                        <i class="{{ $item['icon'] }} text-base"></i>
                    </span>
                @else
                    <i class="{{ $item['icon'] }} text-base"></i>
                @endif
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 leading-none">{{ $item['label'] }}</span>
            @if ($item['active'])
                <span class="w-1 h-1 rounded-full bg-blue-600 mt-0.5"></span>
            @else
                <span class="w-1 h-1 rounded-full bg-transparent mt-0.5"></span>
            @endif
        </a>
    @endforeach
</nav>
@endauth
