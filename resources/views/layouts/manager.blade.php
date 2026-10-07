@extends('layouts.app')

@section('body')
<div class="flex min-h-screen flex-col lg:flex-row" x-data="{ isAuditingOpen: false, isAdminOpen: false }">
    @include('layouts.partials.manager-sidebar')

    <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-28 md:pb-8">
        @yield('content')
    </main>

    @include('layouts.partials.bottom-nav')
</div>
@endsection
