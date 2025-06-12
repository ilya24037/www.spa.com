<!-- resources/js/Pages/Home.vue -->
<template>
    <AppLayout>
        <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
        
        <!-- Контейнер как на Avito -->
        <div class="avito-layout">
            <!-- Карта на всю ширину под шапкой -->
            <div class="map-section">
                <div class="container mx-auto max-w-7xl px-4">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden" style="height: 400px;">
                        <div class="h-full flex items-center justify-center text-gray-500 bg-gray-100">
                            <div class="text-center p-8">
                                <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <p class="text-lg font-medium">Показать объявления на карте</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div class="container mx-auto max-w-7xl px-4 py-4">
                <div class="flex gap-4">
                    <!-- Левая панель с фильтрами -->
                    <aside class="w-72 flex-shrink-0">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <h2 class="font-semibold text-lg mb-4">Фильтры</h2>
                            <FilterPanel 
                                :initial-filters="filters || {}"
                                @filters-updated="handleFiltersUpdate"
                            />
                        </div>
                    </aside>

                    <!-- Список карточек -->
                    <div class="flex-1">
                        <!-- Заголовок и сортировка -->
                        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        СПА-услуги в {{ currentDistrict || 'Москве' }}
                                    </h1>
                                    <p class="text-gray-600 mt-1">
                                        {{ total || 0 }} {{ pluralize(total || 0, ['объявление', 'объявления', 'объявлений']) }}
                                    </p>
                                </div>

                                <!-- Сортировка -->
                                <div class="flex items-center gap-4">
                                    <span class="text-sm text-gray-600">Сортировать:</span>
                                    <select 
                                        v-model="sortBy"
                                        @change="handleSort"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="default">По умолчанию</option>
                                        <option value="price_asc">Дешевле</option>
                                        <option value="price_desc">Дороже</option>
                                        <option value="rating">По рейтингу</option>
                                        <option value="date">По дате</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Карточки мастеров -->
                        <div class="space-y-4">
                            <!-- Состояние загрузки -->
                            <div v-if="loading" class="text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p class="mt-2 text-gray-600">Загрузка...</p>
                            </div>

                            <!-- Карточки -->
                            <div v-else-if="mastersList.length > 0" class="grid grid-cols-1 gap-4">
                                <div 
                                    v-for="master in mastersList"
                                    :key="master.id"
                                    class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                                >
                                    <MasterCard 
                                        :master="master"
                                        view-mode="list"
                                        @click="selectMaster(master)"
                                    />
                                </div>
                            </div>

                            <!-- Пустой результат -->
                            <div v-else class="bg-white rounded-lg shadow-sm p-16 text-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Ничего не найдено</h3>
                                <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
                            </div>

                            <!-- Пагинация -->
                            <div v-if="lastPage > 1" class="bg-white rounded-lg shadow-sm p-4">
                                <Pagination 
                                    :current-page="currentPage"
                                    :total-pages="lastPage"
                                    @page-changed="goToPage"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FilterPanel from '@/Components/Filters/FilterPanel.vue'
import MasterCard from '@/Components/Masters/MasterCard.vue'
import Pagination from '@/Components/Common/Pagination.vue'

// Пропсы
const props = defineProps({
    masters: {
        type: [Array, Object],
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    total: {
        type: Number,
        default: 0
    },
    current_page: {
        type: Number,
        default: 1
    },
    last_page: {
        type: Number,
        default: 1
    },
    currentDistrict: {
        type: String,
        default: null
    }
})

// Состояние
const loading = ref(false)
const sortBy = ref('default')
const selectedMaster = ref(null)

// Вычисляемые свойства
const mastersList = computed(() => {
    // Если masters это объект с пагинацией
    if (props.masters?.data) {
        return props.masters.data
    }
    // Если masters это массив
    if (Array.isArray(props.masters)) {
        return props.masters
    }
    return []
})

const currentPage = computed(() => props.current_page || props.masters?.current_page || 1)
const lastPage = computed(() => props.last_page || props.masters?.last_page || 1)
const total = computed(() => props.total || props.masters?.total || 0)

// Методы
const pluralize = (count, forms) => {
    const cases = [2, 0, 1, 1, 1, 2]
    return forms[(count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10 < 5) ? count % 10 : 5]]
}

const handleFiltersUpdate = (newFilters) => {
    loading.value = true
    router.get('/', newFilters, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false
        }
    })
}

const handleSort = () => {
    handleFiltersUpdate({ ...props.filters, sort: sortBy.value })
}

const selectMaster = (master) => {
    selectedMaster.value = master
    // Можно открыть модальное окно или перейти на страницу мастера
}

const goToPage = (page) => {
    router.get('/', {
        ...props.filters,
        page: page
    }, {
        preserveState: true,
        preserveScroll: false
    })
}
</script>

<style scoped>
.avito-layout {
    /* Структура как на Avito */
}

.map-section {
    background-color: #f5f5f5;
    padding: 1rem 0;
    margin-bottom: 1rem;
}
</style>