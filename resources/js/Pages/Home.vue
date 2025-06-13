<!-- resources/js/Pages/Home.vue -->
<template>
    <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
    
    <!-- Карта на всю ширину как у Ozon -->
    <div class="map-section">
        <div class="page-container">
            <div class="map-wrapper">
                <YandexMap 
                    :masters="mastersList"
                    @bounds-changed="handleBoundsChange"
                    @marker-clicked="selectMaster"
                />
            </div>
        </div>
    </div>
    
    <!-- Основной контент -->
    <div class="main-content">
        <div class="page-container">
            <div class="content-grid">
                <!-- Фильтры слева -->
                <aside class="filters-sidebar">
                    <div class="filters-sticky">
                        <FilterPanel 
                            :initial-filters="filters" 
                            @filters-updated="handleFiltersUpdate"
                            :transparent="true"
                        />
                    </div>
                </aside>
                
                <!-- Контент справа -->
                <section class="products-section">
                    <!-- Заголовок -->
                    <div class="section-header">
                        <div class="header-top">
                            <h1 class="page-title">
                                СПА-услуги в {{ currentDistrict || 'Москве' }}
                            </h1>
                            <select 
                                v-model="sortBy"
                                @change="handleSort"
                                class="sort-select"
                            >
                                <option value="default">По популярности</option>
                                <option value="price_asc">Дешевле</option>
                                <option value="price_desc">Дороже</option>
                                <option value="rating">По рейтингу</option>
                                <option value="date">Новинки</option>
                            </select>
                        </div>
                        <div class="results-info">
                            {{ total || 0 }} {{ pluralize(total || 0, ['объявление', 'объявления', 'объявлений']) }}
                        </div>
                    </div>
                    
                    <!-- Сетка карточек -->
                    <div v-if="loading" class="loading-state">
                        <div class="loader"></div>
                        <p>Загрузка...</p>
                    </div>
                    
                    <div v-else-if="mastersList.length > 0" class="products-grid">
                        <MasterCard 
                            v-for="master in mastersList"
                            :key="master.id"
                            :master="master"
                            class="product-card"
                        />
                    </div>
                    
                    <!-- Пустое состояние -->
                    <div v-else class="empty-state">
                        <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3>Ничего не найдено</h3>
                        <p>Попробуйте изменить параметры поиска</p>
                    </div>
                    
                    <!-- Пагинация -->
                    <div v-if="lastPage > 1" class="pagination-wrapper">
                        <Pagination 
                            :current-page="currentPage"
                            :total-pages="lastPage"
                            @page-changed="goToPage"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import MasterCard from '@/Components/Masters/MasterCard.vue'
import YandexMap from '@/Components/Map/YandexMap.vue'
import FilterPanel from '@/Components/Filters/FilterPanel.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import AppLayout from '@/Layouts/AppLayout.vue'

// Определяем layout
defineOptions({ layout: AppLayout })

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
    cities: {
        type: Array,
        default: () => []
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
    if (props.masters?.data) {
        return props.masters.data
    }
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
    router.visit(route('masters.show', master.id))
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

const handleBoundsChange = (bounds) => {
    console.log('Map bounds changed:', bounds)
}
</script>

<style scoped>
/* Основные стили страницы */
:global(body) {
    background-color: #f5f5f5;
    margin: 0;
    padding: 0;
}

/* Контейнер страницы - как у Ozon */
.page-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Секция карты */
.map-section {
    background-color: #f5f5f5;
    padding: 12px 0;
}

.map-wrapper {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    height: 400px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
}

/* Основной контент */
.main-content {
    background-color: #f5f5f5;
    min-height: calc(100vh - 500px);
    padding-bottom: 40px;
}

/* Сетка контента */
.content-grid {
    display: flex;
    gap: 16px;
    margin-top: 16px;
}

/* Боковая панель фильтров */
.filters-sidebar {
    width: 240px;
    flex-shrink: 0;
}

.filters-sticky {
    position: sticky;
    top: 80px;
}

/* Секция товаров */
.products-section {
    flex: 1;
    min-width: 0;
}

/* Заголовок секции */
.section-header {
    margin-bottom: 16px;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    color: #001a35;
    margin: 0;
}

.results-info {
    font-size: 14px;
    color: #70757a;
}

/* Селект сортировки как у Ozon */
.sort-select {
    padding: 8px 32px 8px 12px;
    border: 1px solid #d5d7db;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: border-color 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
}

.sort-select:hover {
    border-color: #b3b5b9;
}

.sort-select:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Адаптивная сетка товаров как у Ozon */
.products-grid {
    display: grid;
    gap: 8px;
    /* Мобильные устройства: 2 колонки */
    grid-template-columns: repeat(2, 1fr);
}

/* Карточка товара */
.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.product-card:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* Состояние загрузки */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 0;
}

.loader {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3B82F6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-state p {
    color: #70757a;
    font-size: 14px;
}

/* Пустое состояние */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.empty-icon {
    width: 64px;
    height: 64px;
    color: #d5d7db;
    margin: 0 auto 16px;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 500;
    color: #001a35;
    margin: 0 0 8px 0;
}

.empty-state p {
    color: #70757a;
    margin: 0;
    font-size: 14px;
}

/* Пагинация */
.pagination-wrapper {
    margin-top: 32px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.04);
}

/* Адаптивность - маленькие планшеты */
@media (min-width: 576px) {
    .products-grid {
        /* 3 колонки на маленьких планшетах */
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Адаптивность - планшеты */
@media (min-width: 768px) {
    .page-container {
        padding: 0 20px;
    }
    
    .content-grid {
        gap: 20px;
    }
    
    .products-grid {
        /* 4 колонки на планшетах */
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    
    .map-wrapper {
        height: 450px;
    }
}

/* Адаптивность - десктоп */
@media (min-width: 1024px) {
    .page-container {
        padding: 0 24px;
    }
    
    .content-grid {
        gap: 24px;
    }
    
    .filters-sidebar {
        width: 256px;
    }
    
    .products-grid {
        /* 4 колонки на небольших десктопах */
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

/* Адаптивность - большие экраны */
@media (min-width: 1280px) {
    .products-grid {
        /* 5 колонок на больших экранах как у Ozon */
        grid-template-columns: repeat(5, 1fr);
    }
}

/* Адаптивность - очень большие экраны */
@media (min-width: 1440px) {
    .products-grid {
        /* 6 колонок на очень больших экранах */
        grid-template-columns: repeat(6, 1fr);
    }
}

/* Мобильная адаптивность */
@media (max-width: 767px) {
    .content-grid {
        flex-direction: column;
    }
    
    .filters-sidebar {
        width: 100%;
        margin-bottom: 16px;
    }
    
    .filters-sticky {
        position: static;
    }
    
    .header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .sort-select {
        width: 100%;
    }
    
    .map-wrapper {
        height: 300px;
    }
    
    .page-title {
        font-size: 20px;
    }
}

/* Мобильный вариант фильтров (опционально) */
@media (max-width: 767px) {
    /* Можно добавить кнопку "Фильтры" которая открывает модальное окно */
    .mobile-filter-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 12px;
        margin-bottom: 16px;
        background: white;
        border: 1px solid #d5d7db;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .mobile-filter-button:hover {
        border-color: #3B82F6;
        color: #3B82F6;
    }
}
</style>