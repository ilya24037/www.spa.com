<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    activities: Array,
    project: Object,
});

const filterType = ref('all');
const showOnlyHighImpact = ref(false);

const activityTypes = [
    { value: 'all', label: 'Все действия' },
    { value: 'task_created', label: 'Создание задач' },
    { value: 'task_completed', label: 'Завершение задач' },
    { value: 'milestone_completed', label: 'Достижение этапов' },
    { value: 'member_added', label: 'Изменения в команде' },
];

const filteredActivities = computed(() => {
    let filtered = props.activities || [];
    
    if (filterType.value !== 'all') {
        filtered = filtered.filter(activity => activity.type === filterType.value);
    }
    
    if (showOnlyHighImpact.value) {
        filtered = filtered.filter(activity => activity.impact_score >= 5);
    }
    
    return filtered;
});

const groupedActivities = computed(() => {
    const groups = {};
    
    filteredActivities.value.forEach(activity => {
        const date = new Date(activity.created_at).toLocaleDateString('ru-RU');
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(activity);
    });
    
    return groups;
});

const getActivityIcon = (type) => {
    const icons = {
        task_created: {
            path: 'M12 4v16m8-8H4',
            color: 'text-blue-600 bg-blue-100',
        },
        task_completed: {
            path: 'M5 13l4 4L19 7',
            color: 'text-green-600 bg-green-100',
        },
        task_updated: {
            path: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            color: 'text-yellow-600 bg-yellow-100',
        },
        milestone_completed: {
            path: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
            color: 'text-purple-600 bg-purple-100',
        },
        critical_milestone_completed: {
            path: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            color: 'text-red-600 bg-red-100',
        },
        member_added: {
            path: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
            color: 'text-indigo-600 bg-indigo-100',
        },
        member_removed: {
            path: 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6',
            color: 'text-gray-600 bg-gray-100',
        },
        time_logged: {
            path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            color: 'text-gray-600 bg-gray-100',
        },
        task_overdue: {
            path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            color: 'text-orange-600 bg-orange-100',
        },
        critical_health: {
            path: 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01',
            color: 'text-red-600 bg-red-100',
        },
        budget_exceeded: {
            path: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            color: 'text-red-600 bg-red-100',
        },
    };
    
    return icons[type] || icons.task_created;
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatRelativeTime = (date) => {
    const now = new Date();
    const activityDate = new Date(date);
    const diffInMinutes = Math.floor((now - activityDate) / 1000 / 60);
    
    if (diffInMinutes < 1) return 'только что';
    if (diffInMinutes < 60) return `${diffInMinutes} мин. назад`;
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours} ч. назад`;
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) return `${diffInDays} дн. назад`;
    
    return activityDate.toLocaleDateString('ru-RU');
};
</script>

<template>
    <div class="space-y-6">
        <!-- Фильтры -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <select
                        v-model="filterType"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    >
                        <option
                            v-for="type in activityTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    
                    <label class="flex items-center">
                        <input
                            v-model="showOnlyHighImpact"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="ml-2 text-sm text-gray-600">Только важные события</span>
                    </label>
                </div>
                
                <div class="text-sm text-gray-500">
                    {{ filteredActivities.length }} событий
                </div>
            </div>
        </div>

        <!-- Лента активности -->
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                <template v-for="(date, activities) in groupedActivities" :key="date">
                    <!-- Разделитель дат -->
                    <li class="relative pb-8">
                        <div class="relative flex items-center space-x-3">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <span class="bg-gray-50 px-3 py-1 rounded-full text-sm font-medium text-gray-600">
                                        {{ date }}
                                    </span>
                                    <div class="flex-1 ml-4 border-t border-gray-200"></div>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    <!-- События дня -->
                    <li
                        v-for="(activity, activityIdx) in activities"
                        :key="activity.id"
                        class="relative pb-8"
                    >
                        <span
                            v-if="activityIdx !== activities.length - 1"
                            class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200"
                            aria-hidden="true"
                        />
                        
                        <div class="relative flex items-start space-x-3">
                            <!-- Иконка -->
                            <div class="relative">
                                <div
                                    :class="getActivityIcon(activity.type).color"
                                    class="h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-white"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="getActivityIcon(activity.type).path"
                                        />
                                    </svg>
                                </div>
                                
                                <!-- Индикатор важности -->
                                <div
                                    v-if="activity.impact_score >= 10"
                                    class="absolute -bottom-1 -right-1 h-4 w-4 bg-red-500 rounded-full flex items-center justify-center"
                                >
                                    <span class="text-white text-xs font-bold">!</span>
                                </div>
                            </div>
                            
                            <!-- Контент -->
                            <div class="min-w-0 flex-1">
                                <div>
                                    <div class="text-sm">
                                        <a
                                            v-if="activity.user"
                                            href="#"
                                            class="font-medium text-gray-900 hover:text-gray-700"
                                        >
                                            {{ activity.user.name }}
                                        </a>
                                        <span v-else class="font-medium text-gray-900">Система</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-700">
                                        {{ activity.description }}
                                    </p>
                                </div>
                                
                                <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                    <span>{{ formatTime(activity.created_at) }}</span>
                                    <span>•</span>
                                    <span>{{ formatRelativeTime(activity.created_at) }}</span>
                                    
                                    <span
                                        v-if="activity.impact_score"
                                        class="inline-flex items-center"
                                    >
                                        <span>•</span>
                                        <span class="ml-4">Влияние: {{ activity.impact_score }}</span>
                                    </span>
                                </div>
                                
                                <!-- Дополнительная информация для некоторых типов -->
                                <div
                                    v-if="activity.type === 'task_completed' && activity.entity_id"
                                    class="mt-2 bg-gray-50 rounded-md p-3"
                                >
                                    <Link
                                        :href="`/projects/${project.id}/tasks/${activity.entity_id}`"
                                        class="text-sm text-indigo-600 hover:text-indigo-500"
                                    >
                                        Перейти к задаче →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
                
                <!-- Пустое состояние -->
                <li v-if="filteredActivities.length === 0" class="relative pb-8">
                    <div class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-sm">Нет активности для отображения</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Загрузить больше -->
        <div v-if="activities?.length > 20" class="text-center">
            <button
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Загрузить больше
            </button>
        </div>
    </div>
</template>