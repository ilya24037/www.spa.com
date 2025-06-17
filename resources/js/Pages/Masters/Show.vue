<template>
    <AppLayout>
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
                                    {{ master.display_name }}
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
                                <span>{{ master.metro_station }}</span>
                            </div>
                            <div v-if="master.district" class="flex items-center gap-2 mt-1">
                                <BuildingOfficeIcon class="w-4 h-4" />
                                <span>{{ master.district }}</span>
                            </div>
                        </div>
                    </div>
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
                            <p>{{ master.bio }}</p>
                            
                            <h3 class="mt-6">Образование и сертификаты</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                                <div 
                                    v-for="cert in master.certificates"
                                    :key="cert.id"
                                    class="border rounded-lg p-4 text-center"
                                >
                                    <img :src="cert.image" :alt="cert.name" class="w-full h-32 object-cover rounded mb-2">
                                    <p class="text-sm">{{ cert.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Отзывы -->
                    <div v-if="activeTab === 'reviews'">
                        <ReviewsList :reviews="reviews" :master-id="master.id" />
                    </div>

                    <!-- Фото работ -->
                    <div v-if="activeTab === 'portfolio'">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <img 
                                v-for="(photo, index) in portfolio"
                                :key="index"
                                :src="photo"
                                class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90"
                                @click="openGallery(index)"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Фиксированная панель записи -->
            <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 md:hidden">
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
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { HeartIcon, ShareIcon, MapPinIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import StarRating from '@/Components/Common/StarRating.vue'
import ServiceCard from '@/Components/Cards/ServiceCard.vue'
import ReviewsList from '@/Components/Reviews/ReviewsList.vue'
import BookingModal from '@/Components/Booking/BookingModal.vue'
import { useMasterStore } from '@/stores/masterStore'

const props = defineProps({
    master: Object,
    services: Array,
    reviews: Array,
    portfolio: Array
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
            title: props.master.display_name,
            text: `Мастер массажа ${props.master.display_name}`,
            url: window.location.href
        })
    }
}

const openBookingModal = (service = null) => {
    selectedService.value = service
    showBookingModal.value = true
}

const openGallery = (index) => {
    // TODO: Implement gallery viewer
}
</script>