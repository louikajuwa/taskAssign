@extends('layouts.app')

@section('title', 'Sign in - TaskAssign')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-gray-200 w-full max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500 text-white" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">TaskAssign</h1>
            <p class="text-sm text-gray-500 mt-1">Sign in to your account</p>
        </div>

        @if($errors->any())
        <div role="alert" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            @foreach($errors->all() as $error)
            <p class="text-sm text-red-600">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="/login" id="login-form">
            @csrf
            <div class="mb-4">
                <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            </div>

            <div class="mb-4">
                <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="login-password" type="password" name="password" required autocomplete="current-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            </div>

            <div class="flex items-center mb-6">
                <input type="checkbox" name="remember" id="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
            </div>

            <button type="submit" id="login-submit"
                class="w-full py-2 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                Sign In
            </button>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('login-form').addEventListener('submit', function () {
    var b = document.getElementById('login-submit');
    // Defer so the form value still submits; button itself has no name/value.
    setTimeout(function () { b.disabled = true; b.textContent = 'Signing in…'; }, 0);
});
</script>
@endpush
@endsection
