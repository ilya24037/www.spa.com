<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Основное изображение -->
        <div class="relative bg-gray-100">
            <!-- Соотношение сторон 4:3 для единообразия -->
            <div class="relative aspect-[4/3]">
                <!-- Индикаторы слайдов для мобильной версии -->
                <div v-if="validPhotos.length > 1" class="absolute top-4 left-1/2 -translate-x-1/2 z-20 lg:hidden">
                    <div class="flex gap-1">
                        <span
                            v-for="(_, index) in validPhotos"
                            :key="`indicator-${index}`"
                            class="block w-6 h-1 rounded-full transition-all"
                            :class="[
                                currentIndex === index 
                                    ? 'bg-white' 
                                    : 'bg-white/50'
                            ]"
                        ></span>
                    </div>
                </div>

                <!-- Слайдер изображений -->
                <div class="relative h-full w-full overflow-hidden">
                    <transition-group
                        name="slide"
                        tag="div"
                        class="relative h-full w-full"
                    >
                        <div
                            v-for="(photo, index) in validPhotos"
                            v-show="index === currentIndex"
                            :key="`photo-${index}`"
                            class="absolute inset-0"
                        >
                            <img 
                                :src="photo.url || photo" 
                                :alt="`${masterName} - фото ${index + 1}`"
                                class="w-full h-full object-cover"
                                loading="lazy"
                                @error="handleImageError(index)"
                            >
                        </div>
                    </transition-group>

                    <!-- Заглушка если нет фото -->
                    <div v-if="!hasValidPhotos" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Нет фото</p>
                        </div>
                    </div>
                </div>

                <!-- Кнопки навигации (только десктоп) -->
                <template v-if="validPhotos.length > 1">
                    <button
                        @click="prevPhoto"
                        class="hidden lg:flex absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full items-center justify-center shadow-lg transition-all hover:scale-110 z-10"
                    >
                        <ChevronLeftIcon class="w-5 h-5 text-gray-700" />
                    </button>
                    
                    <button
                        @click="nextPhoto"
                        class="hidden lg:flex absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full items-center justify-center shadow-lg transition-all hover:scale-110 z-10"
                    >
                        <ChevronRightIcon class="w-5 h-5 text-gray-700" />
                    </button>
                </template>

                <!-- Счетчик фото (десктоп) -->
                <div v-if="validPhotos.length > 1" class="hidden lg:block absolute top-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
                    {{ currentIndex + 1 }} / {{ validPhotos.length }}
                </div>
                
                <!-- Кнопка полноэкранного просмотра -->
                <button
                    v-if="hasValidPhotos"
                    @click="openFullscreen"
                    class="absolute bottom-4 right-4 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110 z-10"
                >
                    <MagnifyingGlassPlusIcon class="w-5 h-5 text-gray-700" />
                </button>

                <!-- Бейджи -->
                <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                    <span v-if="isPremium" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg">
                        <StarIcon class="w-3 h-3 mr-1" />
                        Premium
                    </span>
                    <span v-if="isVerified" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg">
                        <CheckBadgeIcon class="w-3 h-3 mr-1" />
                        Проверен
                    </span>
                </div>
            </div>
            
            <!-- Превью фотографий (десктоп) -->
            <div v-if="validPhotos.length > 1" class="hidden lg:block p-4 bg-gray-50">
                <div class="flex gap-2 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="(photo, index) in validPhotos"
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
                            :src="photo.thumbnail || photo.url || photo" 
                            :alt="`Превью ${index + 1}`"
                            class="w-full h-full object-cover"
                            loading="lazy"
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassPlusIcon, StarIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline'

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

// Состояние
const currentIndex = ref(0)
const failedImages = ref(new Set())
const touchStartX = ref(0)
const touchEndX = ref(0)

// Вычисляемые свойства
const validPhotos = computed(() => {
    if (!props.photos || props.photos.length === 0) {
        return []
    }
    
    // Фильтруем уникальные и валидные фото
    const uniquePhotos = []
    const seenUrls = new Set()
    
    for (const photo of props.photos) {
        const url = typeof photo === 'string' ? photo : (photo.url || photo.src)
        
        // Пропускаем пустые, дубликаты и заглушки
        if (!url || seenUrls.has(url) || url.includes('no-photo') || failedImages.value.has(url)) {
            continue
        }
        
        seenUrls.add(url)
        uniquePhotos.push({
            url,
            thumbnail: typeof photo === 'object' ? photo.thumbnail : url
        })
    }
    
    return uniquePhotos
})

const hasValidPhotos = computed(() => validPhotos.value.length > 0)

// Методы
const nextPhoto = () => {
    if (validPhotos.value.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % validPhotos.value.length
    }
}

const prevPhoto = () => {
    if (validPhotos.value.length > 1) {
        currentIndex.value = currentIndex.value === 0 
            ? validPhotos.value.length - 1 
            : currentIndex.value - 1
    }
}

const handleImageError = (index) => {
    const photo = validPhotos.value[index]
    if (photo) {
        failedImages.value.add(photo.url)
        // Если текущее фото не загрузилось, переключаемся на следующее
        if (currentIndex.value === index && validPhotos.value.length > 1) {
            nextPhoto()
        }
    }
}

const openFullscreen = () => {
    emit('fullscreen-open', {
        photos: validPhotos.value.map(p => p.url),
        initialIndex: currentIndex.value
    })
}

// Touch события для свайпа на мобильных
const handleTouchStart = (e) => {
    touchStartX.value = e.changedTouches[0].screenX
}

const handleTouchEnd = (e) => {
    touchEndX.value = e.changedTouches[0].screenX
    handleSwipe()
}

const handleSwipe = () => {
    if (!touchStartX.value || !touchEndX.value) return
    
    const swipeThreshold = 50
    const diff = touchStartX.value - touchEndX.value
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            nextPhoto()
        } else {
            prevPhoto()
        }
    }
    
    touchStartX.value = 0
    touchEndX.value = 0
}

// Keyboard navigation
const handleKeydown = (e) => {
    if (e.key === 'ArrowLeft') prevPhoto()
    if (e.key === 'ArrowRight') nextPhoto()
}

// Сброс индекса при изменении фото
watch(() => props.photos, () => {
    currentIndex.value = 0
    failedImages.value.clear()
})

// Lifecycle
onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
    // Добавляем touch события для мобильных
    const gallery = document.querySelector('.gallery-container')
    if (gallery) {
        gallery.addEventListener('touchstart', handleTouchStart, { passive: true })
        gallery.addEventListener('touchend', handleTouchEnd, { passive: true })
    }
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Анимация переключения слайдов */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.slide-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

/* Скрываем скроллбар для превью */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>