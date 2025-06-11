<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Array,
});

// Шаги формы
const currentStep = ref(1);
const totalSteps = 3;

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    budget: '',
    members: [],
});

const selectedMembers = ref([]);
const searchTerm = ref('');
const showMemberDropdown = ref(false);

// Фильтрация пользователей для поиска
const filteredUsers = computed(() => {
    if (!searchTerm.value) return [];
    
    const term = searchTerm.value.toLowerCase();
    return props.users.filter(user => 
        !selectedMembers.value.find(m => m.user_id === user.id) &&
        (user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term))
    ).slice(0, 5);
});

// Валидация для текущего шага
const isStepValid = computed(() => {
    switch(currentStep.value) {
        case 1:
            return form.name.length > 0;
        case 2:
            return !form.end_date || !form.start_date || form.start_date <= form.end_date;
        case 3:
            return true;
        default:
            return false;
    }
});

// Прогресс формы
const formProgress = computed(() => {
    return Math.round((currentStep.value / totalSteps) * 100);
});

const nextStep = () => {
    if (currentStep.value < totalSteps && isStepValid.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const addMember = (user) => {
    if (!selectedMembers.value.find(m => m.user_id === user.id)) {
        selectedMembers.value.push({
            user_id: user.id,
            user: user,
            role: 'developer',
        });
        updateFormMembers();
        searchTerm.value = '';
        showMemberDropdown.value = false;
    }
};

const removeMember = (userId) => {
    selectedMembers.value = selectedMembers.value.filter(m => m.user_id !== userId);
    updateFormMembers();
};

const updateMemberRole = (userId, role) => {
    const member = selectedMembers.value.find(m => m.user_id === userId);
    if (member) {
        member.role = role;
        updateFormMembers();
    }
};

const updateFormMembers = () => {
    form.members = selectedMembers.value.map(m => ({
        user_id: m.user_id,
        role: m.role,
    }));
};

const submit = () => {
    if (isStepValid.value) {
        form.post(route('projects.store'));
    }
};

// Подсказки для пользователя
const tips = {
    1: 'Дайте проекту понятное название, которое отражает его суть',
    2: 'Установите реалистичные сроки с учетом возможных задержек',
    3: 'Добавьте ключевых участников команды для успешной реализации'
};

// Быстрые шаблоны проектов
const projectTemplates = [
    { name: 'Веб-приложение', budget: 500000, duration: 90 },
    { name: 'Мобильное приложение', budget: 800000, duration: 120 },
    { name: 'MVP продукта', budget: 300000, duration: 60 },
    { name: 'Корпоративный сайт', budget: 200000, duration: 45 },
];

const applyTemplate = (template) => {
    form.name = template.name;
    form.budget = template.budget;
    if (template.duration) {
        form.start_date = new Date().toISOString().split('T')[0];
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + template.duration);
        form.end_date = endDate.toISOString().split('T')[0];
    }
};

// Форматирование бюджета
const formatBudget = (value) => {
    return new Intl.NumberFormat('ru-RU').format(value);
};
</script>

<template>
    <Head title="Создать проект" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/projects"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Создать новый проект
                    </h2>
                </div>
                <!-- Прогресс бар -->
                <div class="hidden sm:flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        Шаг {{ currentStep }} из {{ totalSteps }}
                    </div>
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div 
                            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                            :style="`width: ${formProgress}%`"
                        ></div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Мобильный прогресс -->
                    <div class="sm:hidden bg-white shadow rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Шаг {{ currentStep }} из {{ totalSteps }}</span>
                            <span class="text-sm font-medium text-indigo-600">{{ formProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                                :style="`width: ${formProgress}%`"
                            ></div>
                        </div>
                    </div>

                    <!-- Шаг 1: Основная информация -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <!-- Быстрые шаблоны -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-900 mb-3">Быстрый старт с шаблоном</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="template in projectTemplates"
                                    :key="template.name"
                                    @click.prevent="applyTemplate(template)"
                                    type="button"
                                    class="text-left px-3 py-2 text-sm bg-white rounded-md border border-blue-300 hover:bg-blue-100 transition-colors"
                                >
                                    <div class="font-medium text-blue-900">{{ template.name }}</div>
                                    <div class="text-xs text-blue-600">₽{{ formatBudget(template.budget) }} • {{ template.duration }} дней</div>
                                </button>
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Основная информация</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">
                                        Название проекта *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        id="name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg"
                                        placeholder="Например: Разработка интернет-магазина"
                                        required
                                        autofocus
                                    />
                                    <p class="mt-2 text-sm text-gray-500">{{ tips[1] }}</p>
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        Описание проекта
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        id="description"
                                        rows="5"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Опишите цели, задачи и ожидаемые результаты проекта..."
                                    ></textarea>
                                    <div class="mt-2 flex justify-between">
                                        <p class="text-sm text-gray-500">Подробное описание поможет команде лучше понять задачи</p>
                                        <p class="text-sm text-gray-400">{{ form.description.length }}/1000</p>
                                    </div>
                                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Шаг 2: Сроки и бюджет -->
                    <div v-show="currentStep === 2" class="space-y-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Планирование</h3>
                            
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700">
                                            Дата начала
                                        </label>
                                        <input
                                            v-model="form.start_date"
                                            type="date"
                                            id="start_date"
                                            :min="new Date().toISOString().split('T')[0]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.start_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700">
                                            Дата окончания
                                        </label>
                                        <input
                                            v-model="form.end_date"
                                            type="date"
                                            id="end_date"
                                            :min="form.start_date || new Date().toISOString().split('T')[0]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.end_date }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Визуальная временная шкала -->
                                <div v-if="form.start_date && form.end_date" class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Длительность проекта:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ Math.ceil((new Date(form.end_date) - new Date(form.start_date)) / (1000 * 60 * 60 * 24)) }} дней
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label for="budget" class="block text-sm font-medium text-gray-700">
                                        Бюджет проекта
                                    </label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₽</span>
                                        </div>
                                        <input
                                            v-model="form.budget"
                                            type="number"
                                            id="budget"
                                            min="0"
                                            step="1000"
                                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0"
                                        />
                                    </div>
                                    <p v-if="form.budget" class="mt-2 text-sm text-gray-600">
                                        {{ formatBudget(form.budget) }} рублей
                                    </p>
                                    <p v-if="form.errors.budget" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.budget }}
                                    </p>
                                </div>

                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-sm text-yellow-800">
                                        <span class="font-medium">Совет:</span> {{ tips[2] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Шаг 3: Команда -->
                    <div v-show="currentStep === 3" class="space-y-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Команда проекта</h3>
                            
                            <div class="space-y-6">
                                <!-- Поиск участников -->
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Добавить участников
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="searchTerm"
                                            @focus="showMemberDropdown = true"
                                            @blur="setTimeout(() => showMemberDropdown = false, 200)"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
                                            placeholder="Начните вводить имя или email..."
                                        />
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        
                                        <!-- Выпадающий список результатов -->
                                        <div v-if="showMemberDropdown && filteredUsers.length > 0" class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 max-h-60 overflow-auto">
                                            <button
                                                v-for="user in filteredUsers"
                                                :key="user.id"
                                                @click.prevent="addMember(user)"
                                                type="button"
                                                class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-3"
                                            >
                                                <img
                                                    :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=e0f2fe&color=0ea5e9`"
                                                    :alt="user.name"
                                                    class="w-8 h-8 rounded-full"
                                                />
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Список участников -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Участники проекта</h4>
                                    <div v-if="selectedMembers.length > 0" class="space-y-3">
                                        <transition-group name="list" tag="div">
                                            <div
                                                v-for="member in selectedMembers"
                                                :key="member.user_id"
                                                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                            >
                                                <div class="flex items-center">
                                                    <img
                                                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(member.user.name)}&background=e0f2fe&color=0ea5e9`"
                                                        :alt="member.user.name"
                                                        class="w-10 h-10 rounded-full"
                                                    />
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900">{{ member.user.name }}</p>
                                                        <p class="text-xs text-gray-500">{{ member.user.email }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <select
                                                        :value="member.role"
                                                        @change="updateMemberRole(member.user_id, $event.target.value)"
                                                        class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="manager">Менеджер</option>
                                                        <option value="developer">Разработчик</option>
                                                        <option value="viewer">Наблюдатель</option>
                                                    </select>
                                                    <button
                                                        @click="removeMember(member.user_id)"
                                                        type="button"
                                                        class="text-red-600 hover:text-red-800 transition-colors"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </transition-group>
                                    </div>
                                    <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500">
                                            Участники не добавлены. Вы будете единственным участником проекта.
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-sm text-blue-800">
                                        <span class="font-medium">Совет:</span> {{ tips[3] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Навигация и действия -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <button
                                v-if="currentStep > 1"
                                @click="prevStep"
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Назад
                            </button>
                            <Link
                                v-else
                                href="/projects"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Отмена
                            </Link>

                            <div class="space-x-3">
                                <button
                                    v-if="currentStep < totalSteps"
                                    @click="nextStep"
                                    type="button"
                                    :disabled="!isStepValid"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Далее
                                    <svg class="-mr-0.5 ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing || !isStepValid"
                                    class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="-ml-0.5 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Создать проект
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Анимация для списка участников */
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>