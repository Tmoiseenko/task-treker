@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Dashboard</h1>

    <!-- General Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Active Projects -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Active Projects</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $statistics['active_projects_count'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Current Month Hours -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Hours This Month</p>
                    <p class="text-3xl font-bold text-green-600">{{ $statistics['current_month_hours'] }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Current Month Payments -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Payments This Month</p>
                    <p class="text-3xl font-bold text-purple-600">${{ number_format($statistics['current_month_payments'], 2) }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks by Status -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Tasks by Status</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @foreach($statistics['tasks_by_status'] as $status => $count)
                <div class="text-center p-4 rounded-lg {{ $status === 'done' ? 'bg-green-50' : ($status === 'in_progress' ? 'bg-blue-50' : ($status === 'test_failed' ? 'bg-red-50' : 'bg-gray-50')) }}">
                    <p class="text-2xl font-bold {{ $status === 'done' ? 'text-green-600' : ($status === 'in_progress' ? 'text-blue-600' : ($status === 'test_failed' ? 'text-red-600' : 'text-gray-600')) }}">{{ $count }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ ucfirst(str_replace('_', ' ', $status)) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tasks by Priority -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Tasks by Priority</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($statistics['tasks_by_priority'] as $priority => $count)
                <div class="text-center p-4 rounded-lg {{ $priority === 'high' ? 'bg-red-50' : ($priority === 'medium' ? 'bg-yellow-50' : ($priority === 'low' ? 'bg-green-50' : 'bg-gray-50')) }}">
                    <p class="text-2xl font-bold {{ $priority === 'high' ? 'text-red-600' : ($priority === 'medium' ? 'text-yellow-600' : ($priority === 'low' ? 'text-green-600' : 'text-gray-600')) }}">{{ $count }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ ucfirst($priority) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Top Specialists -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Top Specialists by Completed Tasks</h2>
        @if(count($statistics['top_specialists']) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Tasks</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($statistics['top_specialists'] as $index => $specialist)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-bold {{ $index === 0 ? 'text-yellow-500' : ($index === 1 ? 'text-gray-400' : ($index === 2 ? 'text-orange-600' : 'text-gray-600')) }}">
                                        #{{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $specialist['name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $specialist['completed_tasks'] }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500">No completed tasks yet.</p>
        @endif
    </div>

    <!-- Personal Statistics (for specialists) -->
    @if(isset($statistics['personal_stats']))
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow p-6 text-white">
            <h2 class="text-xl font-bold mb-4">Your Personal Statistics</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $statistics['personal_stats']['assigned_tasks'] }}</p>
                    <p class="text-sm opacity-90 mt-1">Assigned Tasks</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $statistics['personal_stats']['in_progress_tasks'] }}</p>
                    <p class="text-sm opacity-90 mt-1">In Progress</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $statistics['personal_stats']['completed_tasks'] }}</p>
                    <p class="text-sm opacity-90 mt-1">Completed</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ $statistics['personal_stats']['monthly_hours'] }}</p>
                    <p class="text-sm opacity-90 mt-1">Hours This Month</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">${{ number_format($statistics['personal_stats']['monthly_payment'], 2) }}</p>
                    <p class="text-sm opacity-90 mt-1">Payment This Month</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
