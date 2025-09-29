<x-filament-panels::page>
    <div class="space-y-6">
        <!-- System Information -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <x-heroicon-o-server class="w-5 h-5 mr-2"/>
                Системная информация
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->getSystemInfo() as $key => $value)
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-sm font-medium text-gray-500 capitalize">
                            {{ str_replace('_', ' ', $key) }}
                        </dt>
                        <dd class="text-sm text-gray-900 mt-1">{{ $value }}</dd>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Database Information -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <x-heroicon-o-circle-stack class="w-5 h-5 mr-2"/>
                База данных
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->getDatabaseInfo() as $key => $value)
                    <div class="bg-gray-50 rounded-md p-3">
                        <dt class="text-sm font-medium text-gray-500 capitalize">
                            {{ str_replace('_', ' ', $key) }}
                        </dt>
                        <dd class="text-sm text-gray-900 mt-1">{{ $value }}</dd>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Storage Information -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <x-heroicon-o-folder class="w-5 h-5 mr-2"/>
                Хранилище
            </h3>
            @foreach($this->getStorageInfo() as $diskName => $diskInfo)
                <div class="mb-4 last:mb-0">
                    <h4 class="font-medium text-gray-900 mb-2">{{ ucfirst($diskName) }} Disk</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($diskInfo as $key => $value)
                            <div class="bg-gray-50 rounded-md p-3">
                                <dt class="text-sm font-medium text-gray-500 capitalize">
                                    {{ str_replace('_', ' ', $key) }}
                                </dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Queue Information -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <x-heroicon-o-queue-list class="w-5 h-5 mr-2"/>
                    Очереди
                </h3>
                @php $queueInfo = $this->getQueueInfo(); @endphp
                @if(isset($queueInfo['error']))
                    <p class="text-red-600 text-sm">Ошибка: {{ $queueInfo['error'] }}</p>
                @else
                    <div class="space-y-3">
                        <div class="bg-gray-50 rounded-md p-3">
                            <dt class="text-sm font-medium text-gray-500">Драйвер по умолчанию</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $queueInfo['default_driver'] }}</dd>
                        </div>
                        @foreach($queueInfo['drivers'] as $name => $info)
                            <div class="bg-gray-50 rounded-md p-3">
                                <dt class="text-sm font-medium text-gray-500">{{ $name }}</dt>
                                <dd class="text-sm text-gray-900 mt-1">
                                    {{ $info['driver'] }} - {{ $info['status'] }}
                                </dd>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Cache Information -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <x-heroicon-o-bolt class="w-5 h-5 mr-2"/>
                    Кеш
                </h3>
                <div class="space-y-3">
                    @foreach($this->getCacheInfo() as $key => $value)
                        <div class="bg-gray-50 rounded-md p-3">
                            <dt class="text-sm font-medium text-gray-500 capitalize">
                                {{ str_replace('_', ' ', $key) }}
                            </dt>
                            <dd class="text-sm mt-1 {{ $key === 'status' && str_contains($value, 'Ошибка') ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $value }}
                            </dd>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Installed Packages -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <x-heroicon-o-cube class="w-5 h-5 mr-2"/>
                Установленные пакеты (топ 20)
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Пакет</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Версия</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Описание</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->getInstalledPackages() as $package)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $package['name'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $package['version'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ Str::limit($package['description'], 80) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>