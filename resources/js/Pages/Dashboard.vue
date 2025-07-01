<template>
    <AppLayout>
        <Head title="Личный кабинет" />
        
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 py-6">
                <div class="flex gap-6">
                    <!-- Боковая панель ВСЕГДА ВИДНА (как на Авито) -->
                    <aside class="w-80 flex-shrink-0">
                        <div class="bg-white rounded-lg shadow-sm sticky top-6">
                            <!-- Профиль пользователя -->
                            <div class="p-6 border-b">
                                <div class="flex items-center gap-4">
                                    <!-- Аватар -->
                                    <div class="relative">
                                        <div 
                                            class="w-16 h-16 rounded-full bg-pink-500 flex items-center justify-center text-white text-2xl font-bold"
                                            :style="{ backgroundColor: avatarColor }"
                                        >
                                            {{ userInitial }}
                                        </div>
                                        <Link 
                                            href="/profile/edit" 
                                            class="absolute bottom-0 right-0 w-6 h-6 bg-white rounded-full shadow-md flex items-center justify-center hover:bg-gray-50"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </Link>
                                    </div>
                                    
                                    <!-- Имя и рейтинг -->
                                    <div>
                                        <h3 class="font-semibold text-lg">{{ user?.name || 'Пользователь' }}</h3>
                                        <Link 
                                            href="/profile/reviews" 
                                            class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600"
                                        >
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
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Меню навигации ВСЕГДА ДОСТУПНО -->
                            <nav class="py-2">
                                <!-- Основные разделы -->
                                <div class="px-3 py-2">
                                    <ul class="space-y-1">
                                        <li>
                                            <Link 
                                                href="/profile"
                                                :class="menuItemClass(route().current('profile.dashboard'))"
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
                                                :class="menuItemClass(route().current('bookings.index'))"
                                            >
                                                Бронирования
                                                <span v-if="counts?.bookings > 0" class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded">
                                                    {{ counts.bookings }}
                                                </span>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/profile/reviews"
                                                :class="menuItemClass(route().current('profile.reviews'))"
                                            >
                                                Мои отзывы
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/favorites"
                                                :class="menuItemClass(route().current('favorites.index'))"
                                            >
                                                Избранное
                                                <span v-if="counts?.favorites > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                                                    {{ counts.favorites }}
                                                </span>
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="border-t my-2"></div>
                                
                                <!-- Сообщения -->
                                <div class="px-3 py-2">
                                    <ul class="space-y-1">
                                        <li>
                                            <Link 
                                                href="/messages"
                                                :class="menuItemClass(route().current('messages.index'))"
                                            >
                                                Сообщения
                                                <span v-if="counts?.unreadMessages > 0" class="ml-auto text-xs bg-red-500 text-white px-2 py-0.5 rounded">
                                                    {{ counts.unreadMessages }}
                                                </span>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/notifications"
                                                :class="menuItemClass(route().current('notifications.index'))"
                                            >
                                                Уведомления
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="border-t my-2"></div>
                                
                                <!-- Финансы и услуги -->
                                <div class="px-3 py-2">
                                    <ul class="space-y-1">
                                        <li>
                                            <Link 
                                                href="/wallet"
                                                :class="menuItemClass(route().current('wallet.index'))"
                                            >
                                                Кошелёк
                                                <span class="ml-auto text-sm font-medium">
                                                    {{ formatPrice(userStats?.balance || 0) }}
                                                </span>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/services"
                                                :class="menuItemClass(route().current('services.index'))"
                                            >
                                                Платные услуги
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/subscription"
                                                :class="menuItemClass(route().current('subscription.index'))"
                                            >
                                                Для профессионалов
                                                <span class="ml-auto text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded">
                                                    Новое
                                                </span>
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="border-t my-2"></div>
                                
                                <!-- Настройки -->
                                <div class="px-3 py-2">
                                    <ul class="space-y-1">
                                        <li>
                                            <Link 
                                                href="/profile/edit"
                                                :class="menuItemClass(route().current('profile.edit'))"
                                            >
                                                Управление профилем
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/profile/security"
                                                :class="menuItemClass(route().current('profile.security'))"
                                            >
                                                Защита профиля
                                            </Link>
                                        </li>
                                        <li>
                                            <Link 
                                                href="/settings"
                                                :class="menuItemClass(route().current('settings.index'))"
                                            >
                                                Настройки
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                            
                            <!-- Баннер приложения -->
                            <div class="p-4 border-t">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-sm font-medium mb-2">Скачайте<br>приложение</p>
                                    <p class="text-xs text-gray-600 mb-3">
                                        Управляйте анкетами<br>в любое время
                                    </p>
                                    <div class="flex gap-2">
                                        <a href="#" class="text-xs bg-black text-white px-3 py-1.5 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                                            </svg>
                                            App Store
                                        </a>
                                        <a href="#" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                                            </svg>
                                            Google Play
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Основной контент (здесь может быть пустое состояние) -->
                    <main class="flex-1">
                        <div class="bg-white rounded-lg shadow-sm">
                            <!-- Заголовок и действия -->
                            <div class="p-6 border-b">
                                <div class="flex items-center justify-between">
                                    <h1 class="text-2xl font-bold">Мои анкеты</h1>
                                    <Link 
                                        href="/masters/create"
                                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Создать анкету
                                    </Link>
                                </div>
                            </div>
                            
                            <!-- Вкладки -->
                            <div class="border-b">
                                <nav class="flex px-6" aria-label="Tabs">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        @click="activeTab = tab.id"
                                        :class="[
                                            'px-4 py-4 text-sm font-medium border-b-2 transition',
                                            activeTab === tab.id
                                                ? 'border-blue-600 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        ]"
                                    >
                                        {{ tab.name }}
                                        <span 
                                            v-if="tab.count > 0"
                                            :class="[
                                                'ml-2 px-2 py-0.5 text-xs rounded-full',
                                                activeTab === tab.id
                                                    ? 'bg-blue-100 text-blue-600'
                                                    : 'bg-gray-100 text-gray-600'
                                            ]"
                                        >
                                            {{ tab.count }}
                                        </span>
                                    </button>
                                </nav>
                            </div>
                            
                            <!-- Контент вкладок (с защитой от пустых данных) -->
                            <div class="p-6">
                                <!-- Активные анкеты -->
                                <div v-if="activeTab === 'active'" class="space-y-4">
                                    <ProfileCard 
                                        v-for="profile in activeProfiles"
                                        :key="profile.id"
                                        :profile="profile"
                                        @edit="editProfile"
                                        @delete="deleteProfile"
                                        @toggle="toggleProfile"
                                    />
                                    
                                    <!-- Пустое состояние -->
                                    <div v-if="activeProfiles.length === 0" class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Нет активных анкет</h3>
                                        <p class="mt-1 text-sm text-gray-500">Создайте свою первую анкету мастера</p>
                                        <div class="mt-6">
                                            <Link 
                                                href="/masters/create"
                                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                            >
                                                Создать анкету
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Черновики -->
                                <div v-if="activeTab === 'draft'" class="space-y-4">
                                    <ProfileCard 
                                        v-for="profile in draftProfiles"
                                        :key="profile.id"
                                        :profile="profile"
                                        :is-draft="true"
                                        @edit="editProfile"
                                        @delete="deleteProfile"
                                        @publish="publishProfile"
                                    />
                                    
                                    <EmptyState 
                                        v-if="draftProfiles.length === 0"
                                        title="Нет черновиков"
                                        description="Здесь будут отображаться незавершенные анкеты"
                                    />
                                </div>
                                
                                <!-- Архив -->
                                <div v-if="activeTab === 'archive'" class="space-y-4">
                                    <ProfileCard 
                                        v-for="profile in archivedProfiles"
                                        :key="profile.id"
                                        :profile="profile"
                                        :is-archived="true"
                                        @restore="restoreProfile"
                                        @delete="deleteProfile"
                                    />
                                    
                                    <EmptyState 
                                        v-if="archivedProfiles.length === 0"
                                        title="Архив пуст"
                                        description="Здесь будут отображаться архивированные анкеты"
                                    />
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'  // 🔥 Добавлен usePage
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProfileCard from '@/Components/Dashboard/ProfileCard.vue'
import EmptyState from '@/Components/Dashboard/EmptyState.vue'

