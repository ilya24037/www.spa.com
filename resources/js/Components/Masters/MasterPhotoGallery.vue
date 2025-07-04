<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="relative">
            <!-- Главное фото -->
            <div class="relative aspect-w-16 aspect-h-9 bg-gray-100">
                <img 
                    :src="currentPhoto" 
                    :alt="masterName"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                >
                
                <!-- Навигационные стрелки -->
                <button
                    v-if="photos.length > 1 && currentIndex > 0"
                    @click="previousPhoto"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110"
                >
                    <ChevronLeftIcon class="w-6 h-6 text-gray-700" />
                </button>
                
                <button
                    v-if="photos.length > 1 && currentIndex < photos.length - 1"
                    @click="nextPhoto"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110"
                >
                    <ChevronRightIcon class="w-6 h-6 text-gray-700" />
                </button>
                
                <!-- Бейджи -->
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                    <span v-if="isPremium" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-yellow-400 to-yellow-500 text-white shadow-lg">
                        ⭐ ТОП мастер
                    </span>
                    <span v-if="isVerified" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg">
                        ✓ Проверенный
                    </span>
                </div>
                
                <!-- Счетчик фото -->
                <div v-if="photos.length > 1" class="absolute top-4 right-4 bg-black/60 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                    {{ currentIndex + 1 }} / {{ photos.length }}
                </div>
                
                <!-- Кнопка полноэкранного просмотра -->
                <button
                    @click="openFullscreen"
                    class="absolute bottom-4 right-4 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110"
                >
                    <MagnifyingGlassPlusIcon class="w-5 h-5 text-gray-700" />
                </button>
            </div>
            
            <!-- Превью фотографий -->
            <div v-if="photos.length > 1" class="p-4 bg-gray-50">
                <div class="flex gap-2 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="(photo, index) in photos"
                        :key="`thumb-${index}`"
                        @click="currentIndex = index"
                        class="relative flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all"
                        :class="[
                            currentIndex === index 
                                ? 'border-indigo-500 shadow-lg scale-105' 
                                : 'border-transparent hover:border-gray-300'
                        ]"
                    >
                        <img 
                            :src="photo" 
                            :alt="`Фото ${index + 1}`"
                            class="w-full h-full object-cover"
                            @error="(e) => e.target.src = '/images/no-photo.jpg'"
                        >
                        <!-- Индикатор активного фото -->
                        <div 
                            v-if="currentIndex === index"
                            class="absolute inset-0 bg-indigo-500/20"
                        ></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassPlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    photos: {
        type: Array,
        default: () => []
    },
    masterName: {
        type: String,
        default: 'Мастер'
    },
    isPremium: {
        type: Boolean,
        default: false
    },
    isVerified: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['fullscreen-open'])

// Состояния
const currentIndex = ref(0)
const imageLoadError = ref(false)

// Вычисляемые свойства
const validPhotos = computed(() => {
    // Если нет фото, показываем заглушку
    if (!props.photos || props.photos.length === 0) {
        return ['/images/no-photo.jpg']
    }
    return props.photos
})

const currentPhoto = computed(() => {
    return validPhotos.value[currentIndex.value] || '/images/no-photo.jpg'
})

// Методы
const nextPhoto = () => {
    if (currentIndex.value < validPhotos.value.length - 1) {
        currentIndex.value++
    }
}

const previousPhoto = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}

const openFullscreen = () => {
    emit('fullscreen-open', {
        images: validPhotos.value,
        initialIndex: currentIndex.value
    })
}

const handleImageError = (event) => {
    event.target.src = '/images/no-photo.jpg'
}

// Клавиатурная навигация
const handleKeyboard = (event) => {
    if (event.key === 'ArrowLeft') {
        previousPhoto()
    } else if (event.key === 'ArrowRight') {
        nextPhoto()
    }
}

// Touch события для свайпов
let touchStartX = 0
let touchEndX = 0

const handleTouchStart = (e) => {
    touchStartX = e.touches[0].clientX
}

const handleTouchEnd = (e) => {
    touchEndX = e.changedTouches[0].clientX
    handleSwipe()
}

const handleSwipe = () => {
    const swipeThreshold = 50
    const diff = touchStartX - touchEndX

    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            nextPhoto()
        } else {
            previousPhoto()
        }
    }
}

// Сброс индекса при изменении фотографий
watch(() => props.photos, () => {
    currentIndex.value = 0
})
</script>

<style scoped>
/* Скрываем скроллбар */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Плавная анимация */
.aspect-w-16 {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
}

.aspect-w-16 > img {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}
</style>