<script setup>
import { ref, computed, onMounted } from 'vue';
import { useProjectStore } from '@/stores/projectStore';
import draggable from 'vuedraggable';

const props = defineProps({
    project: Object,
    initialTasks: Array,
});

const projectStore = useProjectStore();
const showCreateTask = ref(false);
const createTaskColumn = ref(null);
const draggedTask = ref(null);

const columns = [
    { id: 'todo', name: 'Новые', color: 'bg-gray-100' },
    { id: 'in_progress', name: 'В работе', color: 'bg-blue-100' },
    { id: 'review', name: 'На проверке', color: 'bg-yellow-100' },
    { id: 'done', name: 'Выполнены', color: 'bg-green-100' },
    { id: 'blocked', name: 'Заблокированы', color: 'bg-red-100' },
];

const newTask = ref({
    title: '',
    description: '',
    priority: 'medium',
    assigned_to: null,
});

onMounted(() => {
    projectStore.fetchTasks(props.project.id);
});

const tasksByColumn = computed(() => {
    const grouped = {};
    columns.forEach(col => {
        grouped[col.id] = projectStore.tasks.filter(task => task.status === col.id);
    });
    return grouped;
});

const handleDragStart = (event, task) => {
    draggedTask.value = task;
    event.dataTransfer.effectAllowed = 'move';
};

const handleDragEnd = () => {
    draggedTask.value = null;
};

const handleDrop = async (event, newStatus) => {
    event.preventDefault();
    if (!draggedTask.value) return;

    // Получаем новый порядок
    const tasksInColumn = tasksByColumn.value[newStatus];
    const newOrder = tasksInColumn.length;

    // Обновляем задачу
    await projectStore.moveTask(draggedTask.value.id, newStatus, newOrder);
    draggedTask.value = null;
};

const allowDrop = (event) => {
    event.preventDefault();
};

const openCreateTask = (columnId) => {
    createTaskColumn.value = columnId;
    showCreateTask.value = true;
    newTask.value.status = columnId;
};

const createTask = async () => {
    if (!newTask.value.title.trim()) return;

    await projectStore.createTask(props.project.id, {
        ...newTask.value,
        status: createTaskColumn.value,
    });

    // Сброс формы
    newTask.value = {
        title: '',
        description: '',
        priority: 'medium',
        assigned_to: null,
    };
    showCreateTask.value = false;
    createTaskColumn.value = null;
};

const updateTaskStatus = async (task, newStatus) => {
    await projectStore.updateTask(props.project.id, task.id, { status: newStatus });
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'text-gray-500 bg-gray-100',
        medium: 'text-yellow-600 bg-yellow-100',
        high: 'text-orange-600 bg-orange-100',
        critical: 'text-red-600 bg-red-100',
    };
    return colors[priority] || colors.medium;
};

const getPriorityLabel = (priority) => {
    const labels = {
        low: 'Низкий',
        medium: 'Средний',
        high: 'Высокий',
        critical: 'Критический',
    };
    return labels[priority] || labels.medium;
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ru-RU', { 
        day: 'numeric', 
        month: 'short' 
    });
};

const isOverdue = (task) => {
    if (!task.due_date || ['done', 'blocked'].includes(task.status)) return false;
    return new Date(task.due_date) < new Date();
};
</script>

<template>
    <div class="h-full">
        <!-- Заголовок и фильтры -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-medium text-gray-900">Канбан доска</h3>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <span>Всего задач: {{ projectStore.tasks.length }}</span>
                    <span>•</span>
                    <span>Выполнено: {{ tasksByColumn.done?.length || 0 }}</span>
                    <span>•</span>
                    <span class="text-red-600">Просрочено: {{ projectStore.overdueTasks.length }}</span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button
                    @click="projectStore.fetchTasks(project.id)"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Обновить
                </button>
            </div>
        </div>

        <!-- Канбан колонки -->
        <div class="flex gap-4 overflow-x-auto pb-4">
            <div
                v-for="column in columns"
                :key="column.id"
                class="flex-shrink-0 w-80"
            >
                <div :class="column.color" class="rounded-t-lg px-4 py-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-gray-900">
                            {{ column.name }}
                            <span class="ml-2 text-sm text-gray-600">
                                ({{ tasksByColumn[column.id]?.length || 0 }})
                            </span>
                        </h4>
                        <button
                            @click="openCreateTask(column.id)"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    class="bg-gray-50 min-h-[600px] rounded-b-lg p-2 space-y-2"
                    @drop="handleDrop($event, column.id)"
                    @dragover="allowDrop"
                >
                    <!-- Форма создания задачи -->
                    <div
                        v-if="showCreateTask && createTaskColumn === column.id"
                        class="bg-white rounded-lg shadow p-4 mb-2"
                    >
                        <input
                            v-model="newTask.title"
                            type="text"
                            placeholder="Название задачи"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            @keydown.enter="createTask"
                            @keydown.esc="showCreateTask = false"
                            autofocus
                        />
                        <div class="mt-2 flex justify-end space-x-2">
                            <button
                                @click="showCreateTask = false"
                                class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900"
                            >
                                Отмена
                            </button>
                            <button
                                @click="createTask"
                                class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                            >
                                Создать
                            </button>
                        </div>
                    </div>

                    <!-- Задачи -->
                    <div
                        v-for="task in tasksByColumn[column.id]"
                        :key="task.id"
                        class="bg-white rounded-lg shadow-sm p-4 cursor-move hover:shadow-md transition-shadow"
                        draggable="true"
                        @dragstart="handleDragStart($event, task)"
                        @dragend="handleDragEnd"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <h5 class="text-sm font-medium text-gray-900 flex-1 pr-2">
                                {{ task.title }}
                            </h5>
                            <span
                                :class="getPriorityColor(task.priority)"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                            >
                                {{ getPriorityLabel(task.priority) }}
                            </span>
                        </div>

                        <p v-if="task.description" class="text-xs text-gray-600 mb-3 line-clamp-2">
                            {{ task.description }}
                        </p>

                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-3">
                                <!-- Исполнитель -->
                                <div v-if="task.assignee" class="flex items-center">
                                    <img
                                        :src="task.assignee.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(task.assignee.name)}`"
                                        :alt="task.assignee.name"
                                        :title="task.assignee.name"
                                        class="w-6 h-6 rounded-full"
                                    />
                                </div>

                                <!-- Дата -->
                                <div
                                    v-if="task.due_date"
                                    class="flex items-center"
                                    :class="isOverdue(task) ? 'text-red-600' : 'text-gray-500'"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(task.due_date) }}
                                </div>
                            </div>

                            <!-- Прогресс -->
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                    <div
                                        class="bg-indigo-600 h-1.5 rounded-full"
                                        :style="`width: ${task.progress}%`"
                                    />
                                </div>
                                <span class="ml-1 text-gray-500">{{ task.progress }}%</span>
                            </div>
                        </div>

                        <!-- Теги -->
                        <div v-if="task.tags?.length" class="mt-2 flex flex-wrap gap-1">
                            <span
                                v-for="tag in task.tags"
                                :key="tag"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Пустое состояние -->
                    <div
                        v-if="!tasksByColumn[column.id]?.length && !showCreateTask"
                        class="text-center py-8 text-gray-500 text-sm"
                    >
                        Нет задач
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>