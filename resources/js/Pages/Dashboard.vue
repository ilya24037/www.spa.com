<template>
    <Head title="Мои объявления" />
    
    <!-- Обертка с правильными отступами как в Home.vue -->
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
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ userName }}</div>
                            <div class="text-sm text-gray-500">★ {{ userStats.rating || 4.2 }} • {{ userStats.reviews_count || 5 }} отзывов</div>
                        </div>
                    </div>
                </div>
                
                <!-- Меню как на Авито -->
                <nav class="flex-1">
                    <div class="py-2">
                        <!-- Мои объявления (основная секция) -->
                        <div class="px-4">
                            <Link 
                                href="/profile/items/inactive/all"
                                :class="[
                                    'flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                                    isAdsRoute ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-50'
                                ]"
                            >
                                <span>Мои объявления</span>
                                <span v-if="totalAds > 0" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ totalAds }}</span>
                            </Link>
                        </div>
                        
                        <!-- Остальные пункты меню -->
                        <div class="px-4 mt-2 space-y-1">
                            <Link 
                                href="/bookings"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Заказы
                            </Link>
                            
                            <Link 
                                href="/profile/reviews"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Мои отзывы
                            </Link>
                            
                            <Link 
                                href="/favorites"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Избранное
                            </Link>
                            
                            <Link 
                                href="/messages"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Сообщения
                            </Link>
                            
                            <Link 
                                href="/notifications"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Уведомления
                            </Link>
                            
                            <Link 
                                href="/wallet"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Кошелёк
                            </Link>
                            
                            <Link 
                                href="/profile/addresses"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Адреса
                            </Link>
                            
                            <Link 
                                href="/profile/edit"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Управление профилем
                            </Link>
                            
                            <Link 
                                href="/profile/security"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Защита профиля
                            </Link>
                            
                            <Link 
                                href="/settings"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Настройки
                            </Link>
                            
                            <Link 
                                href="/services"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Платные услуги
                            </Link>
                        </div>
                    </div>
                </nav>
            </SidebarWrapper>
            
            <!-- Контент справа -->
            <section class="flex-1 space-y-6">
                
                <!-- Заголовок без фона -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Мои объявления</h1>
                </div>
                
                <!-- Основной контент - белая карточка как на главной -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <!-- Навигация вкладок как на Авито -->
                        <div class="flex items-center space-x-8">
                            <Link 
                                href="/profile/items/inactive/all"
                                :class="[
                                    'pb-2 text-base font-medium border-b-2 transition-colors',
                                    activeTab === 'inactive' 
                                        ? 'text-gray-900 border-gray-900' 
                                        : 'text-gray-500 border-transparent hover:text-gray-700'
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    Ждут действий
                                    <sup v-if="counts.waiting_payment" class="text-sm font-normal">{{ counts.waiting_payment }}</sup>
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/active/all"
                                :class="[
                                    'pb-2 text-base font-medium border-b-2 transition-colors',
                                    activeTab === 'active' 
                                        ? 'text-gray-900 border-gray-900' 
                                        : 'text-gray-500 border-transparent hover:text-gray-700'
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    Активные
                                    <sup v-if="counts.active" class="text-sm font-normal">{{ counts.active }}</sup>
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/draft/all"
                                :class="[
                                    'pb-2 text-base font-medium border-b-2 transition-colors',
                                    activeTab === 'draft' 
                                        ? 'text-gray-900 border-gray-900' 
                                        : 'text-gray-500 border-transparent hover:text-gray-700'
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    Черновики
                                    <sup v-if="counts.draft" class="text-sm font-normal">{{ counts.draft }}</sup>
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/archive/all"
                                :class="[
                                    'pb-2 text-base font-medium border-b-2 transition-colors',
                                    activeTab === 'archive' 
                                        ? 'text-gray-900 border-gray-900' 
                                        : 'text-gray-500 border-transparent hover:text-gray-700'
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    Архив
                                    <sup v-if="counts.archive" class="text-sm font-normal">{{ counts.archive }}</sup>
                                </span>
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Контент вкладки -->
                    <div v-if="profiles && profiles.length > 0" class="space-y-6">
                        <ItemCard 
                            v-for="profile in profiles" 
                            :key="profile.id"
                            :item="profile"
                            :show-status="true"
                            @update="handleItemUpdate"
                            @delete="handleItemDelete"
                        />
                    </div>
                    
                    <!-- Пустое состояние как на Авито -->
                    <div v-else class="text-center py-16">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ getEmptyStateTitle(activeTab) }}</h3>
                            <p class="text-gray-600 mb-8 leading-relaxed">{{ getEmptyStateDescription(activeTab) }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <!-- Глобальные уведомления -->
    <Toast
        v-for="toast in toasts"
        :key="toast.id"
        :message="toast.message"
        :type="toast.type"
        :duration="toast.duration"
        @close="removeToast(toast.id)"
    />
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ItemCard from '../Components/Profile/ItemCard.vue'
import Toast from '../Components/UI/Toast.vue'

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
    },
    activeTab: {
        type: String,
        default: 'inactive'
    },
    title: {
        type: String,
        default: 'Мои объявления'
    }
})

