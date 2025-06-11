<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    project: Object,
});

const emit = defineEmits(['refresh']);

const statusConfig = {
    planning: { label: 'Планирование', color: 'bg-gray-100 text-gray-800' },
    active: { label: 'Активный', color: 'bg-green-100 text-green-800' },
    on_hold: { label: 'Приостановлен', color: 'bg-yellow-100 text-yellow-800' },
    completed: { label: 'Завершен', color: 'bg-blue-100 text-blue-800' },
    cancelled: { label: 'Отменен', color: 'bg-red-100 text-red-800' },
};

const healthColor = computed(() => {
    const score = props.project.health_score;
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    if (score >= 40) return 'text-orange-600';
    return 'text-red-600';
});

const healthBgColor = computed(() => {
    const score = props.project.health_score;
    if (score >= 80) return 'bg-green-50';
    if (score >= 60) return 'bg-yellow-50';
    if (score >= 40) return 'bg-orange-50';
    return 'bg-red-50';
});

const progressRingColor = computed(() => {
    const score = props.project.health_score;
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    if (score >= 40) return 'text-orange-600';
    return 'text-red-600';
});

const daysRemaining = computed(() => {
    if (!props.project.end_date) return null;
    const end = new Date(props.project.end_date);
    const today = new Date();
    const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
    return diff;
});

const budgetUsagePercent = computed(() => {
    if (!props.project.budget || props.project.budget === 0) return 0;
    return Math.round((props.project.spent_budget / props.project.budget) * 100);
});

const progressStrokeOffset = computed(() => {
    const radius = 40;
    const circumference = 2 * Math.PI * radius;
    const progress = props.project.progress || 0;
    return circumference - (progress / 100) * circumference;
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
        <Link :href="`/projects/${project.id}`" class="block">
            <!-- Header -->
            <div class="p-6 pb-4">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            {{ project.name }}
                        </h3>
                        <span :class="statusConfig[project.status].color" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ statusConfig[project.status].label }}
                        </span>
                    </div>
                    
                    <!-- Circular Progress -->
                    <div class="relative w-20 h-20 ml-4">
                        <svg class="w-full h-full transform -rotate-90">
                            <!-- Background circle -->
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                stroke="currentColor"
                                stroke-width="8"
                                fill="none"
                                class="text-gray-200"
                            />
                            <!-- Progress circle -->
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                stroke="currentColor"
                                stroke-width="8"
                                fill="none"
                                :stroke-dasharray="2 * Math.PI * 36"
                                :stroke-dashoffset="progressStrokeOffset"
                                :class="progressRingColor"
                                class="transition-all duration-500 ease-out"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xl font-bold text-gray-900">{{ project.progress }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <p v-if="project.description" class="text-sm text-gray-600 line-clamp-2 mb-4">
                    {{ project.description }}
                </p>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Health Score -->
                    <div :class="healthBgColor" class="rounded-lg p-3">
                        <div class="flex items-center">
                            <svg :class="healthColor" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-600">Здоровье</p>
                                <p :class="healthColor" class="text-lg font-semibold">{{ project.health_score }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Days Remaining -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-xs text-gray-600">Осталось</p>
                                <p class="text-lg font-semibold" :class="daysRemaining && daysRemaining < 7 ? 'text-red-600' : 'text-gray-900'">
                                    {{ daysRemaining !== null ? `${daysRemaining} дн.` : 'Не задано' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Task & Budget Progress -->
                <div class="space-y-3">
                    <!-- Tasks -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Задачи</span>
                            <span class="font-medium text-gray-900">{{ project.tasks_count || 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                :style="`width: ${project.progress}%`"
                            />
                        </div>
                    </div>

                    <!-- Budget -->
                    <div v-if="project.budget">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Бюджет</span>
                            <span class="font-medium" :class="budgetUsagePercent > 100 ? 'text-red-600' : 'text-gray-900'">
                                {{ budgetUsagePercent }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="h-2 rounded-full transition-all duration-500"
                                :class="budgetUsagePercent > 100 ? 'bg-red-600' : budgetUsagePercent > 80 ? 'bg-yellow-600' : 'bg-green-600'"
                                :style="`width: ${Math.min(budgetUsagePercent, 100)}%`"
                            />
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>₽{{ Number(project.spent_budget).toLocaleString() }}</span>
                            <span>из ₽{{ Number(project.budget).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-3">
                <div class="flex items-center justify-between">
                    <!-- Team Members -->
                    <div class="flex -space-x-2">
                        <template v-for="(member, index) in project.members?.slice(0, 3)" :key="member.id">
                            <img
                                :src="member.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.user?.name || '')}&color=0ea5e9&background=e0f2fe`"
                                :alt="member.user?.name"
                                :title="member.user?.name"
                                class="w-8 h-8 rounded-full border-2 border-white"
                            />
                        </template>
                        <div 
                            v-if="project.members?.length > 3"
                            class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center"
                        >
                            <span class="text-xs font-medium text-gray-700">+{{ project.members.length - 3 }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2">
                        <span v-if="project.milestones_count" class="text-sm text-gray-500">
                            {{ project.milestones_count }} этапов
                        </span>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>