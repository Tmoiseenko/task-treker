{{-- Audit History Section --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">История изменений</h2>
    
    @if($task->auditLogs->count() > 0)
        <div class="space-y-3">
            @foreach($task->auditLogs->take(20) as $log)
                <div class="flex gap-3 text-sm border-l-2 border-gray-200 pl-3 py-2 hover:border-indigo-500 hover:bg-gray-50 transition-colors rounded-r">
                    {{-- Timeline dot --}}
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
                    </div>
                    
                    {{-- Change details --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium text-gray-900">{{ $log->user->name }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-500">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        
                        <div class="text-gray-700">
                            <span class="text-gray-600">изменил(а)</span>
                            <span class="font-medium text-gray-900 px-1.5 py-0.5 bg-gray-100 rounded">{{ $log->field }}</span>
                            
                            @if($log->old_value)
                                <div class="mt-1 text-sm">
                                    <span class="text-gray-500">с</span>
                                    <span class="text-red-600 line-through px-1.5 py-0.5 bg-red-50 rounded">"{{ $log->old_value }}"</span>
                                    <span class="text-gray-500">на</span>
                                    <span class="text-green-600 px-1.5 py-0.5 bg-green-50 rounded">"{{ $log->new_value }}"</span>
                                </div>
                            @else
                                <div class="mt-1 text-sm">
                                    <span class="text-gray-500">на</span>
                                    <span class="text-green-600 px-1.5 py-0.5 bg-green-50 rounded">"{{ $log->new_value }}"</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            
            @if($task->auditLogs->count() > 20)
                <div class="text-center pt-2">
                    <p class="text-sm text-gray-500">
                        Показано 20 из {{ $task->auditLogs->count() }} изменений
                    </p>
                </div>
            @endif
        </div>
    @else
        <p class="text-gray-500 text-center py-4">История изменений пуста</p>
    @endif
</div>
