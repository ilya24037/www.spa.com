<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Range Filter -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата начала</label>
                    <input type="date"
                           wire:model.live="startDate"
                           class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата окончания</label>
                    <input type="date"
                           wire:model.live="endDate"
                           class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getOverviewStats() as $stat)
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600">{{ $stat->getLabel() }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stat->getValue() }}</p>
                            @if($stat->getDescription())
                                <p class="text-xs text-gray-500 mt-1">{{ $stat->getDescription() }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Динамика доходов</h3>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- User Registration Chart -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Регистрации пользователей</h3>
                <div class="h-64">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Masters -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ мастера</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Мастер</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Бронирований</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Заработано</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->getTopMastersData() as $master)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $master->name }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $master->bookings_count }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ number_format($master->total_earned, 0, ',', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Services -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ услуги</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Услуга</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Бронирований</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Доход</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->getTopServicesData() as $service)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ Str::limit($service->title, 30) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $service->bookings_count }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ number_format($service->total_revenue, 0, ',', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = @json($this->getRevenueChartData());

            new Chart(revenueCtx, {
                type: 'line',
                data: revenueData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // User Registration Chart
            const userCtx = document.getElementById('userChart').getContext('2d');
            const userData = @json($this->getUserRegistrationChartData());

            new Chart(userCtx, {
                type: 'line',
                data: userData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
    @endpush

    <x-filament-actions::modals />
</x-filament-panels::page>