// Состояние
const showSidebar = ref(false)
const toasts = ref([])

// Пользователь
const page = usePage()
const user = computed(() => page.props.auth?.user || {})

// Вычисляемые свойства для пользователя
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = computed(() => {
    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})

// Проверка текущего роута для объявлений
const isAdsRoute = computed(() => {
    const currentRoute = page.url
    return currentRoute.includes('/profile/items/')
})

// Общее количество объявлений
const totalAds = computed(() => {
    const counts = props.counts || {}
    return (counts.active || 0) + (counts.draft || 0) + (counts.waiting_payment || 0) + (counts.old || 0) + (counts.archive || 0)
})

// Функции для заголовков и описаний
const getPageTitle = (tab) => {
    const titles = {
        active: 'Активные',
        draft: 'Черновики',
        inactive: 'Ждут действий',
        old: 'Старые объявления',
        archive: 'Архив'
    }
    return titles[tab] || 'Мои объявления'
}

const getEmptyStateTitle = (tab) => {
    const titles = {
        active: 'Нет активных объявлений',
        draft: 'Нет черновиков',
        inactive: 'Нет объявлений, ожидающих действий',
        old: 'Нет старых объявлений',
        archive: 'Архив пуст'
    }
    return titles[tab] || 'Нет объявлений'
}

const getEmptyStateDescription = (tab) => {
    const descriptions = {
        active: 'У вас пока нет активных объявлений. Разместите новое объявление, чтобы начать получать заказы.',
        draft: 'У вас нет сохраненных черновиков. Создайте новое объявление или сохраните текущее как черновик.',
        inactive: 'Здесь появятся объявления, которые требуют вашего внимания - например, истекающие или отклоненные.',
        old: 'Здесь будут показаны ваши старые неактивные объявления.',
        archive: 'Архивированные объявления не отображаются в поиске, но сохраняют всю информацию.'
    }
    return descriptions[tab] || 'Пока здесь пусто.'
}

// Обработчики событий
const handleItemUpdate = (itemId, data) => {
    console.log('Обновление объявления:', itemId, data)
    // Здесь логика обновления
}

const handleItemDelete = (itemId) => {
    console.log('Удаление объявления:', itemId)
    // Здесь логика удаления
}

// Управление Toast уведомлениями
const addToast = (message, type = 'success', duration = 5000) => {
    const id = Date.now()
    toasts.value.push({ id, message, type, duration })
}

const removeToast = (id) => {
    toasts.value = toasts.value.filter(toast => toast.id !== id)
}
</script>

<style scoped>
/* Авито-подобные стили для навигации */
.router-link-exact-active {
    @apply text-gray-900 border-gray-900;
}

/* Плавные переходы для ховеров */
a {
    @apply transition-colors duration-150;
}

/* Стилизация счетчиков */
sup {
    @apply text-xs text-gray-500 font-normal;
}
</style>
