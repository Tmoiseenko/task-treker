@extends('layouts.app')

@section('title', 'Уведомления')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Уведомления</h1>
                <p class="text-sm text-gray-600 mt-1">
                    @php
                        $unreadCount = $notifications->where('read_at', null)->count();
                    @endphp
                    @if($unreadCount > 0)
                        У вас {{ $unreadCount }} {{ $unreadCount === 1 ? 'непрочитанное уведомление' : 'непрочитанных уведомлений' }}
                    @else
                        Все уведомления прочитаны
                    @endif
                </p>
            </div>
            
            @if($unreadCount > 0)
                <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                        Отметить все как прочитанные
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Notifications List -->
    @if($notifications->isEmpty())
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Нет уведомлений</h3>
            <p class="mt-2 text-sm text-gray-500">У вас пока нет уведомлений. Они появятся здесь, когда произойдут важные события.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 {{ $notification->read_at ? '' : 'ring-2 ring-blue-500 ring-opacity-50' }}">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <!-- Title and Badge -->
                                <div class="flex items-center gap-3 mb-2">
                                    @if(!$notification->read_at)
                                        <span class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full"></span>
                                    @endif
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $notification->title }}
                                    </h3>
                                    @if(!$notification->read_at)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Новое
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Message -->
                                <p class="text-gray-700 mb-3 leading-relaxed">
                                    {{ $notification->message }}
                                </p>
                                
                                <!-- Footer with timestamp and actions -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        
                                        @if(isset($notification->data['task_id']))
                                            <a href="{{ route('tasks.show', $notification->data['task_id']) }}" 
                                               class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                                                Перейти к задаче
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                    
                                    @if(!$notification->read_at)
                                        <form action="{{ route('notifications.mark-as-read', $notification) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                Отметить прочитанным
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-sm text-gray-400 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Прочитано
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
