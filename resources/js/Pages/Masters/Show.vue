<template>
    <!-- БЕЗ AppLayout - структура как в Home.vue -->
    <Head :title="pageTitle" />

    <!-- Обертка с правильными отступами как на главной -->
    <div class="py-6 lg:py-8">
        
        <!-- Хлебные крошки без фона -->
<Breadcrumbs
  :items="[
    { title: 'Главная', href: '/' },
    { title: 'Мастера', href: '/search' },
    { title: master.name }
  ]"
  class="mb-6"
/>
            </div>

        <!-- Основной контент с гэпом между блоками -->
        <div class="flex gap-6">
            <!-- Левая колонка - основной контент -->
            <div class="flex-1 space-y-6">
                <!-- Основная информация и галерея -->
                <ContentCard class="p-0 overflow-hidden">
                    <div class="lg:flex">
                        <!-- Галерея фотографий -->
                        <div class="lg:w-2/5 bg-gray-50">
                            <div class="relative">
                                <!-- Основное фото -->
                                <div class="aspect-[3/4] relative">
                                    <img 
                                        :src="currentPhoto" 
                                        :alt="master.name"
                                        class="w-full h-full object-cover"
                                        @error="handleImageError"
                                    >
                                    
                                    <!-- Бейджи -->
                                    <div class="absolute top-4 left-4 space-y-2">
                                        <span v-if="master.is_premium" 
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-lg">
                                            ⭐ ПРЕМИУМ
                                        </span>
                                        <span v-if="master.is_verified" 
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg">
                                            ✓ Проверен
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Миниатюры -->
                                <div v-if="allPhotos.length > 1" class="p-3 bg-white">
                                    <div class="flex gap-2 overflow-x-auto">
                                        <button
                                            v-for="(photo, index) in allPhotos.slice(0, 6)"
                                            :key="`thumb-${index}`"
                                            @click="currentPhotoIndex = index"
                                            class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all"
                                            :class="currentPhotoIndex === index ? 'border-indigo-500' : 'border-transparent'"
                                        >
                                            <img 
                                                :src="photo" 
                                                :alt="`Фото ${index + 1}`"
                                                class="w-full h-full object-cover"
                                                @error="handleImageError"
                                            >
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Информация о мастере -->
                        <div class="lg:w-3/5 p-6">
                            <!-- Заголовок -->
                            <div class="mb-4">
                                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                                    {{ master.name }}
                                </h1>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span>{{ master.age }} лет</span>
                                    <span>•</span>
                                    <span>Опыт {{ master.experience_years }} лет</span>
                                    <span>•</span>
                                    <span>ID: {{ master.id }}</span>
                                </div>
                            </div>
                            
                            <!-- Рейтинг и статистика -->
                            <div class="flex items-center gap-6 mb-6">
                                <div class="flex items-center gap-2">
                                    <!-- Простой рейтинг звездами -->
                                    <div class="flex text-yellow-400">
                                        <span v-for="i in 5" :key="i" class="text-lg">
                                            {{ i <= Math.round(master.rating || 5) ? '★' : '☆' }}
                                        </span>
                                    </div>
                                    <span class="font-semibold">{{ master.rating || '5.0' }}</span>
                                    <span class="text-gray-500">({{ master.reviews_count || 0 }} отзывов)</span>
                                </div>
                                <span v-if="master.is_available_now" class="text-green-600 flex items-center gap-1">
                                    <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                                    Доступен сейчас
                                </span>
                            </div>
                            
                            <!-- Описание -->
                            <div class="prose prose-sm max-w-none mb-6">
                                <p>{{ master.description || 'Профессиональный массажист с большим опытом работы.' }}</p>
                            </div>
                            
                            <!-- Локация -->
                            <div class="space-y-2 text-sm">
                                <div v-if="master.metro_station" class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Метро {{ master.metro_station }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>{{ master.district }}, {{ master.city }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </ContentCard>
                
                <!-- Услуги -->
                <ContentCard>
                    <template #header>
                        <h2 class="text-lg font-semibold">Услуги и цены</h2>
                    </template>
                    
                    <div class="space-y-3">
                        <div 
                            v-for="service in displayServices"
                            :key="service.id"
                            class="flex items-start justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                            @click="selectService(service)"
                        >
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900">{{ service.name }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ service.category }} • {{ service.duration }} минут</p>
                                <p v-if="service.description" class="text-sm text-gray-500 mt-2">{{ service.description }}</p>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-xl font-bold text-indigo-600">{{ formatPrice(service.price) }} ₽</div>
                                <button class="text-sm text-indigo-600 hover:text-indigo-700 mt-1">
                                    Записаться
                                </button>
                            </div>
                        </div>
                    </div>
                </ContentCard>
                
                <!-- Отзывы -->
                <ContentCard>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Отзывы ({{ master.reviews_count || 0 }})</h2>
                        </div>
                    </template>
                    
                    <div class="space-y-4">
                        <p class="text-gray-500">Отзывы будут добавлены позже</p>
                    </div>
                </ContentCard>
            </div>
            
            <!-- Правая колонка - липкий блок с ценой (десктоп) -->
            <SidebarWrapper
                v-model="showMobilePricePanel"
                :always-visible-desktop="true"
                position="right"
                :sticky="true"
                :sticky-top="90"
                width-class="w-80 lg:w-96"
                mobile-mode="bottom-sheet"
                content-class="p-6"
                :show-desktop-header="false"
            >
                <!-- Блок с ценой -->
                <div class="mb-6">
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="text-3xl font-bold text-gray-900">
                            от {{ formatPrice(master.price_from) }} ₽
                        </span>
                        <span class="text-gray-500">за сеанс</span>
                    </div>
                    <div v-if="master.price_to" class="text-sm text-gray-500">
                        до {{ formatPrice(master.price_to) }} ₽
                    </div>
                </div>
                
                <!-- Кнопки действий -->
                <div class="space-y-3 mb-6">
                    <button 
                        @click="showBookingModal = true"
                        class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-indigo-700 transition-colors"
                    >
                        Записаться на приём
                    </button>
                    
                    <button 
                        @click="showPhone = !showPhone"
                        class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ showPhone ? formatPhone(master.phone) : 'Показать телефон' }}
                    </button>
                </div>
                
                <!-- Действия -->
                <div class="flex gap-3 mb-6">
                    <button 
                        @click="toggleFavorite"
                        class="flex-1 py-2 px-3 border rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
                        :class="{ 'text-red-500 border-red-500': master.is_favorite }"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        {{ master.is_favorite ? 'В избранном' : 'В избранное' }}
                    </button>
                </div>
                
                <!-- График работы -->
                <div class="border-t pt-6">
                    <h3 class="font-semibold text-gray-900 mb-3">График работы</h3>
                    <div class="space-y-2 text-sm">
                        <div v-for="day in ['Пн-Пт', 'Сб-Вс']" :key="day" class="flex justify-between">
                            <span>{{ day }}</span>
                            <span>10:00 - 21:00</span>
                        </div>
                    </div>
                </div>
            </SidebarWrapper>
        </div>

        <!-- Мобильная кнопка записи -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t p-4 z-40">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold">от {{ formatPrice(master.price_from) }} ₽</span>
                <button 
                    @click="showMobilePricePanel = true"
                    class="text-indigo-600 text-sm"
                >
                    Подробнее
                </button>
            </div>
            <button 
                @click="showBookingModal = true"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium"
            >
                Записаться
            </button>
        </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import ContentCard from '@/Components/Layout/ContentCard.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'
