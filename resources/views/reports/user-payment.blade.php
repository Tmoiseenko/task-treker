@extends('layouts.app')

@section('title', 'Отчет по выплатам - ' . $user->name)

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Отчет по выплатам</h1>
    <p class="text-gray-600">{{ $user->name }}</p>
</div>

<!-- Date Range Filter -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form method="GET" action="{{ route('reports.user-payment', $user) }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">Дата начала</label>
                <input type="date" name="from" id="from" value="{{ $from->format('Y-m-d') }}" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Дата окончания</label>
                <input type="date" name="to" id="to" value="{{ $to->format('Y-m-d') }}" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                    Применить фильтр
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Всего часов</div>
        <div class="text-3xl font-bold text-gray-900">{{ number_format($report['total_hours'], 2) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Ставка</div>
        <div class="text-3xl font-bold text-gray-900">${{ number_format($report['hourly_rate'], 2) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">К выплате</div>
        <div class="text-3xl font-bold text-indigo-600">${{ number_format($report['total_payment'], 2) }}</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm font-medium text-gray-500 mb-1">Проектов</div>
        <div class="text-3xl font-bold text-gray-900">{{ count($report['by_project']) }}</div>
    </div>
</div>

<!-- Time by Project -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Время по проектам</h2>
    </div>
    
    @if(count($report['by_project']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Проект
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
                    @foreach($report['by_project'] as $projectData)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $projectData['project_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($projectData['total_hours'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">${{ number_format($projectData['total_cost'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('reports.project-time', $projectData['project_id']) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Подробнее →
                                </a>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                            ${{ number_format($report['total_payment'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Записи времени отсутствуют</p>
            <p class="text-sm mt-2">За выбранный период не было добавлено записей времени</p>
        </div>
    @endif
</div>

<!-- Time by Project -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Время по проектам</h2>
    </div>
    
    @if(count($report['by_project']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Проект
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
                    @foreach($report['by_project'] as $projectData)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $projectData['project_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($projectData['total_hours'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">${{ number_format($projectData['total_cost'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('reports.project-time', $projectData['project_id']) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Подробнее →
                                </a>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                            ${{ number_format($report['total_payment'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Записи времени отсутствуют</p>
            <p class="text-sm mt-2">За выбранный период не было добавлено записей времени</p>
        </div>
    @endif
</div>
@endsection
