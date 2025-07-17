<template>
    <AppLayout>
        <Head title="Мои объявления" />
    
        <!-- Основной контент с боковой панелью как на главной -->
        <div class="flex gap-6 py-6 lg:py-8">
            <!-- Боковая панель -->
            <aside class="w-64 flex-shrink-0">
                <!-- Профиль пользователя -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ userName }}</h2>
                            <div class="flex items-center mt-1">
                                <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span class="text-sm text-gray-600">{{ userRating }} • {{ userReviewsCount }} отзывов</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Навигация -->
                <nav class="bg-white rounded-lg shadow-sm">
                    <ul class="space-y-1">
                        <li>
                            <div class="text-xs font-medium text-gray-700 uppercase tracking-wide px-3 py-2">
                                Мои объявления
                            </div>
                        </li>
                        <li>
                            <Link 
                                href="/profile/items/inactive/all"
                                :class="menuItemClass(isCurrentRoute('profile.items.inactive'))"
                            >
                                Мои объявления
                                <span v-if="totalAds > 0" class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-medium ml-auto">
                                    {{ totalAds }}
                                </span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/orders"
                                :class="menuItemClass(isCurrentRoute('orders.index'))"
                            >
                                Заказы
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/reviews"
                                :class="menuItemClass(isCurrentRoute('reviews.index'))"
                            >
                                Мои отзывы
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/favorites"
                                :class="menuItemClass(isCurrentRoute('favorites.index'))"
                            >
                                Избранное
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/portal-prizov"
                                :class="menuItemClass(false)"
                            >
                                Портал призов
                                <span class="bg-red-500 text-white px-1.5 py-0.5 rounded text-xs font-medium ml-auto">Новое</span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/garage"
                                :class="menuItemClass(false)"
                            >
                                Гараж
                                <span class="bg-red-500 text-white px-1.5 py-0.5 rounded text-xs font-medium ml-auto">Новое</span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/messages"
                                :class="menuItemClass(isCurrentRoute('messages.index'))"
                            >
                                Сообщения
                                <span v-if="counts.unreadMessages > 0" class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs font-medium ml-auto">
                                    {{ counts.unreadMessages }}
                                </span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/notifications"
                                :class="menuItemClass(isCurrentRoute('notifications.index'))"
                            >
                                Уведомления
                            </Link>
                        </li>
                        <li class="pt-2 mt-2 border-t">
                            <Link 
                                href="/wallet"
                                :class="menuItemClass(isCurrentRoute('wallet.index'))"
                            >
                                Кошелёк
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/services"
                                :class="menuItemClass(isCurrentRoute('services.index'))"
                            >
                                Платные услуги
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/pro"
                                :class="menuItemClass(isCurrentRoute('pro.index'))"
                            >
                                Для профессионалов
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/special-offers"
                                :class="menuItemClass(false)"
                            >
                                Спецпредложения
                                <span class="bg-red-500 text-white px-1.5 py-0.5 rounded text-xs font-medium ml-auto">Новое</span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/subscriptions"
                                :class="menuItemClass(isCurrentRoute('subscriptions.index'))"
                            >
                                Рассылки
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/service-level"
                                :class="menuItemClass(isCurrentRoute('service-level.index'))"
                            >
                                Уровень сервиса
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/avito-mall"
                                :class="menuItemClass(isCurrentRoute('avito-mall.index'))"
                            >
                                Авито Молл
                            </Link>
                        </li>
                        <li class="pt-2 mt-2 border-t">
                            <Link 
                                href="/addresses"
                                :class="menuItemClass(isCurrentRoute('addresses.index'))"
                            >
                                Адреса
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/profile/manage"
                                :class="menuItemClass(isCurrentRoute('profile.manage'))"
                            >
                                Управление профилем
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/profile/security"
                                :class="menuItemClass(isCurrentRoute('profile.security'))"
                            >
                                Защита профиля
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/settings"
                                :class="menuItemClass(isCurrentRoute('settings.index'))"
                            >
                                Настройки
                            </Link>
                        </li>
                    </ul>
                </nav>
            </aside>
            
            <!-- Основной контент как на главной -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <!-- Заголовок страницы -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">{{ title || 'Мои объявления' }}</h1>
                </div>



                <!-- Основной контент с правильными отступами -->
                <div class="space-y-6">
                    <!-- Вкладки как на Avito -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <Link 
                                href="/profile/items/inactive/all"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'inactive'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Ждут действий
                                <span v-if="counts.inactive > 0" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs font-medium">
                                    {{ counts.inactive }}
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/active/all"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'active'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Активные
                                <span v-if="counts.active > 0" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs font-medium">
                                    {{ counts.active }}
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/draft/all"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'draft'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Черновики
                                <span v-if="counts.draft > 0" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs font-medium">
                                    {{ counts.draft }}
                                </span>
                            </Link>
                            
                            <Link 
                                href="/profile/items/archive/all"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === 'archived'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                Архив
                                <span v-if="counts.archived > 0" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs font-medium">
                                    {{ counts.archived }}
                                </span>
                            </Link>
                        </nav>
                    </div>

                    <!-- Список объявлений -->
                    <div class="items-list">
                        <div v-if="filteredProfiles.length > 0" class="space-y-4">
                            <ItemCard 
                                v-for="profile in filteredProfiles" 
                                :key="profile.id"
                                :item="profile"
                                @item-deleted="handleItemDeleted"
                            />
                        </div>
                    
                    <!-- Пустое состояние -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Нет объявлений</h3>
                        <p class="mt-1 text-sm text-gray-500">Создайте свое первое объявление</p>
                        <div class="mt-6">
                            <Link 
                                href="/additem"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Разместить объявление
                            </Link>
                        </div>
                                            </div>
                    </div>
                </div>
            </div>
            </main>
        </div>

        <!-- Уведомления -->
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :message="toast.message"
            :type="toast.type"
            :duration="toast.duration"
            @close="removeToast(toast.id)"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import SidebarWrapper from '../Components/Layout/SidebarWrapper.vue'
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
        default: 'paused'
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
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

