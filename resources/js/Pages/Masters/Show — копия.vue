<template>
    <!-- 🔥 ДОБАВЛЕНО: Компонент для meta-тегов -->
    <MetaTags :meta="meta" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Шапка профиля -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Фото -->
                    <div class="flex-shrink-0">
                        <img 
                            :src="master.avatar || '/images/no-avatar.jpg'" 
                            :alt="master.display_name"
                            class="w-32 h-32 rounded-full object-cover"
                        >
                    </div>
                    
                    <!-- Основная информация -->
                    <div class="flex-grow">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                    {{ master.display_name || master.name }}
                                    <span v-if="master.is_verified" class="text-green-500">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                        </svg>
                                    </span>
                                </h1>
                                
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <StarRating :rating="master.rating" />
                                        <span class="ml-2">{{ master.rating }} ({{ master.reviews_count }} отзывов)</span>
                                    </div>
                                    <span>•</span>
                                    <span>Опыт {{ master.experience_years }} лет</span>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-3">
                                    <span v-if="master.home_service" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Выезд на дом
                                    </span>
                                    <span v-if="master.salon_service" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Прием в салоне
                                    </span>
                                    <span v-if="master.is_premium" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ⭐ Премиум
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="flex gap-2">
                                <button 
                                    @click="toggleFavorite"
                                    class="p-2 rounded-lg border hover:bg-gray-50"
                                    :class="{ 'text-red-500 border-red-500': isFavorite }"
                                >
                                    <HeartIcon class="w-5 h-5" />
                                </button>
                                <button 
                                    @click="share"
                                    class="p-2 rounded-lg border hover:bg-gray-50"
                                >
                                    <ShareIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        
                        <!-- Локация -->
                        <div class="mt-4 text-gray-600">
                            <div v-if="master.metro_station" class="flex items-center gap-2">
                                <MapPinIcon class="w-4 h-4" />
                                <span>м. {{ master.metro_station }}</span>
                            </div>
                            <div v-if="master.district" class="flex items-center gap-2 mt-1">
                                <BuildingOfficeIcon class="w-4 h-4" />
                                <span>{{ master.district }}, {{ master.city }}</span>
                            </div>
                        </div>
                        
                        <!-- Цены -->
                        <div class="mt-4">
                            <div class="text-2xl font-bold text-gray-900">
                                от {{ formatPrice(master.price_from) }} ₽
                                <span v-if="master.price_to" class="text-base font-normal text-gray-600">
                                    до {{ formatPrice(master.price_to) }} ₽
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- О себе (краткое описание) -->
                <div v-if="master.description" class="mt-4 text-gray-700">
                    <p class="line-clamp-3">{{ master.description }}</p>
                </div>
            </div>

            <!-- Табы -->
            <div class="bg-white rounded-lg shadow-lg">
                <div class="border-b">
                    <nav class="flex -mb-px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'py-4 px-6 text-sm font-medium border-b-2 transition-colors',
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Контент табов -->
                <div class="p-6">
                    <!-- Услуги -->
                    <div v-if="activeTab === 'services'">
                        <div class="grid gap-4">
                            <ServiceCard
                                v-for="service in master.services"
                                :key="service.id"
                                :service="service"
                                @book="openBookingModal(service)"
                            />
                        </div>
                    </div>

                    <!-- О мастере -->
                    <div v-if="activeTab === 'about'">
                        <div class="prose max-w-none">
                            <p>{{ master.description || 'Информация о мастере скоро будет добавлена.' }}</p>
                            
                            <h3 v-if="master.certificates && master.certificates.length" class="mt-6">Образование и сертификаты</h3>
                            <div v-if="master.certificates && master.certificates.length" class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                                <div 
                                    v-for="(cert, index) in master.certificates"
                                    :key="index"
                                    class="border rounded-lg p-4 text-center"
                                >
                                    <img :src="cert.image || '/images/cert-placeholder.jpg'" :alt="cert.name" class="w-full h-32 object-cover rounded mb-2">
                                    <p class="text-sm">{{ cert.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Отзывы -->
                    <div v-if="activeTab === 'reviews'">
                        <ReviewsList :reviews="master.reviews || []" :master-id="master.id" />
                    </div>

                    <!-- Фото работ -->
                    <div v-if="activeTab === 'portfolio'">
                        <div v-if="master.photos && master.photos.length" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <img 
                                v-for="(photo, index) in master.photos"
                                :key="index"
                                :src="photo.path || photo"
                                class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90"
                                @click="openGallery(index)"
                            >
                        </div>
                        <div v-else class="text-center text-gray-500 py-8">
                            Фотографии работ скоро будут добавлены
                        </div>
                    </div>
                </div>
            </div>

            <!-- Похожие мастера -->
            <div v-if="similarMasters && similarMasters.length" class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Похожие мастера</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <MasterCard 
                        v-for="similar in similarMasters"
                        :key="similar.id"
                        :master="similar"
                    />
                </div>
            </div>

            <!-- Фиксированная панель записи (мобильная версия) -->
            <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 md:hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-lg font-bold">от {{ formatPrice(master.price_from) }} ₽</span>
                    <span class="text-sm text-gray-600">{{ master.services?.length || 0 }} услуг</span>
                </div>
                <button 
                    @click="openBookingModal()"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium"
                >
                    Записаться
                </button>
            </div>
        </div>

        <!-- Модальное окно бронирования -->
        <BookingModal 
            v-if="showBookingModal"
            :master="master"
            :service="selectedService"
            @close="showBookingModal = false"
        />
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { HeartIcon, ShareIcon, MapPinIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import MetaTags from '@/Components/MetaTags.vue' // 🔥 ДОБАВЛЕНО
import StarRating from '@/Components/Common/StarRating.vue'
import ServiceCard from '@/Components/Cards/ServiceCard.vue'
import ReviewsList from '@/Components/Reviews/ReviewsList.vue'
import BookingModal from '@/Components/Booking/BookingModal.vue'
import MasterCard from '@/Components/Cards/MasterCard.vue' // 🔥 ДОБАВЛЕНО для похожих мастеров
import { useMasterStore } from '@/stores/masterStore'

// 🔥 ОБНОВЛЕНО: Добавлен prop для meta
const props = defineProps({
    master: Object,
    meta: Object,        // Meta-теги для SEO
    similarMasters: Array // Похожие мастера
})

const masterStore = useMasterStore()
const activeTab = ref('services')
const showBookingModal = ref(false)
const selectedService = ref(null)

const tabs = [
    { id: 'services', name: 'Услуги' },
    { id: 'about', name: 'О мастере' },
    { id: 'reviews', name: 'Отзывы' },
    { id: 'portfolio', name: 'Фото работ' }
]

const isFavorite = computed(() => masterStore.isInFavorites(props.master.id))

const toggleFavorite = () => {
    masterStore.toggleFavorite(props.master.id)
}

const share = () => {
    if (navigator.share) {
        navigator.share({
            title: props.master.name || props.master.display_name,
            text: `Массажист ${props.master.name || props.master.display_name} в ${props.master.city}`,
            url: window.location.href
        })
    } else {
        // Fallback - копирование в буфер обмена
        navigator.clipboard.writeText(window.location.href)
        // Можно добавить toast уведомление
    }
}

const openBookingModal = (service = null) => {
    selectedService.value = service
    showBookingModal.value = true
}

const openGallery = (index) => {
    // TODO: Implement gallery viewer
    console.log('Open gallery at index:', index)
}

// 🔥 ДОБАВЛЕНО: Форматирование цены
const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price)
}
</script>