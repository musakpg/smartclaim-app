@extends('layouts.app')

@section('body')
<div class="flex min-h-screen flex-col lg:flex-row" x-data="{ isAuditingOpen: false, isAdminOpen: false }">
    @include('layouts.partials.manager-sidebar')

    <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-x-hidden">
        @yield('content')
    </main>
</div>
@endsection
