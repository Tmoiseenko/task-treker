@extends('layouts.app')

@section('title', 'Отчет по времени - ' . $project->name)

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Отчет по времени на проект</h1>
    <p class="text-gray-600">{{ $project->name }}</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Всего часов</div>
        <div class="text-3xl font-bold text-gray-900">{{ number_format($report['total_hours'], 2) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Общая стоимость</div>
        <div class="text-3xl font-bold text-indigo-600">${{ number_format($report['total_cost'], 2) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Задач</div>
        <div class="text-3xl font-bold text-gray-900">{{ count($report['by_task']) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Специалистов</div>
        <div class="text-3xl font-bold text-gray-900">{{ count($report['by_user']) }}</div>
    </div>
</div>

<!-- Time by User -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Время по специалистам</h2>
    </div>
    
    @if(count($report['by_user']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Специалист
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Часов
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ставка
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Стоимость
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($report['by_user'] as $userData)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $userData['user_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($userData['total_hours'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">${{ number_format($userData['hourly_rate'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">${{ number_format($userData['total_cost'], 2) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            Итого
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            {{ number_format($report['total_hours'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                            ${{ number_format($report['total_cost'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Записи времени отсутствуют</p>
            <p class="text-sm mt-2">Для этого проекта еще не было добавлено записей времени</p>
        </div>
    @endif
</div>

<!-- Time by Task -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Время по задачам</h2>
    </div>
    
    @if(count($report['by_task']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Задача
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Статус
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Часов
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Стоимость
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Действия
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($report['by_task'] as $taskData)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $taskData['task_title'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    N/A
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($taskData['total_hours'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">${{ number_format($taskData['total_cost'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('reports.task-time', $taskData['task_id']) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Подробнее →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Задачи отсутствуют</p>
        </div>
    @endif
</div>
@endsection
