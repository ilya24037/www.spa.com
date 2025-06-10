<script setup>
import { ref, computed, onMounted } from 'vue';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement
);

const props = defineProps({
    project: Object,
    metrics: Array,
    timeline: Object,
    taskStats: Object,
});

// Подготовка данных для графиков
const progressChartData = computed(() => {
    const dates = props.metrics.map(m => new Date(m.date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' }));
    const healthScores = props.metrics.map(m => m.health_score);
    const completionRates = props.metrics.map(m => 
        m.tasks_total > 0 ? Math.round((m.tasks_completed / m.tasks_total) * 100) : 0
    );
    const velocities = props.metrics.map(m => m.team_velocity);

    return {
        labels: dates,
        datasets: [
            {
                label: 'Здоровье проекта',
                data: healthScores,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                yAxisID: 'y',
            },
            {
                label: 'Процент выполнения',
                data: completionRates,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.3,
                yAxisID: 'y',
            },
            {
                label: 'Скорость команды',
                data: velocities,
                borderColor: 'rgb(251, 146, 60)',
                backgroundColor: 'rgba(251, 146, 60, 0.1)',
                tension: 0.3,
                yAxisID: 'y1',
            },
        ],
    };
});

const taskDistributionData = computed(() => ({
    labels: ['Новые', 'В работе', 'На проверке', 'Выполнены', 'Заблокированы'],
    datasets: [{
        data: [
            props.taskStats.total - props.taskStats.completed - props.taskStats.in_progress,
            props.taskStats.in_progress,
            0, // На проверке - нужно добавить в taskStats
            props.taskStats.completed,
            0, // Заблокированы - нужно добавить в taskStats
        ],
        backgroundColor: [
            'rgba(156, 163, 175, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(251, 146, 60, 0.8)',
            'rgba(34, 197, 94, 0.8)',
            'rgba(239, 68, 68, 0.8)',
        ],
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    scales: {
        y: {
            type: 'linear',
            display: true,
            position: 'left',
            min: 0,
            max: 100,
            title: {
                display: true,
                text: 'Проценты',
            },
        },
        y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
                display: true,
                text: 'Задач/день',
            },
            grid: {
                drawOnChartArea: false,
            },
        },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
        },
    },
};

// Вычисление ключевых метрик
const burndownRate = computed(() => {
    if (props.metrics.length === 0) return 0;
    const lastMetric = props.metrics[props.metrics.length - 1];
    if (lastMetric.team_velocity === 0) return 'Н/Д';
    const remainingTasks = lastMetric.tasks_total - lastMetric.tasks_completed;
    return Math.ceil(remainingTasks / lastMetric.team_velocity);
});

const budgetHealth = computed(() => {
    if (!props.project.budget || props.project.budget === 0) return 100;
    const percentage = (props.project.spent_budget / props.project.budget) * 100;
    if (percentage > 100) return 0;
    if (percentage > 90) return 50;
    if (percentage > 75) return 75;
    return 100;
});

const criticalTasks = computed(() => {
    return props.project.tasks?.filter(task => 
        task.priority === 'critical' && !['done', 'blocked'].includes(task.status)
    ).length || 0;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Ключевые метрики -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Прогресс проекта -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Общий прогресс
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ project.progress }}%
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                        <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="sr-only">Increased by</span>
                                        2.5%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <Link :href="`/projects/${project.id}/tasks`" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Просмотреть задачи
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Дни до завершения -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Дней до дедлайна
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ timeline.remaining_days }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <span :class="timeline.status === 'behind' ? 'text-red-600' : 'text-gray-500'">
                                            из {{ timeline.total_days }}
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm text-gray-500">
                        Прогресс времени: {{ timeline.progress }}%
                    </div>
                </div>
            </div>

            <!-- Скорость выполнения -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Дней до завершения
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ burndownRate }}
                                    </div>
                                    <div class="ml-2 text-sm text-gray-500">
                                        при текущей скорости
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm text-gray-500">
                        Скорость: {{ metrics[metrics.length - 1]?.team_velocity || 0 }} задач/день
                    </div>
                </div>
            </div>

            <!-- Критические задачи -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Критические задачи
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold" :class="criticalTasks > 0 ? 'text-red-600' : 'text-gray-900'">
                                        {{ criticalTasks }}
                                    </div>
                                    <div class="ml-2 text-sm text-gray-500">
                                        / {{ taskStats.overdue }} просрочено
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <Link :href="`/projects/${project.id}/tasks?priority=critical`" class="font-medium text-red-600 hover:text-red-500">
                            Требуют внимания
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- График прогресса -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Динамика проекта</h3>
                <div class="h-64">
                    <Line :data="progressChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Распределение задач -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Распределение задач</h3>
                <div class="h-64">
                    <Doughnut :data="taskDistributionData" :options="doughnutOptions" />
                </div>
            </div>
        </div>

        <!-- Дополнительная информация -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Бюджет -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Бюджет</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Использовано</span>
                            <span class="font-medium">₽{{ Number(project.spent_budget).toLocaleString() }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all duration-500"
                                :class="budgetHealth === 0 ? 'bg-red-600' : budgetHealth === 50 ? 'bg-yellow-600' : 'bg-green-600'"
                                :style="`width: ${Math.min((project.spent_budget / project.budget) * 100, 100)}%`"
                            />
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>0</span>
                            <span>₽{{ Number(project.budget).toLocaleString() }}</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Остаток</span>
                            <span class="font-medium" :class="project.budget - project.spent_budget < 0 ? 'text-red-600' : 'text-green-600'">
                                ₽{{ Number(project.budget - project.spent_budget).toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Команда -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Команда</h3>
                <div class="space-y-3">
                    <div v-for="member in project.members?.slice(0, 5)" :key="member.id" class="flex items-center">
                        <img
                            :src="member.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.user?.name || '')}`"
                            :alt="member.user?.name"
                            class="w-8 h-8 rounded-full"
                        />
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ member.user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ member.role }}</p>
                        </div>
                    </div>
                    <div v-if="project.members?.length > 5" class="text-sm text-gray-500 text-center pt-2">
                        И еще {{ project.members.length - 5 }} участников
                    </div>
                </div>
            </div>

            <!-- Ближайшие этапы -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ближайшие этапы</h3>
                <div class="space-y-3">
                    <div v-for="milestone in project.milestones?.filter(m => m.status !== 'completed').slice(0, 3)" :key="milestone.id">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ milestone.name }}</p>
                                <p class="text-xs text-gray-500">{{ new Date(milestone.due_date).toLocaleDateString('ru-RU') }}</p>
                            </div>
                            <span 
                                v-if="milestone.is_critical"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                            >
                                Критический
                            </span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5">
                            <div 
                                class="bg-indigo-600 h-1.5 rounded-full"
                                :style="`width: ${milestone.progress || 0}%`"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>