const props = defineProps({
    profiles: {
        type: Array,
        default: () => []  // 🔥 Защита от undefined
    },
    counts: {
        type: Object,
        default: () => ({
            profiles: 0,
            bookings: 0,
            favorites: 0,
            unreadMessages: 0
        })
    },
    userStats: {
        type: Object,
        default: () => ({
            rating: 0,
            reviewsCount: 0,
            balance: 0
        })
    }
})

const user = computed(() => usePage().props.auth.user)
const userInitial = computed(() => user.value?.name?.charAt(0)?.toUpperCase() || 'U')  // 🔥 Защита от undefined

// Цвет аватара на основе имени
const colors = ['#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#ff9800', '#ff5722']
const avatarColor = computed(() => {
    const charCode = user.value?.name?.charCodeAt(0) || 85  // 85 = 'U'
    return colors[charCode % colors.length]
})

// Вкладки
const activeTab = ref('active')
const tabs = computed(() => [
    { 
        id: 'active', 
        name: 'Активные', 
        count: activeProfiles.value.length 
    },
    { 
        id: 'draft', 
        name: 'Черновики', 
        count: draftProfiles.value.length 
    },
    { 
        id: 'archive', 
        name: 'Архив', 
        count: archivedProfiles.value.length 
    }
])

// Фильтрация профилей (с защитой от undefined)
const activeProfiles = computed(() => 
    props.profiles?.filter(p => p.status === 'active') || []
)
const draftProfiles = computed(() => 
    props.profiles?.filter(p => p.status === 'draft') || []
)
const archivedProfiles = computed(() => 
    props.profiles?.filter(p => p.status === 'archived') || []
)

// Класс для пунктов меню
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
    isActive 
        ? 'bg-blue-50 text-blue-600 font-medium' 
        : 'text-gray-700 hover:bg-gray-50'
]

// Форматирование цены
const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(price)
}

// Действия с профилями
const editProfile = (profile) => {
    router.get(`/masters/${profile.id}/edit`)
}

const deleteProfile = (profile) => {
    if (confirm('Удалить анкету?')) {
        router.delete(`/masters/${profile.id}`)
    }
}

const toggleProfile = (profile) => {
    router.patch(`/masters/${profile.id}/toggle`)
}

const publishProfile = (profile) => {
    router.patch(`/masters/${profile.id}/publish`)
}

const restoreProfile = (profile) => {
    router.patch(`/masters/${profile.id}/restore`)
}
</script>