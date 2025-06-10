<script setup>
import { ref, computed, onMounted } from 'vue';
import { useProjectStore } from '@/stores/projectStore';

const props = defineProps({
    project: Object,
    milestones: Array,
});

const projectStore = useProjectStore();
const showCreateMilestone = ref(false);
const selectedMilestone = ref(null);

const newMilestone = ref({
    name: '',
    description: '',
    due_date: '',
    weight: 5,
    is_critical: false,
});

onMounted(() => {
    projectStore.fetchMilestones(props.project.id);
});

const sortedMilestones = computed(() => {
    return [...projectStore.milestones].sort((a, b) => 
        new Date(a.due_date) - new Date(b.due_date)
    );
});

const timelineData = computed(() => {
    if (!props.project.start_date || !props.project.end_date) return null;
    
    const start = new Date(props.project.start_date);
    const end = new Date(props.project.end_date);
    const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    
    return sortedMilestones.value.map(milestone => {
        const milestoneDate = new Date(milestone.due_date);
        const daysFromStart = Math.ceil((milestoneDate - start) / (1000 * 60 * 60 * 24));
        const position = (daysFromStart / totalDays) * 100;
        
        return {
            ...milestone,
            position: Math.max(0, Math.min(100, position)),
            daysFromNow: Math.ceil((milestoneDate - new Date()) / (1000 * 60 * 60 * 24)),
        };
    });
});

const createMilestone = async () => {
    if (!newMilestone.value.name || !newMilestone.value.due_date) return;
    
    await projectStore.createMilestone(props.project.id, newMilestone.value);
    
    // Сброс формы
    newMilestone.value = {
        name: '',
        description: '',
        due_date: '',
        weight: 5,
        is_critical: false,
    };
    showCreateMilestone.value = false;
};

const completeMilestone = async (milestone) => {
    if (confirm(`Вы уверены, что хотите отметить этап "${milestone.name}" как завершенный?`)) {
        await projectStore.completeMilestone(props.project.id, milestone.id);
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        delayed: 'bg-red-100 text-red-800',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Ожидает',
        in_progress: 'В процессе',
        completed: 'Завершен',
        delayed: 'Задержан',
    };
    return labels[status] || status;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Заголовок и действия -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
                Этапы проекта
                <span class="ml-2 text-sm text-gray-500">
                    ({{ sortedMilestones.length }} этапов)
                </span>
            </h3>
            <button
                @click="showCreateMilestone = true"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Добавить этап
            </button>
        </div>

        <!-- Временная шкала -->
        <div v-if="timelineData" class="bg-white rounded-lg shadow p-6">
            <div class="relative">
                <!-- Основная линия -->
                <div class="absolute top-8 left-0 right-0 h-1 bg-gray-200 rounded"></div>
                
                <!-- Текущий прогресс -->
                <div 
                    class="absolute top-8 left-0 h-1 bg-indigo-600 rounded transition-all duration-500"
                    :style="`width: ${props.project.progress}%`"
                ></div>

                <!-- Маркер текущей даты -->
                <div 
                    v-if="props.project.start_date && props.project.end_date"
                    class="absolute top-6 w-0.5 h-8 bg-red-500"
                    :style="`left: ${((new Date() - new Date(props.project.start_date)) / (new Date(props.project.end_date) - new Date(props.project.start_date))) * 100}%`"
                >
                    <span class="absolute -top-6 -left-12 text-xs text-red-600 whitespace-nowrap">
                        Сегодня
                    </span>
                </div>

                <!-- Этапы -->
                <div class="relative h-16 mb-8">
                    <div
                        v-for="milestone in timelineData"
                        :key="milestone.id"
                        class="absolute top-0 transform -translate-x-1/2 cursor-pointer group"
                        :style="`left: ${milestone.position}%`"
                        @click="selectedMilestone = milestone"
                    >
                        <!-- Маркер этапа -->
                        <div
                            class="w-4 h-4 rounded-full border-2 transition-all"
                            :class="[
                                milestone.status === 'completed' ? 'bg-green-500 border-green-500' :
                                milestone.is_critical ? 'bg-red-500 border-red-500' :
                                milestone.status === 'delayed' ? 'bg-orange-500 border-orange-500' :
                                'bg-white border-indigo-600 group-hover:bg-indigo-100'
                            ]"
                        ></div>
                        
                        <!-- Название этапа -->
                        <div class="absolute top-6 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                            <p class="text-xs font-medium text-gray-900">{{ milestone.name }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(milestone.due_date) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Даты начала и конца -->
                <div class="flex justify-between text-xs text-gray-500 mt-4">
                    <span>{{ formatDate(props.project.start_date) }}</span>
                    <span>{{ formatDate(props.project.end_date) }}</span>
                </div>
            </div>
        </div>

        <!-- Список этапов -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="milestone in sortedMilestones"
                :key="milestone.id"
                class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer"
                @click="selectedMilestone = milestone"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h4 class="text-lg font-medium text-gray-900">
                            {{ milestone.name }}
                        </h4>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ formatDate(milestone.due_date) }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end space-y-2">
                        <span
                            :class="getStatusColor(milestone.status)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                            {{ getStatusLabel(milestone.status) }}
                        </span>
                        <span
                            v-if="milestone.is_critical"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                        >
                            Критический
                        </span>
                    </div>
                </div>

                <p v-if="milestone.description" class="text-sm text-gray-600 mb-4">
                    {{ milestone.description }}
                </p>

                <!-- Прогресс -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Прогресс</span>
                        <span class="font-medium">{{ milestone.progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="h-2 rounded-full transition-all duration-500"
                            :class="milestone.status === 'completed' ? 'bg-green-500' : 'bg-indigo-600'"
                            :style="`width: ${milestone.progress}%`"
                        />
                    </div>
                </div>

                <!-- Задачи и дни -->
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        {{ milestone.tasks_count || 0 }} задач
                    </div>
                    <div 
                        class="flex items-center"
                        :class="milestone.daysFromNow < 0 ? 'text-red-600' : milestone.daysFromNow < 7 ? 'text-yellow-600' : 'text-gray-500'"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ milestone.daysFromNow > 0 ? `${milestone.daysFromNow} дней` : milestone.daysFromNow === 0 ? 'Сегодня' : 'Просрочен' }}
                    </div>
                </div>

                <!-- Действия -->
                <div v-if="milestone.status !== 'completed'" class="mt-4 pt-4 border-t">
                    <button
                        @click.stop="completeMilestone(milestone)"
                        class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                    >
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Отметить как завершенный
                    </button>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания этапа -->
        <div v-if="showCreateMilestone" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateMilestone = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Создать новый этап</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Название</label>
                            <input
                                v-model="newMilestone.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Описание</label>
                            <textarea
                                v-model="newMilestone.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Дата завершения</label>
                            <input
                                v-model="newMilestone.due_date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Вес (важность)</label>
                            <input
                                v-model.number="newMilestone.weight"
                                type="range"
                                min="1"
                                max="10"
                                class="mt-1 block w-full"
                            />
                            <span class="text-sm text-gray-500">{{ newMilestone.weight }}</span>
                        </div>
                        
                        <div class="flex items-center">
                            <input
                                v-model="newMilestone.is_critical"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 block text-sm text-gray-900">
                                Критический этап
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button
                            @click="createMilestone"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
                        >
                            Создать
                        </button>
                        <button
                            @click="showCreateMilestone = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                        >
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>