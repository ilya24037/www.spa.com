<script setup>
import { ref, computed } from 'vue';
import { useProjectStore } from '@/stores/projectStore';

const props = defineProps({
    project: Object,
    members: Array,
    memberStats: Array,
    canManage: Boolean,
});

const projectStore = useProjectStore();
const showAddMember = ref(false);
const selectedMember = ref(null);
const availableUsers = ref([]);

const newMember = ref({
    user_id: null,
    role: 'developer',
});

const roles = [
    { value: 'manager', label: 'Менеджер', color: 'bg-purple-100 text-purple-800' },
    { value: 'developer', label: 'Разработчик', color: 'bg-blue-100 text-blue-800' },
    { value: 'viewer', label: 'Наблюдатель', color: 'bg-gray-100 text-gray-800' },
];

const loadAvailableUsers = async () => {
    try {
        const response = await axios.get(`/projects/${props.project.id}/members/available`);
        availableUsers.value = response.data.users;
    } catch (error) {
        console.error('Error loading available users:', error);
    }
};

const addMember = async () => {
    if (!newMember.value.user_id) return;
    
    await projectStore.addMember(props.project.id, newMember.value);
    
    newMember.value = {
        user_id: null,
        role: 'developer',
    };
    showAddMember.value = false;
};

const updateMemberRole = async (member, newRole) => {
    await projectStore.updateMember(props.project.id, member.id, { role: newRole });
};

const removeMember = async (member) => {
    if (confirm(`Вы уверены, что хотите удалить ${member.user.name} из проекта?`)) {
        await projectStore.removeMember(props.project.id, member.id);
    }
};

const getRoleConfig = (role) => {
    return roles.find(r => r.value === role) || roles[2];
};

const getMemberStats = (memberId) => {
    return props.memberStats?.find(stat => stat.user.id === memberId)?.stats || null;
};

const openAddMemberModal = () => {
    loadAvailableUsers();
    showAddMember.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
                Команда проекта
                <span class="ml-2 text-sm text-gray-500">
                    ({{ members.length }} участников)
                </span>
            </h3>
            <button
                v-if="canManage"
                @click="openAddMemberModal"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Добавить участника
            </button>
        </div>

        <!-- Список участников -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="member in members"
                :key="member.id"
                class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <img
                            :src="member.user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.user?.name || '')}`"
                            :alt="member.user?.name"
                            class="w-12 h-12 rounded-full"
                        />
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">{{ member.user?.name }}</h4>
                            <p class="text-sm text-gray-500">{{ member.user?.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            :class="getRoleConfig(member.role).color"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                            {{ getRoleConfig(member.role).label }}
                        </span>
                        <button
                            v-if="canManage && member.role !== 'owner'"
                            @click="selectedMember = member"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Статистика участника -->
                <div v-if="getMemberStats(member.user_id)" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500">Задач назначено</p>
                            <p class="font-semibold">{{ getMemberStats(member.user_id).tasks_assigned }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Задач выполнено</p>
                            <p class="font-semibold text-green-600">{{ getMemberStats(member.user_id).tasks_completed }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Часов записано</p>
                            <p class="font-semibold">{{ getMemberStats(member.user_id).hours_logged }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Дней в проекте</p>
                            <p class="font-semibold">{{ getMemberStats(member.user_id).days_in_project }}</p>
                        </div>
                    </div>
                    
                    <!-- Прогресс выполнения -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Процент выполнения</span>
                            <span class="font-medium">{{ getMemberStats(member.user_id).completion_rate }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-green-600 h-2 rounded-full"
                                :style="`width: ${getMemberStats(member.user_id).completion_rate}%`"
                            />
                        </div>
                    </div>
                </div>

                <!-- Дата присоединения -->
                <div class="mt-4 pt-4 border-t text-xs text-gray-500">
                    Присоединился {{ new Date(member.joined_at).toLocaleDateString('ru-RU') }}
                </div>
            </div>
        </div>

        <!-- Модальное окно добавления участника -->
        <div v-if="showAddMember" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddMember = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Добавить участника</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Пользователь</label>
                            <select
                                v-model="newMember.user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">Выберите пользователя</option>
                                <option
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Роль</label>
                            <select
                                v-model="newMember.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button
                            @click="addMember"
                            :disabled="!newMember.user_id"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Добавить
                        </button>
                        <button
                            @click="showAddMember = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                        >
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Контекстное меню для участника -->
        <div v-if="selectedMember" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="selectedMember = null"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Управление участником
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Изменить роль</label>
                            <select
                                :value="selectedMember.role"
                                @change="updateMemberRole(selectedMember, $event.target.value)"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                        </div>
                        
                        <button
                            @click="removeMember(selectedMember)"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
                        >
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Удалить из проекта
                        </button>
                    </div>

                    <div class="mt-5">
                        <button
                            @click="selectedMember = null"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>