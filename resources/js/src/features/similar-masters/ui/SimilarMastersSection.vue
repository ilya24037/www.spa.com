<!-- Similar Masters Section with TypeScript -->
<template>
  <div v-if="masters.length > 0" class="similar-masters-section">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Похожие мастера</h2>
      <p class="text-gray-600 mt-1">Мастера с похожими услугами в вашем районе</p>
    </div>
    
    <!-- Слайдер для десктопа -->
    <div class="relative hidden lg:block">
      <!-- Контейнер слайдера -->
      <div class="overflow-hidden">
        <div 
          class="flex transition-transform duration-300 ease-out"
          :style="{ transform: `translateX(-${currentSlide * slideWidth}px)` }"
        >
          <div 
            v-for="master in masters"
            :key="master.id"
            class="flex-shrink-0 px-3"
            :style="{ width: `${slideWidth}px` }"
          >
            <MasterCard 
              :master="master"
              @click="goToMaster(master)"
            />
          </div>
        </div>
      </div>
      
      <!-- Кнопки навигации -->
      <button
        v-if="currentSlide > 0"
        @click="previousSlide"
        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 bg-white shadow-lg rounded-full p-3 hover:shadow-xl transition-shadow z-10"
        aria-label="Предыдущий"
      >
        <svg class="w-6 h-6"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      
      <button
        v-if="currentSlide < maxSlide"
        @click="nextSlide"
        class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 bg-white shadow-lg rounded-full p-3 hover:shadow-xl transition-shadow z-10"
        aria-label="Следующий"
      >
        <svg class="w-6 h-6"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7" />
        </svg>
      </button>
      
      <!-- Индикаторы -->
      <div class="flex justify-center gap-2 mt-6">
        <button
          v-for="i in totalSlides"
          :key="i"
          @click="currentSlide = i - 1"
          class="w-2 h-2 rounded-full transition-all"
          :class="currentSlide === i - 1 ? 'bg-indigo-600 w-8' : 'bg-gray-300'"
          :aria-label="`Слайд ${i}`"
        />
      </div>
    </div>
    
    <!-- Сетка для мобильных -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:hidden">
      <MasterCard 
        v-for="master in masters.slice(0, 4)"
        :key="master.id"
        :master="master"
        @click="goToMaster(master)"
      />
    </div>
    
    <!-- Кнопка "Показать всех" для мобильных -->
    <div class="text-center mt-6 lg:hidden">
      <Link 
        :href="searchUrl"
        class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
      >
        Показать всех похожих мастеров
        <svg class="w-5 h-5 ml-2"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </Link>
    </div>
    
    <!-- CTA блок -->
    <div class="mt-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white text-center">
      <h3 class="text-2xl font-bold mb-2">Не нашли подходящего мастера?</h3>
      <p class="mb-6 opacity-90">Посмотрите всех мастеров в вашем городе</p>
      <Link 
        href="/search"
        class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-lg font-medium hover:bg-gray-100 transition-colors"
      >
        Смотреть всех мастеров
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import type { Master } from '@/src/entities/master/model/types'

interface Props {
  masters: Master[]
  currentMaster: Master
}

const props = withDefaults(defineProps<Props>(), {
  masters: () => []
})

// Состояние слайдера
const currentSlide = ref(0)
const slideWidth = ref(320) // ширина одной карточки
const slidesPerView = ref(4)

// Вычисляемые свойства
const totalSlides = computed(() => Math.ceil(props.masters.length / slidesPerView.value))
const maxSlide = computed(() => totalSlides.value - 1)

const searchUrl = computed(() => {
  const params = new URLSearchParams({
    category: String(props.currentMaster.primary_category_id || ''),
    district: props.currentMaster.district || '',
    exclude: String(props.currentMaster.id)
  })
  return `/search?${params.toString()}`
})

// Методы навигации
const nextSlide = () => {
  if (currentSlide.value < maxSlide.value) {
    currentSlide.value++
  }
}

const previousSlide = () => {
  if (currentSlide.value > 0) {
    currentSlide.value--
  }
}

const goToMaster = (master: Master) => {
  const slug = master.slug || generateSlug(master.name)
  router.visit(`/masters/${slug}-${master.id}`)
}

const generateSlug = (text: string | null | undefined): string => {
  if (!text) return 'master'
  return text
    .toLowerCase()
    .replace(/[^a-z0-9а-яё\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim()
    || 'master'
}

// Обработка изменения размера окна
const handleResize = () => {
  const width = window.innerWidth
  if (width >= 1536) {
    slidesPerView.value = 4
    slideWidth.value = 320
  } else if (width >= 1280) {
    slidesPerView.value = 3
    slideWidth.value = 360
  } else if (width >= 1024) {
    slidesPerView.value = 3
    slideWidth.value = 320
  }
}

// Автопрокрутка (опционально)
let autoplayInterval: ReturnType<typeof setInterval> | null = null

const startAutoplay = () => {
  if (props.masters.length > slidesPerView.value) {
    autoplayInterval = setInterval(() => {
      if (currentSlide.value >= maxSlide.value) {
        currentSlide.value = 0
      } else {
        nextSlide()
      }
    }, 5000) // каждые 5 секунд
  }
}

const stopAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval)
    autoplayInterval = null
  }
}

// Жизненный цикл
onMounted(() => {
  handleResize()
  window.addEventListener('resize', handleResize)
  
  // Включаем автопрокрутку (раскомментировать при необходимости)
  // startAutoplay()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  stopAutoplay()
})
</script>

<style scoped>
/* Плавные переходы для слайдера */
.similar-masters-section {
  @apply relative;
}

/* Отключаем выделение текста на кнопках */
button {
  @apply select-none;
}

/* Анимация для индикаторов */
button[class*="bg-indigo-600"] {
  transition: width 0.3s ease;
}
</style>