// Пропсы
const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    meta: {
        type: Object,
        default: () => ({})
    }
})

// Состояния
const showPhone = ref(false)
const showBookingModal = ref(false)
const showMobilePricePanel = ref(false)
const currentPhotoIndex = ref(0)

// Вычисляемые свойства
const pageTitle = computed(() => `${props.master.name} - Массажист в ${props.master.city || 'Москве'}`)

const allPhotos = computed(() => {
    const photos = props.master.all_photos || 
                  props.master.photos || 
                  (props.master.avatar ? [props.master.avatar] : [])
    
    if (!photos) return ['/images/placeholder.jpg']
    return Array.isArray(photos) ? photos.filter(Boolean) : [photos].filter(Boolean)
})

const currentPhoto = computed(() => allPhotos.value[currentPhotoIndex.value] || '/images/placeholder.jpg')

const displayServices = computed(() => {
    return props.master.services || [
        { id: 1, name: 'Классический массаж', category: 'Массаж', duration: 60, price: 3000 },
        { id: 2, name: 'Расслабляющий массаж', category: 'Массаж', duration: 90, price: 4500 }
    ]
})

// Методы
const handleImageError = (e) => {
    e.target.src = '/images/placeholder.jpg'
}

const formatPhone = (phone) => {
    if (!phone) return ''
    return phone.replace(/(\d{3})(\d{3})(\d{2})(\d{2})/, '+7 ($1) $2-$3-$4')
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price || 0)
}

const toggleFavorite = () => {
    // TODO: Реализовать добавление в избранное
    console.log('Toggle favorite')
}

const selectService = (service) => {
    console.log('Selected service:', service)
    showBookingModal.value = true
}
</script>

<style scoped>
.aspect-\[3\/4\] {
    aspect-ratio: 3 / 4;
}
</style>