@extends('layouts.app')

@section('body')
<div class="flex min-h-screen flex-col lg:flex-row">
    @include('layouts.partials.finance-sidebar')

    <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        @yield('content')
    </main>
</div>
@endsection
