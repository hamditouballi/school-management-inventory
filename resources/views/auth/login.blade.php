@extends('layouts.app')

@section('title', __('messages.login'))

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-center mb-6">{{ __('messages.login') }}</h2>
        
        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                <input type="text" name="username" id="username" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 @error('username') border-red-500 @enderror"
                    value="{{ old('username') }}" required autofocus>
                @error('username')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                <input type="password" name="password" id="password" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 @error('password') border-red-500 @enderror"
                    required>
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-semibold">
                {{ __('messages.login') }}
            </button>
        </form>

        <div class="mt-6 text-sm text-gray-600">
            <p class="font-semibold mb-2">Demo Credentials:</p>
            <ul class="space-y-1">
                <li><strong>HR Manager:</strong> hr_manager / password</li>
                <li><strong>Stock Manager:</strong> stock_manager / password</li>
                <li><strong>Finance Manager:</strong> finance_manager / password</li>
                <li><strong>Teacher:</strong> teacher_nursery / password</li>
            </ul>
        </div>
    </div>
</div>
@endsection
