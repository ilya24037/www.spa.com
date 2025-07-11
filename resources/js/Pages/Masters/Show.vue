<template>
        <div class="min-h-screen bg-gray-50">
            <!-- Контейнер страницы -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Хлебные крошки -->
                <nav class="mb-6">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link href="/" class="text-gray-500 hover:text-gray-700">
                                Главная
                            </Link>
                        </li>
                        <li class="text-gray-400">/</li>
                        <li>
                            <Link href="/search" class="text-gray-500 hover:text-gray-700">
                                Каталог мастеров
                            </Link>
                        </li>
                        <li class="text-gray-400">/</li>
                        <li class="text-gray-900 font-medium">
                            {{ master.name }}
                        </li>
                    </ol>
                </nav>
                <!-- Основной контент -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Левая колонка (2/3 на десктопе) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Галерея и основная информация -->
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <!-- Галерея фотографий -->
                            <MasterGalleryPreview
                                :images="masterImages"
                                :master-name="master.name"
                                :is-premium="master.is_premium"
                                :rating="master.rating"
                                :reviews-count="master.reviews_count"
                            />
                            
                            <!-- Основная информация о мастере -->
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h1 class="text-2xl font-bold text-gray-900">{{ master.name }}</h1>
                                        <p class="text-gray-600 mt-1">{{ master.specialization || 'Мастер массажа' }}</p>
                                    </div>
                                    
                                    <!-- Рейтинг -->
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            <span class="ml-1 font-semibold">{{ master.rating || 5.0 }}</span>
                                        </div>
                                        <span class="text-gray-600">({{ master.reviews_count || 0 }} отзывов)</span>
                                    </div>
                                </div>
                                
                                <!-- О мастере -->
                                <div v-if="master.description" class="mb-6">
                                    <h3 class="font-semibold text-gray-900 mb-2">О мастере</h3>
                                    <p class="text-gray-700 leading-relaxed">{{ master.description }}</p>
                                </div>
                                
                                <!-- Статистика -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ master.experience_years || 5 }}</div>
                                        <div class="text-sm text-gray-600">лет опыта</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ master.services_count || 12 }}</div>
                                        <div class="text-sm text-gray-600">видов услуг</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ master.clients_count || 450 }}</div>
                                        <div class="text-sm text-gray-600">довольных клиентов</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ master.certificates_count || 8 }}</div>
                                        <div class="text-sm text-gray-600">сертификатов</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Услуги и цены -->
                        <ServicesSection :services="master.services || []" />
                        
                        <!-- Отзывы -->
                        <ReviewsSection 
                            :master-id="master.id"
                            :reviews="master.reviews || []" 
                        />
                    </div>
                    
                    <!-- Правая колонка (1/3 на десктопе) -->
                    <div class="space-y-6">
                        <!-- Виджет бронирования (sticky) -->
                        <div class="sticky top-4">
                            <BookingWidget :master="master" />
                        </div>
                    </div>
                </div>
                
                <!-- Похожие мастера -->
                <div class="mt-16">
                    <SimilarMastersSection 
                        :masters="master.similar_masters || []"
                        :current-master="master" 
                    />
                </div>
            </div>
        </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

// Импортируем существующие компоненты с правильными путями
import MasterGalleryPreview from '@/Components/Masters/MasterGalleryPreview.vue'
import BookingWidget from '@/Components/Masters/BookingWidget/index.vue'
import ServicesSection from '@/Components/Masters/ServicesSection.vue'
import ReviewsSection from '@/Components/Masters/ReviewsSection.vue'
import SimilarMastersSection from '@/Components/Masters/SimilarMastersSection.vue'

// Пропсы
const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    gallery: {
        type: Array,
        default: () => []
    },
    meta: {
        type: Object,
        default: () => ({})
    },
    similarMasters: {
        type: Array,
        default: () => []
    }
})

// Вычисляемые свойства
const masterImages = computed(() => {
    // Если есть галерея фотографий из контроллера
    if (props.master.gallery && props.master.gallery.length > 0) {
        return props.master.gallery.map(img => img.url || img)
    }
    
    // Если есть только аватар
    if (props.master.avatar) {
        return [props.master.avatar]
    }
    
    // Заглушки для демо
    return [
        '/images/masters/demo-1.jpg',
        '/images/masters/demo-2.jpg',
        '/images/masters/demo-3.jpg',
        '/images/masters/demo-4.jpg'
    ]
})
</script>

<style scoped>
/* Стили для sticky виджета на мобильных */
@media (max-width: 1024px) {
    .sticky {
        position: relative !important;
        top: auto !important;
    }
}
</style>