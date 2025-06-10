<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import ProjectStats from '@/Components/Projects/ProjectStats.vue';
import { useProjectStore } from '@/stores/projectStore';

const props = defineProps({
    projects: Object,
    stats: Object,
    filters: Object,
});

const projectStore = useProjectStore();
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const loading = ref(false);

// Автообновление каждые 60 секунд
let updateInterval = null;

onMounted(() => {
    // Загружаем проекты в store
    projectStore.projects = props.projects.data;
    projectStore.stats = props.stats;
    
    // Запускаем автообновление
    updateInterval = setInterval(() => {
        refreshProjects();
    }, 60000);
});

onUnmounted(() => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

const refreshProjects = () => {
    router.reload({
        only: ['projects', 'stats'],
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    router.get('/projects', {
        search: searchQuery.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const createProject = () => {
    router.visit('/projects/create');
};

const projectsByStatus = computed(() => {
    const grouped = {
        active: [],
        planning: [],
        on_hold: [],
        completed: [],
    };
    
    props.projects.data.forEach(project => {
        if (grouped[project.status]) {
            grouped[project.status].push(project);
        }
    });
    
    return grouped;
});

const healthColorClass = (score) => {
    if (score >= 80) return 'text-green-600 bg-green-100';
    if (score >= 60) return 'text-yellow-600 bg-yellow-100';
    if (score >= 40) return 'text-orange-600 bg-orange-100';
    return 'text-red-600 bg-red-100';
};
</script>

<template>
    <Head title="Проекты" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Управление проектами
                </h2>
                <button
                    @click="createProject"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Новый проект
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Статистика -->
                <ProjectStats :stats="stats" class="mb-8" />

                <!-- Фильтры -->
                <div class="mb-6 bg-white rounded-lg shadow p-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Поиск проектов..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @input="applyFilters"
                            />
                        </div>
                        <div class="sm:w-48">
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Все статусы</option>
                                <option value="planning">Планирование</option>
                                <option value="active">Активные</option>
                                <option value="on_hold">Приостановлены</option>
                                <option value="completed">Завершенные</option>
                            </select>
                        </div>
                        <button
                            @click="refreshProjects"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :class="{ 'animate-spin': loading }"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Список проектов -->
                <div v-if="projects.data.length > 0" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ProjectCard
                        v-for="project in projects.data"
                        :key="project.id"
                        :project="project"
                        @refresh="refreshProjects"
                    />
                </div>

                <!-- Пустое состояние -->
                <div v-else class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Нет проектов</h3>
                    <p class="mt-1 text-sm text-gray-500">Начните с создания нового проекта.</p>
                    <div class="mt-6">
                        <button
                            @click="createProject"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Создать проект
                        </button>
                    </div>
                </div>

                <!-- Пагинация -->
                <div v-if="projects.links && projects.links.length > 3" class="mt-6">
                    <nav class="flex items-center justify-between px-4 sm:px-0">
                        <div class="-mt-px flex w-0 flex-1">
                            <Link
                                v-if="projects.prev_page_url"
                                :href="projects.prev_page_url"
                                class="inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                            >
                                <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                </svg>
                                Предыдущая
                            </Link>
                        </div>
                        <div class="hidden md:-mt-px md:flex">
                            <template v-for="(link, index) in projects.links" :key="index">
                                <Link
                                    v-if="link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                                    :href="link.url"
                                    :class="[
                                        link.active
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'inline-flex items-center border-t-2 px-4 pt-4 text-sm font-medium'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                        <div class="-mt-px flex w-0 flex-1 justify-end">
                            <Link
                                v-if="projects.next_page_url"
                                :href="projects.next_page_url"
                                class="inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                            >
                                Следующая
                                <svg class="ml-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </Link>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>