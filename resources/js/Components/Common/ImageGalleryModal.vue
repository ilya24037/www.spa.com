<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
                <!-- Overlay -->
                <div 
                    class="absolute inset-0 bg-black/90 backdrop-blur-sm"
                    @click="close"
                ></div>

                <!-- Контент галереи -->
                <div class="relative h-full flex flex-col">
                    <!-- Шапка с кнопками -->
                    <div class="absolute top-0 left-0 right-0 z-20 p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-white">
                                {{ currentIndex + 1 }} / {{ images.length }}
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button
                                    @click="downloadImage"
                                    class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                                    title="Скачать"
                                >
                                    <ArrowDownTrayIcon class="w-5 h-5" />
                                </button>
                                <button
                                    @click="close"
                                    class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Основной контейнер изображения -->
                    <div class="flex-1 flex items-center justify-center p-4">
                        <div class="relative max-w-full max-h-full">
                            <!-- Изображение -->
                            <TransitionGroup
                                enter-active-class="duration-300 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="duration-200 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <img
                                    v-for="(image, index) in images"
                                    v-show="index === currentIndex"
                                    :key="image"
                                    :src="image"
                                    :alt="`Изображение ${index + 1}`"
                                    class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl"
                                    @touchstart="handleTouchStart"
                                    @touchmove="handleTouchMove"
                                    @touchend="handleTouchEnd"
                                >
                            </TransitionGroup>

                            <!-- Кнопки навигации -->
                            <button
                                v-if="currentIndex > 0"
                                @click="previousImage"
                                class="absolute left-4 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-all hover:scale-110"
                            >
                                <ChevronLeftIcon class="w-6 h-6" />
                            </button>
                            
                            <button
                                v-if="currentIndex < images.length - 1"
                                @click="nextImage"
                                class="absolute right-4 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-all hover:scale-110"
                            >
                                <ChevronRightIcon class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <!-- Превью изображений -->
                    <div v-if="images.length > 1" class="absolute bottom-0 left-0 right-0 p-4">
                        <div class="flex justify-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
                            <button
                                v-for="(image, index) in images"
                                :key="`thumb-${index}`"
                                @click="currentIndex = index"
                                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all"
                                :class="[
                                    currentIndex === index 
                                        ? 'border-white shadow-lg' 
                                        : 'border-transparent opacity-60 hover:opacity-100'
                                ]"
                            >
                                <img 
                                    :src="image" 
                                    :alt="`Превью ${index + 1}`"
                                    class="w-full h-full object-cover"
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { XMarkIcon, ChevronLeftIcon, ChevronRightIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    images: {
        type: Array,
        required: true
    },
    initialIndex: {
        type: Number,
        default: 0
    },
    modelValue: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:modelValue', 'close'])

// Состояния
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