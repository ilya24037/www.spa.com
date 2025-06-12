// resources/js/Pages/Home.vue
<template>
    <AppLayout>
        <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
        
        <div class="flex h-[calc(100vh-64px)]">
            <!-- Левая панель с фильтрами -->
            <FilterPanel 
                :initial-filters="filters"
                @filters-updated="handleFiltersUpdate"
            />

            <!-- Основной контент -->
            <div class="flex-1 flex">
                <!-- Список мастеров (левая половина) -->
                <div class="w-1/2 border-r border-gray-200">
                    <!-- Верхняя панель -->
                    <div class="bg-white border-b border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <!-- Заголовок и количество -->
                            <div>
                                <h1 class="text-lg font-semibold text-gray-900">
                                    СПА-услуги в {{ currentDistrict || 'Москве' }}
                                </h1>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ totalMasters }} {{ pluralize(totalMasters, ['объявление', 'объявления', 'объявлений']) }}
                                </p>
                            </div>

                            <!-- Переключатели вида и сортировка -->
                            <div class="flex items-center space-x-4">
                                <!-- Переключатель вида -->
                                <div class="flex items-center bg-gray-100 rounded-lg p-1">
                                    <button 
                                        @click="viewMode = 'list'"
                                        :class="[
                                            'p-1.5 rounded',
                                            viewMode === 'list' ? 'bg-white shadow-sm' : 'text-gray-600'
                                        ]"
                                        title="Список"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="viewMode = 'grid'"
                                        :class="[
                                            'p-1.5 rounded',
                                            viewMode === 'grid' ? 'bg-white shadow-sm' : 'text-gray-600'
                                        ]"
                                        title="Плитка"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="showMapOnly = !showMapOnly"
                                        :class="[
                                            'p-1.5 rounded',
                                            showMapOnly ? 'bg-white shadow-sm' : 'text-gray-600'
                                        ]"
                                        title="Карта"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Сортировка -->
                                <div class="relative" ref="sortDropdown">
                                    <button 
                                        @click="showSortMenu = !showSortMenu"
                                        class="flex items-center space-x-1 text-sm text-gray-700 hover:text-gray-900"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                        </svg>
                                        <span>Сортировка</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Выпадающее меню сортировки -->
                                    <transition
                                        enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95"
                                    >
                                        <div 
                                            v-if="showSortMenu"
                                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20"
                                        >
                                            <div class="py-1">
                                                <button
                                                    v-for="option in sortOptions"
                                                    :key="option.value"
                                                    @click="setSortBy(option.value)"
                                                    :class="[
                                                        'w-full text-left px-4 py-2 text-sm hover:bg-gray-100',
                                                        sortBy === option.value ? 'text-blue-600 bg-blue-50' : 'text-gray-700'
                                                    ]"
                                                >
                                                    {{ option.label }}
                                                </button>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                        <!-- Дополнительные фильтры -->
                        <div class="bg-gray-50 border-b border-gray-200 px-4 py-2">
                            <div class="flex items-center justify-between">
                                <!-- Переключатель "Сначала из Москвы" -->
                                <label class="flex items-center cursor-pointer">
                                    <input 
                                        type="checkbox"
                                        v-model="localPriorityEnabled"
                                        class="sr-only"
                                    >
                                    <div class="relative">
                                        <div 
                                            :class="[
                                                'w-10 h-6 rounded-full transition-colors',
                                                localPriorityEnabled ? 'bg-blue-600' : 'bg-gray-300'
                                            ]"
                                        ></div>
                                        <div 
                                            :class="[
                                                'absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform',
                                                localPriorityEnabled ? 'translate-x-4' : 'translate-x-0'
                                            ]"
                                        ></div>
                                    </div>
                                    <span class="ml-3 text-sm text-gray-700">Сначала из Москвы</span>
                                </label>

                                <!-- Сохранить поиск -->
                                <button class="flex items-center text-sm text-gray-700 hover:text-gray-900">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    Сохранить поиск
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Список карточек -->
                    <div class="overflow-y-auto h-[calc(100%-120px)]">
                        <!-- Состояние загрузки -->
                        <div v-if="loading" class="p-4">
                            <div 
                                v-for="n in 5" 
                                :key="`skeleton-${n}`"
                                class="bg-white rounded-lg shadow-sm p-4 mb-4 animate-pulse"
                            >
                                <div class="flex space-x-4">
                                    <div class="w-32 h-32 bg-gray-200 rounded-lg"></div>
                                    <div class="flex-1">
                                        <div class="h-6 bg-gray-200 rounded w-1/3 mb-2"></div>
                                        <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                                        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Карточки мастеров -->
                        <div 
                            v-else-if="masters.length > 0"
                            :class="[
                                'p-4',
                                viewMode === 'grid' ? 'grid grid-cols-2 gap-4' : 'space-y-4'
                            ]"
                        >
                            <MasterCard 
                                v-for="master in masters"
                                :key="master.id"
                                :master="master"
                                :view-mode="viewMode"
                                @click="selectMaster(master)"
                                :class="[
                                    selectedMaster?.id === master.id ? 'ring-2 ring-blue-500' : ''
                                ]"
                            />
                        </div>

                        <!-- Пустой результат -->
                        <div v-else class="flex flex-col items-center justify-center py-16">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">Ничего не найдено</h3>
                            <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
                        </div>

                        <!-- Пагинация -->
                        <div v-if="totalPages > 1" class="p-4 border-t border-gray-200">
                            <Pagination 
                                :current-page="currentPage"
                                :total-pages="totalPages"
                                @page-changed="goToPage"
                            />
                        </div>
                    </div>
                </div>

                <!-- Карта (правая половина) -->
                <div class="w-1/2 relative">
                    <YandexMap 
                        :masters="masters"
                        :selected-master="selectedMaster"
                        :center="mapCenter"
                        :zoom="mapZoom"
                        @marker-clicked="selectMaster"
                        @bounds-changed="updateMapBounds"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FilterPanel from '@/Components/Filters/FilterPanel.vue'
