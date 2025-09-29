<x-filament-widgets::widget>
    <x-filament::section>
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
            Быстрые действия
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Модерация объявлений --}}
            @if($pendingModeration > 0)
            <a href="/admin/ads?tableFilters[status][values][0]=pending_moderation" 
               class="relative group bg-orange-50 dark:bg-orange-900/10 rounded-lg p-4 hover:bg-orange-100 dark:hover:bg-orange-900/20 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-orange-600 dark:text-orange-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="mt-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                На модерации
                            </div>
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ $pendingModeration }}
                            </div>
                        </div>
                    </div>
                    <div class="text-orange-400 group-hover:text-orange-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Требуют проверки
                </div>
            </a>
            @endif
            
            {{-- Жалобы --}}
            @if($pendingComplaints > 0)
            <a href="/admin/complaints" 
               class="relative group bg-red-50 dark:bg-red-900/10 rounded-lg p-4 hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-red-600 dark:text-red-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                            </svg>
                        </div>
                        <div class="mt-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                Жалобы
                            </div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ $pendingComplaints }}
                            </div>
                        </div>
                    </div>
                    <div class="text-red-400 group-hover:text-red-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Ожидают рассмотрения
                </div>
            </a>
            @endif
            
            {{-- Новые пользователи --}}
            <a href="/admin/users?tableFilters[created_at][from]={{ today()->format('Y-m-d') }}" 
               class="relative group bg-green-50 dark:bg-green-900/10 rounded-lg p-4 hover:bg-green-100 dark:hover:bg-green-900/20 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-green-600 dark:text-green-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <div class="mt-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                Новых сегодня
                            </div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $todayRegistrations }}
                            </div>
                        </div>
                    </div>
                    <div class="text-green-400 group-hover:text-green-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Регистрации
                </div>
            </a>
            
            {{-- Бронирования --}}
            <a href="/admin/bookings?tableFilters[created_at][from]={{ today()->format('Y-m-d') }}" 
               class="relative group bg-blue-50 dark:bg-blue-900/10 rounded-lg p-4 hover:bg-blue-100 dark:hover:bg-blue-900/20 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-blue-600 dark:text-blue-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="mt-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                Бронирований
                            </div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $todayBookings }}
                            </div>
                        </div>
                    </div>
                    <div class="text-blue-400 group-hover:text-blue-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Сегодня
                </div>
            </a>
        </div>
        
        {{-- Дополнительные быстрые ссылки --}}
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-2">
                <a href="/admin/ads/create" 
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Создать объявление
                </a>
                
                <a href="/admin/users/create" 
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Добавить пользователя
                </a>
                
                <a href="/admin/notifications/create" 
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    Отправить уведомление
                </a>
                
                <a href="/admin/reports" 
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v7m3-2h6"></path>
                    </svg>
                    Отчеты
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>