<template>
    <Head title="Личный кабинет" />
    
    <!-- Обертка с правильными отступами как на главной -->
    <div class="py-6 lg:py-8">
        <div class="flex gap-6">
            <!-- Боковая панель -->
            <SidebarWrapper :show="showSidebar" @close="showSidebar = false">
                <!-- Профиль пользователя -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ userName }}</h2>
                            <div class="flex items-center space-x-1">
                                <div class="flex">
                                    <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= (userStats.rating || 0) ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500">{{ userStats.reviewsCount || 0 }} отзывов</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Навигация -->
                <nav class="flex-1 px-4 py-4 space-y-1">
                    <ul class="space-y-1">
                        <li>
                            <Link 
                                href="/profile"
                                :class="menuItemClass(isCurrentRoute('profile.dashboard'))"
                            >
                                Мои объявления
                                <span v-if="totalAds > 0" class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ totalAds }}
                                </span>
                            </Link>
                        </li>
                        <li>
                            <Link 
                                href="/bookings"
                                :class="menuItemClass(isCurrentRoute('bookings.index'))"
                            >
                                Бронирования
                                <span v-if="counts.bookings > 0" class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs font-medium">
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
                                <span v-if="counts.favorites > 0" class="bg-pink-100 text-pink-600 px-2 py-0.5 rounded-full text-xs font-medium">
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
                </nav>
            </SidebarWrapper>
            
            <!-- Основной контент -->
            <main class="flex-1">
                <!-- Заголовок страницы -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Мои объявления</h1>
                </div>

                <!-- Промо-карточки -->
                <div class="mb-6">
                    <div class="promo-carousel">
                        <ul class="promo-list">
                            <li class="promo-item">
                                <PromoCard 
                                    :card="{
                                        title: 'Скидки и акции',
                                        description: 'настройте для покупателей',
                                        icon: '/images/promo-icon.svg',
                                        link: '/promotions',
                                        badge: 'Новое'
                                    }"
                                />
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Основной контент с правильными отступами -->
                <div class="space-y-6">
                    <!-- Вкладки -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button 
                                v-for="tab in tabs" 
                                :key="tab.key"
                                @click="activeTab = tab.key"
                                :class="[
                                    'py-2 px-1 border-b-2 font-medium text-sm',
                                    activeTab === tab.key
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                {{ tab.title }}
                                <span v-if="tab.count > 0" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs font-medium">
                                    {{ tab.count }}
                                </span>
                            </button>
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
            </main>
        </div>
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
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import SidebarWrapper from '../Components/Layout/SidebarWrapper.vue'
import PromoCard from '../Components/Profile/PromoCard.vue'
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

// Активная вкладка
const activeTab = ref('paused')

// Вкладки
const tabs = computed(() => [
    {
        key: 'paused',
        title: 'Ждут действий',
        count: props.profiles.filter(p => p.status === 'paused').length
    },
    {
        key: 'active',
        title: 'Активные',
        count: props.profiles.filter(p => p.status === 'active').length
    },
    {
        key: 'draft',
        title: 'Черновики',
        count: props.profiles.filter(p => p.status === 'draft').length
    },
    {
        key: 'archived',
        title: 'Архив',
        count: props.profiles.filter(p => p.status === 'archived').length
    }
])

// Фильтрация объявлений по активной вкладке
const filteredProfiles = computed(() => {
    return props.profiles.filter(profile => {
        switch (activeTab.value) {
            case 'paused':
                return profile.status === 'paused'
            case 'active':
                return profile.status === 'active'
            case 'draft':
                return profile.status === 'draft'
            case 'archived':
                return profile.status === 'archived'
            default:
                return true
        }
    })
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
.promo-carousel {
  @apply overflow-hidden;
}

.promo-list {
  @apply flex gap-4;
}

.promo-item {
  @apply flex-shrink-0;
}

.items-list {
  @apply space-y-4;
}
</style>