<template>
    <Head title="Личный кабинет" />
    
    <!-- Обертка с правильными отступами -->
    <div class="py-6 lg:py-8">
        
        <!-- Основной контент с гэпом между блоками -->
        <div class="flex gap-6">
            
            <!-- Боковая панель через SidebarWrapper -->
            <SidebarWrapper 
                v-model="showSidebar"
                content-class="p-0"
                :show-desktop-header="false"
                :always-visible-desktop="true"
            >                    
                <!-- Профиль пользователя -->
                <div class="p-6 border-b">
                    <div class="flex items-center gap-4">
                        <div 
                            class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">{{ userName }}</h3>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="font-medium">{{ userStats?.rating || '—' }}</span>
                                <div class="flex">
                                    <svg 
                                        v-for="i in 5" 
                                        :key="i"
                                        class="w-4 h-4"
                                        :class="i <= Math.floor(userStats?.rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <span class="text-xs">{{ userStats?.reviewsCount || 0 }} отзывов</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Меню навигации -->
                <nav class="py-2">
                    <div class="px-3 py-2">
                        <ul class="space-y-1">
                            <li>
                                <Link 
                                    href="/profile"
                                    :class="menuItemClass(isCurrentRoute('profile.dashboard'))"
                                >
                                    Мои анкеты
                                    <span v-if="counts?.profiles > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                                        {{ counts.profiles }}
                                    </span>
                                </Link>
                            </li>
                            <li>
                                <Link 
                                    href="/bookings"
                                    :class="menuItemClass(isCurrentRoute('bookings.index'))"
                                >
                                    Бронирования
                                    <span v-if="counts?.bookings > 0" class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded">
                                        {{ counts.bookings }}
                                    </span>
                                </Link>
                            </li>
                            <li>
                                <Link 
                                    href="/favorites"
                                    :class="menuItemClass(isCurrentRoute('favorites.index'))"
                                >
                                    Избранное
                                    <span v-if="counts?.favorites > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                                        {{ counts.favorites }}
                                    </span>
                                </Link>
                            </li>
                            <li>
                                <Link 
                                    href="/profile/edit"
                                    :class="menuItemClass(isCurrentRoute('profile.edit'))"
                                >
                                    Настройки профиля
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>
            </SidebarWrapper>
            
            <!-- Основной контент -->
            <main class="flex-1">
                <ContentCard title="Мои анкеты">
                    <!-- Кнопка создания в заголовке -->
                    <template #headerActions>
                        <Link 
                            href="/additem"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Создать анкету
                        </Link>
                    </template>
                    
                    <!-- Список анкет -->
                    <div v-if="profiles && profiles.length > 0" class="space-y-4">
                        <ContentCard
                            v-for="profile in profiles" 
                            :key="profile.id"
                            custom-class="border hover:shadow-md transition"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-lg">{{ profile.name }}</h3>
                                    <p class="text-gray-600">{{ profile.services_list || 'Услуги не указаны' }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ profile.full_address }}</p>
                                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                                        <span>{{ profile.views_count }} просмотров</span>
                                        <span>{{ profile.bookings_count }} бронирований</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span 
                                        v-if="profile.is_active"
                                        class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded"
                                    >
                                        Активна
                                    </span>
                                    <span 
                                        v-else
                                        class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded"
                                    >
                                        Неактивна
                                    </span>
                                    <Link 
                                        :href="`/masters/${profile.id}/edit`"
                                        class="text-blue-600 hover:text-blue-700"
                                    >
                                        Редактировать
                                    </Link>
                                </div>
                            </div>
                        </ContentCard>
                    </div>
                    
                    <!-- Пустое состояние -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Нет анкет</h3>
                        <p class="mt-1 text-sm text-gray-500">Создайте свою первую анкету мастера</p>
                        <div class="mt-6">
                            <Link 
                                href="/additem"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Создать анкету
                            </Link>
                        </div>
                    </div>
                </ContentCard>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

// Props
const props = defineProps({
    profiles: {
        type: Array,
        default: () => []
    },
    counts: {
        type: Object,
        default: () => ({})
    },
    userStats: {
        type: Object,
        default: () => ({})
    }
})

// Состояние
const showSidebar = ref(false)

// Пользователь
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

// Цвет аватара
const colors = ['#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#ff9800', '#ff5722']
const avatarColor = computed(() => {
    const charCode = userName.value.charCodeAt(0) || 85
    return colors[charCode % colors.length]
})

// Проверка текущего роута
const isCurrentRoute = (routeName) => {
    return page.url === `/${routeName.replace('.', '/')}`
}

// Класс для пунктов меню
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
    isActive 
        ? 'bg-blue-50 text-blue-600 font-medium' 
        : 'text-gray-700 hover:bg-gray-50'
]
</script>