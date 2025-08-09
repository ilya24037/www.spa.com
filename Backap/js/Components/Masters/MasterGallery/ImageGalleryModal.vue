<template>
    <!-- Модальное окно галереи -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
                <!-- Затемнённый фон -->
                <div 
                    class="absolute inset-0 bg-black/80 backdrop-blur-sm" 
                    @click="close"
                ></div>

                <!-- Контейнер галереи -->
                <div class="relative h-full flex items-center justify-center p-4">
                    <!-- Кнопка закрытия -->
                    <button
                        @click="close"
                        class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                        aria-label="Закрыть галерею"
                    >
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Основной контент -->
                    <div class="relative max-w-6xl w-full mx-auto flex gap-4">
                        <!-- Миниатюры (desktop) -->
                        <div class="hidden lg:flex flex-col gap-2 max-h-[600px] overflow-y-auto scrollbar-hide">
                            <button
                                v-for="(image, index) in images"
                                :key="index"
                                @click="currentIndex = index"
                                class="relative w-20 h-20 rounded-lg overflow-hidden transition-all"
                                :class="[
                                    currentIndex === index 
                                        ? 'ring-2 ring-purple-500 scale-105' 
                                        : 'opacity-70 hover:opacity-100'
                                ]"
                            >
                                <img 
                                    :src="image" 
                                    :alt="`Фото ${index + 1}`"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            </button>
                        </div>

                        <!-- Основное изображение -->
                        <div class="relative flex-1">
                            <!-- Изображение с поддержкой touch-жестов -->
                            <div 
                                class="relative bg-white rounded-lg overflow-hidden"
                                @touchstart="handleTouchStart"
                                @touchmove="handleTouchMove"
                                @touchend="handleTouchEnd"
                            >
                                <img 
                                    :src="images[currentIndex]" 
                                    :alt="`Фото ${currentIndex + 1}`"
                                    class="w-full h-auto max-h-[80vh] object-contain"
                                >

                                <!-- Счётчик изображений -->
                                <div class="absolute top-4 left-4 px-3 py-1 bg-black/50 text-white rounded-full text-sm">
                                    {{ currentIndex + 1 }} / {{ images.length }}
                                </div>
                            </div>

                            <!-- Навигация стрелками -->
                            <button
                                v-if="currentIndex > 0"
                                @click="previousImage"
                                class="absolute left-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                                aria-label="Предыдущее фото"
                            >
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <button
                                v-if="currentIndex < images.length - 1"
                                @click="nextImage"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                                aria-label="Следующее фото"
                            >
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>

                            <!-- Миниатюры (mobile) -->
                            <div class="lg:hidden flex gap-2 mt-4 overflow-x-auto scrollbar-hide justify-center">
                                <button
                                    v-for="(image, index) in images"
                                    :key="index"
                                    @click="currentIndex = index"
                                    class="relative w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 transition-all"
                                    :class="[
                                        currentIndex === index 
                                            ? 'ring-2 ring-purple-500 scale-105' 
                                            : 'opacity-70'
                                    ]"
                                >
                                    <img 
                                        :src="image" 
                                        :alt="`Фото ${index + 1}`"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Дополнительные действия -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        <button
                            @click="downloadImage"
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Скачать
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

// Пропсы
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    images: {
        type: Array,
        required: true,
        validator: (value) => value.length > 0
    },
    initialIndex: {
        type: Number,
        default: 0
    }
})

// Эмиты
const emit = defineEmits(['update:modelValue', 'close'])

// Состояние
const currentIndex = ref(props.initialIndex)
const touchStartX = ref(0)
const touchEndX = ref(0)

// Вычисляемые свойства
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// Методы навигации
const nextImage = () => {
    if (currentIndex.value < props.images.length - 1) {
        currentIndex.value++
    }
}

const previousImage = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}

const close = () => {
    emit('close')
    isOpen.value = false
}

// Скачивание изображения
const downloadImage = async () => {
    const imageUrl = props.images[currentIndex.value]
    try {
        const response = await fetch(imageUrl)
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `image-${currentIndex.value + 1}.jpg`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
    } catch (error) {
        console.error('Ошибка при скачивании изображения:', error)
    }
}

// Touch события для свайпов на мобильных
const handleTouchStart = (e) => {
    touchStartX.value = e.touches[0].clientX
}

const handleTouchMove = (e) => {
    touchEndX.value = e.touches[0].clientX
}

const handleTouchEnd = () => {
    const swipeThreshold = 50
    const diff = touchStartX.value - touchEndX.value

    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            nextImage()
        } else {
            previousImage()
        }
    }
}

// Обработка клавиатуры
const handleKeydown = (e) => {
    if (!isOpen.value) return
    
    switch(e.key) {
        case 'ArrowLeft':
            previousImage()
            break
        case 'ArrowRight':
            nextImage()
            break
        case 'Escape':
            close()
            break
    }
}

// Блокировка скролла body при открытой галерее
watch(isOpen, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
})
</script>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>