// Цвет аватара
const colors = ['#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#ff9800', '#ff5722']
const avatarColor = computed(() => {
    const charCode = userName.value.charCodeAt(0) || 85
    return colors[charCode % colors.length]
})

// Общее количество объявлений
const totalAds = computed(() => {
    return (props.counts.active || 0) + (props.counts.draft || 0) + (props.counts.inactive || 0) + (props.counts.old || 0) + (props.counts.archived || 0)
})

// Активная вкладка (из props)
const activeTab = computed(() => props.activeTab || 'active')

// Показываем объявления пришедшие с сервера (уже отфильтрованные)
const filteredProfiles = computed(() => {
    return props.profiles || []
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

// Обработчик события удаления объявления
const handleItemDeleted = (deletedId) => {
  // Удаляем элемент из списка профилей
  const index = props.profiles.findIndex(p => p.id === deletedId)
  if (index > -1) {
    props.profiles.splice(index, 1)
  }
  
  // Показываем уведомление об успешном удалении
  addToast('Объявление успешно удалено', 'success', 3000)
}

// Emit событие для обновления счетчиков
const emit = defineEmits(['counts-updated'])

// Управление уведомлениями
const addToast = (message, type = 'info', duration = 5000) => {
    const id = Date.now()
    toasts.value.push({ id, message, type, duration })
    setTimeout(() => removeToast(id), duration)
}

const removeToast = (id) => {
    toasts.value = toasts.value.filter(toast => toast.id !== id)
}
</script>

<style scoped>
/* Стили навигации как на Avito */
nav ul li {
    @apply relative;
}

nav ul li a {
    @apply block px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors duration-150;
}

nav ul li a.router-link-active {
    @apply bg-gray-100 text-gray-900 font-medium;
}

nav ul li a span.bg-red-500 {
    @apply text-xs px-1.5 py-0.5;
}

.items-list {
    @apply space-y-4;
}

/* Убираем лишние отступы */
aside nav {
    @apply p-0;
}

aside nav ul {
    @apply py-2;
}
</style>