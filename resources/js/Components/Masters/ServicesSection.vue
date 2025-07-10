<template>
    <AppLayout :title="`${master.user.name} - Мастер массажа`">
        <div class="min-h-screen bg-gray-50">
            <!-- Хлебные крошки -->
            <div class="container mx-auto px-4 py-4">
                <nav class="flex text-sm text-gray-600">
                    <Link href="/" class="hover:text-purple-600">Главная</Link>
                    <span class="mx-2">/</span>
                    <Link href="/masters" class="hover:text-purple-600">Мастера</Link>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ master.user.name }}</span>
                </nav>
            </div>

            <!-- Основной контент -->
            <div class="container mx-auto px-4 pb-12">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Левая колонка - Галерея и основная информация -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Галерея фотографий -->
                        <MasterGalleryPreview
                            :images="masterImages"
                            :master-name="master.user.name"
                            :is-premium="master.is_premium"
                            :rating="master.rating"
                            :reviews-count="master.reviews_count"
                        />

                        <!-- Основная информация -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">{{ master.user.name }}</h1>
                                    <p class="text-gray-600 mt-1">{{ master.specialization || 'Мастер массажа' }}</p>
                                </div>
                                
                                <!-- Рейтинг -->
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="ml-1 font-semibold">{{ master.rating || '5.0' }}</span>
                                    </div>
                                    <span class="text-gray-500">({{ master.reviews_count || 0 }} отзывов)</span>
                                </div>
                            </div>

                            <!-- Описание -->
                            <div class="prose prose-gray max-w-none">
                                <h3 class="text-lg font-semibold mb-2">О мастере</h3>
                                <p class="text-gray-700">{{ master.bio || 'Профессиональный мастер массажа с большим опытом работы.' }}</p>
                            </div>

                            <!-- Опыт и образование -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Опыт работы</p>
                                        <p class="font-semibold">{{ master.experience_years || 5 }} лет</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Сертификаты</p>
                                        <p class="font-semibold">{{ master.certificates_count || 12 }} шт</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Услуги мастера -->
                        <ServicesSection :services="master.services || []" />

                        <!-- Отзывы -->
                        <ReviewsSection :master-id="master.id" :reviews="master.reviews || []" />
                    </div>

                    <!-- Правая колонка - Виджет бронирования -->
                    <div class="lg:sticky lg:top-20 h-fit space-y-4">
                        <!-- Виджет бронирования -->
                        <BookingWidget :master="master" />

                        <!-- Похожие мастера -->
                        <SimilarMastersSection :current-master-id="master.id" :category-id="master.services?.[0]?.category_id" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
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
    }
})

// Вычисляемые свойства
const masterImages = computed(() => {
    // Если есть галерея фотографий
    if (props.master.gallery && props.master.gallery.length > 0) {
        return props.master.gallery.map(img => img.url || img)
    }
    
    // Если есть только аватар
    if (props.master.avatar_url) {
        return [props.master.avatar_url]
    }
    
    // Заглушки для демо
    return [
        '/images/masters/demo-1.jpg',
        '/images/masters/demo-2.jpg',
        '/images/masters/demo-3.jpg',
        '/images/masters/demo-4.jpg',
        '/images/masters/demo-5.jpg',
        '/images/masters/demo-6.jpg'
    ]
})
</script>