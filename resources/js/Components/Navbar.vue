// resources/js/Components/Layout/Navbar.vue
<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Логотип и название -->
                <div class="flex items-center space-x-8">
                    <Link :href="route('home')" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SPA.COM</span>
                    </Link>

                    <!-- Геолокация -->
                    <button 
                        @click="openLocationModal"
                        class="flex items-center text-gray-700 hover:text-gray-900 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">{{ currentCity }}</span>
                    </button>
                </div>

                <!-- Центральная часть - кнопка каталога и поиск -->
                <div class="flex items-center space-x-4 flex-1 max-w-2xl mx-8">
                    <!-- Кнопка Каталог -->
                    <button 
                        @click="toggleCatalog"
                        class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Каталог
                    </button>

                    <!-- Поиск -->
                    <div class="flex-1 relative">
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Поиск услуг и мастеров..."
                            class="w-full pl-10 pr-16 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button 
                            @click="handleSearch"
                            class="absolute right-2 top-1 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Найти
                        </button>
                    </div>
                </div>

                <!-- Правая часть -->
                <div class="flex items-center space-x-6">
                    <!-- Навигационные ссылки -->
                    <nav class="hidden lg:flex items-center space-x-6">
                        <Link 
                            :href="route('masters.index')" 
                            class="text-gray-700 hover:text-gray-900 font-medium"
                        >
                            Мастера
                        </Link>
                        <Link 
                            :href="route('services.index')" 
                            class="text-gray-700 hover:text-gray-900 font-medium"
                        >
                            Услуги
                        </Link>
                    </nav>

                    <!-- Избранное -->
                    <Link 
                        :href="route('favorites.index')"
                        class="relative p-2 text-gray-700 hover:text-gray-900"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span 
                            v-if="favoritesCount > 0"
                            class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                        >
                            {{ favoritesCount }}
                        </span>
                        <span class="ml-2 hidden lg:inline">Избранное</span>
                    </Link>

                    <!-- Сравнение -->
                    <Link 
                        :href="route('compare.index')"
                        class="relative p-2 text-gray-700 hover:text-gray-900"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span 
                            v-if="compareCount > 0"
                            class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                        >
                            {{ compareCount }}
                        </span>
                        <span class="ml-2 hidden lg:inline">Сравнить</span>
                    </Link>

                    <!-- Кнопка размещения объявления -->
                    <Link 
                        :href="route('masters.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Разместить объявление
                    </Link>
                </div>
            </div>
        </div>

        <!-- Выпадающий каталог -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="showCatalog" class="absolute left-0 right-0 bg-white shadow-lg z-50">
                <div class="container mx-auto px-4 py-6">
                    <div class="grid grid-cols-4 gap-6">
                        <div v-for="category in categories" :key="category.id">
                            <h3 class="font-semibold text-gray-900 mb-3">{{ category.name }}</h3>
                            <ul class="space-y-2">
                                <li v-for="subcat in category.subcategories" :key="subcat.id">
                                    <Link 
                                        :href="route('services.category', subcat.slug)"
                                        class="text-gray-600 hover:text-blue-600 transition-colors"
                                    >
                                        {{ subcat.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Реактивные данные
const searchQuery = ref('')
const showCatalog = ref(false)
const currentCity = ref('Москва')

// Вычисляемые свойства
const favoritesCount = computed(() => page.props.favoritesCount || 0)
const compareCount = computed(() => page.props.compareCount || 0)
const categories = computed(() => page.props.categories || [])

// Методы
const toggleCatalog = () => {
    showCatalog.value = !showCatalog.value
}

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        // Переход на страницу поиска с параметрами
        window.location.href = `/search?q=${encodeURIComponent(searchQuery.value)}`
    }
}

const openLocationModal = () => {
    // Открытие модального окна выбора города
    console.log('Открыть выбор города')
}

// Закрытие каталога при клике вне
const handleClickOutside = (event) => {
    if (showCatalog.value && !event.target.closest('.catalog-dropdown')) {
        showCatalog.value = false
    }
}

// Слушатель для закрытия каталога
if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside)
}
</script>