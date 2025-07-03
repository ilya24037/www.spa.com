<!-- Страница создания объявления (/additem) -->
<template>
    <Head title="Разместить объявление" />
    
    <!-- Обертка с правильными отступами как в Dashboard -->
    <div class="py-6 lg:py-8">
        
        <!-- Основной контент с гэпом между блоками -->
        <div class="flex gap-6">
            
            <!-- Боковая панель через SidebarWrapper -->
            <SidebarWrapper 
                v-model="showMobileSidebar"
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
                                    :class="menuItemClass(isCurrentRoute('profile'))"
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
                                    :class="menuItemClass(isCurrentRoute('bookings'))"
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
                                    :class="menuItemClass(isCurrentRoute('favorites'))"
                                >
                                    Избранное
                                    <span v-if="counts?.favorites > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                                        {{ counts.favorites }}
                                    </span>
                                </Link>
                            </li>
                            <li>
                                <Link 
                                    href="/additem"
                                    :class="menuItemClass(isCurrentRoute('additem'))"
                                >
                                    Разместить объявление
                                </Link>
                            </li>
                            <li>
                                <Link 
                                    href="/profile/edit"
                                    :class="menuItemClass(isCurrentRoute('profile/edit'))"
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
                <ContentCard>
                    <template #header>
                        <!-- Хлебные крошки -->
                        <nav class="flex mb-4" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                <li v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
                                    <Link 
                                        v-if="breadcrumb.url" 
                                        :href="breadcrumb.url"
                                        class="text-gray-500 hover:text-gray-700 transition-colors"
                                    >
                                        {{ breadcrumb.name }}
                                    </Link>
                                    <span v-else class="text-gray-900 font-medium">{{ breadcrumb.name }}</span>
                                    
                                    <svg 
                                        v-if="index < breadcrumbs.length - 1"
                                        class="w-4 h-4 text-gray-400 mx-2" 
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </li>
                            </ol>
                        </nav>
                    </template>

                    <!-- Заголовок -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            Разместить объявление
                        </h1>
                        <p class="text-lg text-gray-600">
                            Выберите категорию услуг для создания объявления. Ваше объявление увидят тысячи клиентов.
                        </p>
                    </div>

                    <!-- Сетка категорий -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="relative group"
                        >
                            <!-- Карточка категории -->
                            <Link 
                                :href="`/additem/${category.id}`"
                                class="block bg-gray-50 rounded-xl p-6 hover:bg-gray-100 transition-all duration-300 transform hover:-translate-y-1 border-2 border-transparent hover:border-blue-200"
                            >
                                <!-- Популярный лейбл -->
                                <div v-if="category.popular" class="absolute top-4 right-4 z-10">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ⭐ Популярное
                                    </span>
                                </div>

                                <!-- 18+ лейбл -->
                                <div v-if="category.adult" class="absolute top-4 left-4 z-10">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        🔞 18+
                                    </span>
                                </div>

                                <!-- Контент карточки -->
                                <div class="text-center">
                                    <!-- Иконка -->
                                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-white rounded-full shadow-sm">
                                        <span class="text-3xl">{{ category.icon }}</span>
                                    </div>

                                    <!-- Название -->
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                                        {{ category.name }}
                                    </h3>

                                    <!-- Описание -->
                                    <p class="text-gray-600 text-sm mb-4">
                                        {{ category.description }}
                                    </p>

                                    <!-- Кнопка -->
                                    <div class="flex justify-center">
                                        <span class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg text-sm group-hover:bg-blue-700 transition-colors">
                                            Выбрать
                                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Дополнительная информация -->
                    <div class="bg-blue-50 rounded-xl p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 text-center">
                            Почему стоит разместить объявление у нас?
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Высокая видимость</h3>
                                <p class="text-gray-600 text-sm">Ваше объявление увидят тысячи потенциальных клиентов</p>
                            </div>
                            <div class="text-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Доступные цены</h3>
                                <p class="text-gray-600 text-sm">Размещение от 300₽ в месяц</p>
                            </div>
                            <div class="text-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Быстрая модерация</h3>
                                <p class="text-gray-600 text-sm">Публикация в течение 2-4 часов</p>
                            </div>
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
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

// Получаем данные от контроллера
defineProps({
  categories: {
    type: Array,
    required: true
  },
  breadcrumbs: {
    type: Array,
    required: true
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

// Состояние для мобильного меню
const showMobileSidebar = ref(false)

// Пользователь (копируем логику из Dashboard)
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
    return page.url.includes(routeName)
}

// Класс для пунктов меню
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
    isActive 
        ? 'bg-blue-50 text-blue-600 font-medium' 
        : 'text-gray-700 hover:bg-gray-50'
]
</script>