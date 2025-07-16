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
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ userName }}</div>
                            <div class="text-sm text-gray-500">Личный кабинет</div>
                        </div>
                    </div>
                </div>

                <!-- Меню -->
                <nav class="p-4 space-y-1">
                    <Link 
                        href="/dashboard" 
                        :class="menuItemClass(isCurrentRoute('/dashboard'))"
                    >
                        <span>📊 Dashboard</span>
                    </Link>
                    <Link 
                        href="/additem" 
                        :class="menuItemClass(isCurrentRoute('/additem'))"
                    >
                        <span>➕ Разместить объявление</span>
                    </Link>
                    <Link 
                        href="/profile" 
                        :class="menuItemClass(isCurrentRoute('/profile'))"
                    >
                        <span>👤 Мой профиль</span>
                    </Link>
                </nav>
            </SidebarWrapper>

            <!-- Основной контент -->
            <main class="flex-1">
                <ContentCard>
                    <template #header>
                        <!-- Хлебные крошки -->
                        <nav class="flex mb-4" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                <li class="inline-flex items-center">
                                    <Link 
                                        href="/"
                                        class="text-gray-500 hover:text-gray-700 transition-colors"
                                    >
                                        Главная
                                    </Link>
                                    <svg 
                                        class="w-4 h-4 text-gray-400 mx-2" 
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </li>
                                <li>
                                    <span class="text-gray-900 font-medium">
                                        {{ selectedCategory ? getCategoryName(selectedCategory) : 'Разместить объявление' }}
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </template>

                    <!-- Выбор категории (показывается если категория не выбрана) -->
                    <div v-if="!selectedCategory">
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
                                v-for="category in props.categories"
                                :key="category.id"
                                class="relative group"
                            >
                                <!-- Карточка категории -->
                                <button 
                                    @click="selectCategory(category.id)"
                                    type="button"
                                    class="w-full block bg-gray-50 rounded-xl p-6 hover:bg-gray-100 transition-all duration-300 transform hover:-translate-y-1 border-2 border-transparent hover:border-blue-200"
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
                                </button>
                            </div>
                        </div>

                        <!-- Почему стоит разместить объявление у нас -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
                                Почему стоит разместить объявление у нас?
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-2xl">👁️</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-2">Высокая видимость</h3>
                                    <p class="text-sm text-gray-600">Ваше объявление увидят тысячи потенциальных клиентов</p>
                                </div>
                                
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-2xl">💰</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-2">Доступные цены</h3>
                                    <p class="text-sm text-gray-600">Размещение объявлений по выгодным тарифам</p>
                                </div>
                                
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <span class="text-2xl">⚡</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-2">Быстрая модерация</h3>
                                    <p class="text-sm text-gray-600">Объявления проверяются и публикуются в течение часа</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Форма создания объявления (показывается когда категория выбрана) -->
                    <div v-else>
                        <!-- Заголовок с кнопкой "Назад" -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-4">
                                <button 
                                    @click="goBackToCategories"
                                    type="button"
                                    class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Назад к категориям
                                </button>
                                <div class="h-6 w-px bg-gray-300"></div>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    {{ getCategoryName(selectedCategory) }}
                                </h1>
                            </div>
                        </div>

                        <!-- Форма объявления -->
                        <AdForm 
                            :category="selectedCategory"
                            :categories="props.categories"
                            @success="handleFormSuccess"
                        />
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
import AdForm from '@/Components/Form/AdForm.vue'

// Получаем данные от контроллера
const props = defineProps({
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

// Внутреннее состояние для выбранной категории
const selectedCategory = ref(null)

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

// Методы для работы с категориями
const selectCategory = (categoryId) => {
    selectedCategory.value = categoryId
}

const goBackToCategories = () => {
    selectedCategory.value = null
}

const getCategoryName = (categoryId) => {
    const category = props.categories.find(cat => cat.id === categoryId)
    return category ? category.name : 'Неизвестная категория'
}

const handleFormSuccess = (data) => {
    // Перенаправляем на страницу объявления или показываем успешное сообщение
    console.log('Объявление создано:', data)
    // Можно добавить toast уведомление или перенаправление
}
</script>

<script>
export default {
    layout: AppLayout
}
</script>