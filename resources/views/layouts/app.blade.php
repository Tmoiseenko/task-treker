<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('tasks.index') }}" class="text-xl font-bold text-gray-800">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('tasks.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('tasks.*') && !request()->routeIs('kanban.*') && !request()->routeIs('calendar.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Задачи
                            </a>
                            <a href="{{ route('kanban.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('kanban.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Kanban
                            </a>
                            <a href="{{ route('calendar.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('calendar.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Календарь
                            </a>
                            <a href="{{ route('documents.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('documents.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                База знаний
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">
                        @auth
                            <!-- Notification Dropdown -->
                            @php
                                $unreadNotifications = auth()->user()
                                    ->notifications()
                                    ->whereNull('read_at')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(5)
                                    ->get();
                                $unreadCount = auth()->user()
                                    ->notifications()
                                    ->whereNull('read_at')
                                    ->count();
                            @endphp
                            
                            <x-notification-dropdown 
                                :unreadCount="$unreadCount" 
                                :notifications="$unreadNotifications" 
                            />
                            
                            <span class="text-sm text-gray-700">{{ auth()->user()->name }}</span>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
