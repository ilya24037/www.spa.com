<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Array,
});

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    budget: '',
    members: [],
});

const selectedMembers = ref([]);

const addMember = (userId) => {
    const user = props.users.find(u => u.id === parseInt(userId));
    if (user && !selectedMembers.value.find(m => m.user_id === user.id)) {
        selectedMembers.value.push({
            user_id: user.id,
            user: user,
            role: 'developer',
        });
        updateFormMembers();
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
    form.post(route('projects.store'));
};
</script>

<template>
    <Head title="Создать проект" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    href="/projects"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Создать новый проект
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Основная информация -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Основная информация</h3>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Название проекта *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">
                                    Описание
                                </label>
                                <textarea
                                    v-model="form.description"
                                    id="description"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">
                                        Дата начала
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        id="start_date"
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
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.end_date }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="budget" class="block text-sm font-medium text-gray-700">
                                    Бюджет (₽)
                                </label>
                                <input
                                    v-model="form.budget"
                                    type="number"
                                    id="budget"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.budget" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.budget }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Команда проекта -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Команда проекта</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Добавить участников
                            </label>
                            <div class="flex gap-2">
                                <select
                                    @change="addMember($event.target.value); $event.target.value = ''"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Выберите пользователя</option>
                                    <option
                                        v-for="user in users.filter(u => !selectedMembers.find(m => m.user_id === u.id))"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Список выбранных участников -->
                        <div v-if="selectedMembers.length > 0" class="space-y-3">
                            <div
                                v-for="member in selectedMembers"
                                :key="member.user_id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center">
                                    <img
                                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(member.user.name)}`"
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
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-gray-500 text-sm">
                            Участники не добавлены. Вы будете единственным участником проекта.
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="flex justify-end space-x-3">
                        <Link
                            href="/projects"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Отмена
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
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
                            Создать проект
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>