import MasterCard from '@/Components/Masters/MasterCard.vue'
import YandexMap from '@/Components/Map/YandexMap.vue'
import Pagination from '@/Components/Common/Pagination.vue'

// Пропсы от сервера
const props = defineProps({
    masters: Array,
    filters: Object,
    totalMasters: Number,
    currentPage: Number,
    totalPages: Number,
    currentDistrict: String,
    mapCenter: {
        type: Object,
        default: () => ({ lat: 55.7558, lng: 37.6173 })
    }
})

// Состояние
const loading = ref(false)
const viewMode = ref('list')
const showMapOnly = ref(false)
const showSortMenu = ref(false)
const localPriorityEnabled = ref(true)
const sortBy = ref('default')
const selectedMaster = ref(null)
const mapZoom = ref(12)

// Опции сортировки
const sortOptions = [
    { value: 'default', label: 'По умолчанию' },
    { value: 'price_asc', label: 'Дешевле' },
    { value: 'price_desc', label: 'Дороже' },
    { value: 'rating', label: 'По рейтингу' },
    { value: 'reviews', label: 'По отзывам' },
    { value: 'distance', label: 'По удалённости' }
]

// Методы
const pluralize = (count, forms) => {
    const cases = [2, 0, 1, 1, 1, 2]
    return forms[(count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10 < 5) ? count % 10 : 5]]
}

const handleFiltersUpdate = (newFilters) => {
    loading.value = true
    router.get(route('home'), {
        ...newFilters,
        sort: sortBy.value,
        local_priority: localPriorityEnabled.value
    }, {
        preserveState: true,
        onFinish: () => {
            loading.value = false
        }
    })
}

const setSortBy = (value) => {
    sortBy.value = value
    showSortMenu.value = false
    handleFiltersUpdate(props.filters)
}

const selectMaster = (master) => {
    selectedMaster.value = master
}

const goToPage = (page) => {
    router.get(route('home'), {
        ...props.filters,
        page: page
    }, {
        preserveState: true,
        preserveScroll: false
    })
}

const updateMapBounds = (bounds) => {
    // Обновление списка мастеров при изменении области карты
    console.log('Map bounds changed:', bounds)
}

// Закрытие меню сортировки при клике вне
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            showSortMenu.value = false
        }
    })
})
</script>