<template>
    <div class="master-gallery">
        <!-- Основное изображение с лейблами -->
        <div class="relative group">
            <!-- Главное фото -->
            <div 
                @click="openGallery(0)"
                class="relative aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden cursor-pointer"
            >
                <img 
                    v-if="mainImage"
                    :src="mainImage"
                    :alt="masterName"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                >
                
                <!-- Маркетинговые лейблы (как на Ozon) -->
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                    <!-- Премиум статус -->
                    <div 
                        v-if="isPremium"
                        class="px-3 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full text-sm font-medium flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Премиум
                    </div>

                    <!-- Высокий рейтинг -->
                    <div 
                        v-if="rating >= 4.8"
                        class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm font-medium flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ rating }} рейтинг
                    </div>

                    <!-- Количество отзывов -->
                    <div 
                        v-if="reviewsCount > 50"
                        class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium"
                    >
                        {{ reviewsCount }}+ отзывов
                    </div>
                </div>

                <!-- Индикатор количества фото -->
                <div class="absolute bottom-4 right-4 px-3 py-1 bg-black/60 text-white rounded-full text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ images.length }}
                </div>

                <!-- Кнопка "Смотреть все фото" при наведении -->
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <span class="px-4 py-2 bg-white text-gray-900 rounded-lg font-medium">
                        Смотреть все фото
                    </span>
                </div>
            </div>
        </div>

        <!-- Превью остальных фото (как на Ozon) -->
        <div class="grid grid-cols-4 gap-2 mt-2">
            <button
                v-for="(image, index) in previewImages"
                :key="index"
                @click="openGallery(index + 1)"
                class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden group"
            >
                <img 
                    :src="image"
                    :alt="`Фото ${index + 2}`"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                    loading="lazy"
                >
                
                <!-- Затемнение на последнем превью если фото больше -->
                <div 
                    v-if="index === 3 && images.length > 5"
                    class="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-medium"
                >
                    +{{ images.length - 5 }}
                </div>
            </button>
        </div>

        <!-- Модальная галерея -->
        <ImageGalleryModal
            v-model="isGalleryOpen"
            :images="images"
            :initial-index="selectedImageIndex"
            @close="isGalleryOpen = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import ImageGalleryModal from '@/Components/Common/ImageGalleryModal.vue'

// Пропсы
const props = defineProps({
    images: {
        type: Array,
        required: true,
        validator: (value) => value.length > 0
    },
    masterName: {
        type: String,
        default: 'Мастер'
    },
    isPremium: {
        type: Boolean,
        default: false
    },
    rating: {
        type: Number,
        default: 0
    },
    reviewsCount: {
        type: Number,
        default: 0
    }
})

// Состояние
const isGalleryOpen = ref(false)
const selectedImageIndex = ref(0)

// Вычисляемые свойства
const mainImage = computed(() => props.images[0])
const previewImages = computed(() => props.images.slice(1, 5))

// Методы
const openGallery = (index) => {
    selectedImageIndex.value = index
    isGalleryOpen.value = true
}
</script>

<style scoped>
/* Анимации при наведении */
.master-gallery img {
    will-change: transform;
}
</style>