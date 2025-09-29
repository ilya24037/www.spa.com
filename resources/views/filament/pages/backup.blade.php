<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Backup Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @php $stats = $this->getBackupStats(); @endphp
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600">Всего копий</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_backups'] }}</p>
                    </div>
                    <x-heroicon-o-archive-box class="w-8 h-8 text-blue-500"/>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600">Общий размер</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_size'] }}</p>
                    </div>
                    <x-heroicon-o-server class="w-8 h-8 text-green-500"/>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600">Последняя копия</p>
                        <p class="text-sm font-medium text-gray-900">{{ $stats['latest_backup'] }}</p>
                    </div>
                    <x-heroicon-o-clock class="w-8 h-8 text-yellow-500"/>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600">Использование диска</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['disk_usage'] }}</p>
                    </div>
                    <x-heroicon-o-folder class="w-8 h-8 text-purple-500"/>
                </div>
            </div>
        </div>

        <!-- Backup Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-400 mt-0.5 mr-3"/>
                <div>
                    <h4 class="text-sm font-medium text-blue-800">Рекомендации по резервному копированию</h4>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Создавайте резервные копии регулярно (рекомендуется еженедельно)</li>
                            <li>Храните копии в нескольких местах (локально и в облаке)</li>
                            <li>Тестируйте восстановление данных из резервных копий</li>
                            <li>Полные копии включают и базу данных, и файлы приложения</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Files List -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Существующие резервные копии</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Название файла
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Тип
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Размер
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Создано
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->getBackupsList() as $backup)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $backup['name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $backup['type'] === 'База данных' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $backup['type'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $backup['size'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $backup['created_at'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="downloadBackup('{{ $backup['name'] }}')"
                                            class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1"/>
                                        Скачать
                                    </button>
                                    <button wire:click="deleteBackup('{{ $backup['name'] }}')"
                                            wire:confirm="Вы уверены, что хотите удалить эту резервную копию?"
                                            class="text-red-600 hover:text-red-900 inline-flex items-center">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1"/>
                                        Удалить
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <x-heroicon-o-archive-box class="mx-auto h-12 w-12 text-gray-400"/>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Нет резервных копий</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Создайте первую резервную копию, используя кнопку выше.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Backup Storage Information -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Информация о хранении</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Локальное хранение</h4>
                    <p class="text-sm text-gray-600 mb-2">
                        Резервные копии сохраняются в директории: <code class="bg-gray-100 px-1 rounded">storage/app/backups/</code>
                    </p>
                    <p class="text-sm text-gray-600">
                        Рекомендуется регулярно копировать файлы в безопасное место или настроить автоматическую синхронизацию с облачным хранилищем.
                    </p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Автоматическое создание</h4>
                    <p class="text-sm text-gray-600 mb-2">
                        Настройте расписание автоматического создания резервных копий через кнопку "Настроить расписание".
                    </p>
                    <p class="text-sm text-gray-600">
                        Для работы автоматического создания копий убедитесь, что настроены cron задачи Laravel.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>