<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProjectDashboard from '@/Components/Projects/ProjectDashboard.vue';
import TaskKanban from '@/Components/Projects/TaskKanban.vue';
import MilestoneTimeline from '@/Components/Projects/MilestoneTimeline.vue';
import TeamMembers from '@/Components/Projects/TeamMembers.vue';
import ActivityFeed from '@/Components/Projects/ActivityFeed.vue';
import { useProjectStore } from '@/stores/projectStore';

const props = defineProps({
    project: Object,
    metrics: Array,
    activities: Array,
    timeline: Object,
    taskStats: Object,
    memberStats: Array,
    canEdit: Boolean,
});

const projectStore = useProjectStore();
const activeTab = ref('dashboard');

const tabs = [
    { id: 'dashboard', name: 'Дашборд', icon: 'chart-bar' },
    { id: 'tasks', name: 'Задачи', icon: 'clipboard-list' },
    { id: 'milestones', name: 'Этапы', icon: 'flag' },
    { id: 'team', name: 'Команда', icon: 'users' },
    { id: 'activity', name: 'Активность', icon: 'activity' },
];

onMounted(() => {
    // Загружаем данные в store
    projectStore.currentProject = props.project;
    projectStore.metrics = props.metrics;
    projectStore.activities = props.activities;
    projectStore.tasks = props.project.tasks || [];
    projectStore.milestones = props.project.milestones || [];
    projectStore.members = props.project.members || [];
    
    // Запускаем автообновление
    projectStore.startAutoUpdate(props.project.id);
});

onUnmounted(() => {
    projectStore.stopAutoUpdate();
    projectStore.clearProject();
});

const refreshProject = () => {
    router.reload({
        only: ['project', 'metrics', 'activities', 'timeline', 'taskStats'],
        preserveState: true,
        preserveScroll: true,
    });
};

const healthStatus = computed(() => {
    const score = props.project.health_score;
    if (score >= 80) return { text: 'Отлично', color: 'text-green-600', bg: 'bg-green-100' };
    if (score >= 60) return { text: 'Хорошо', color: 'text-yellow-600', bg: 'bg-yellow-100' };
    if (score >= 40) return { text: 'Требует внимания', color: 'text-orange-600', bg: 'bg-orange-100' };
    return { text: 'Критично', color: 'text-red-600', bg: 'bg-red-100' };
});

const timelineStatus = computed(() => {
    const status = props.timeline.status;
    const statusMap = {
        'on_track': { text: 'По графику', color: 'text-green-600', icon: 'check' },
        'ahead': { text: 'Опережает график', color: 'text-blue-600', icon: 'trending-up' },
        'behind': { text: 'Отстает от графика', color: 'text-red-600', icon: 'trending-down' },
    };
    return statusMap[status] || statusMap['on_track'];
});

const getTabIcon = (iconName) => {
    const icons = {
        'chart-bar': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'clipboard-list': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'flag': 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        'users': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'activity': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return icons[iconName] || icons['chart-bar'];
};
</script>

<template>
    <Head :title="project.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/projects"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ project.name }}
                        </h2>
                        <div class="flex items-center mt-1 space-x-4 text-sm">
                            <span :class="[healthStatus.color, healthStatus.bg]" class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium">
                                Здоровье: {{ project.health_score }}% - {{ healthStatus.text }}
                            </span>
                            <span class="flex items-center text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Прогресс: {{ project.progress }}%
                            </span>
                            <span :class="timelineStatus.color" class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                {{ timelineStatus.text }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="refreshProject"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Обновить
                    </button>
                    <Link
                        v-if="canEdit"
                        :href="`/projects/${project.id}/edit`"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Редактировать
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-8">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            <svg
                                :class="[
                                    activeTab === tab.id ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500',
                                    '-ml-0.5 mr-2 h-5 w-5'
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="getTabIcon(tab.icon)"
                                />
                            </svg>
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div>
                    <!-- Dashboard Tab -->
                    <ProjectDashboard
                        v-if="activeTab === 'dashboard'"
                        :project="project"
                        :metrics="metrics"
                        :timeline="timeline"
                        :taskStats="taskStats"
                    />

                    <!-- Tasks Tab -->
                    <TaskKanban
                        v-if="activeTab === 'tasks'"
                        :project="project"
                        :initialTasks="project.tasks"
                    />

                    <!-- Milestones Tab -->
                    <MilestoneTimeline
                        v-if="activeTab === 'milestones'"
                        :project="project"
                        :milestones="project.milestones"
                    />

                    <!-- Team Tab -->
                    <TeamMembers
                        v-if="activeTab === 'team'"
                        :project="project"
                        :members="project.members"
                        :memberStats="memberStats"
                        :canManage="canEdit"
                    />

                    <!-- Activity Tab -->
                    <ActivityFeed
                        v-if="activeTab === 'activity'"
                        :activities="activities"
                        :project="project"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>