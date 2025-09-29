<!-- 
  RecommendedSection - Секция персонализированных рекомендаций
  Отображает рекомендованных мастеров на основе поведения пользователя
-->
<template>
  <section 
    v-if="recommendations.length > 0"
    class="recommended-section"
  >
    <!-- Заголовок с навигацией -->
    <div class="recommended-section__header">
      <div class="recommended-section__title-group">
        <h2 class="recommended-section__title">
          {{ title }}
        </h2>
        <p v-if="subtitle" class="recommended-section__subtitle">
          {{ subtitle }}
        </p>
      </div>
      
      <!-- Навигация для карусели -->
      <div v-if="showNavigation" class="recommended-section__nav">
        <button
          :disabled="!canScrollLeft"
          class="recommended-section__nav-btn"
          @click="scrollLeft"
        >
          <svg class="w-5 h-5"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          :disabled="!canScrollRight"
          class="recommended-section__nav-btn"
          @click="scrollRight"
        >
          <svg class="w-5 h-5"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Карусель рекомендаций -->
    <div 
      ref="scrollContainer"
      class="recommended-section__carousel"
      @scroll="handleScroll"
    >
      <div class="recommended-section__track">
        <div
          v-for="(master, index) in recommendations"
          :key="`${sectionId}-${master.id}`"
          class="recommended-section__item"
          :class="{ 'recommended-section__item--premium': master.is_premium }"
        >
          <!-- Бейдж с причиной рекомендации -->
          <div 
            v-if="master._recommendationReasons && master._recommendationReasons[0]"
            class="recommended-section__badge"
          >
            {{ master._recommendationReasons[0] }}
          </div>
          
          <!-- Карточка мастера -->
          <MasterCard
            :master="master"
            :index="index"
            :is-favorite="isFavorite(master.id)"
            @toggle-favorite="handleToggleFavorite"
            @booking="handleBooking"
            @quick-view="handleQuickView"
            @click="trackInteraction(master, 'click')"
            @mouseenter="trackInteraction(master, 'hover')"
          />
        </div>
      </div>
    </div>

    <!-- Индикаторы страниц -->
    <div v-if="showIndicators" class="recommended-section__indicators">
      <button
        v-for="page in totalPages"
        :key="page"
        class="recommended-section__indicator"
        :class="{ 'recommended-section__indicator--active': currentPage === page }"
        @click="scrollToPage(page)"
      />
    </div>

    <!-- Кнопка "Показать все" -->
    <div v-if="showViewAll" class="recommended-section__footer">
      <SecondaryButton @click="handleViewAll">
        Показать все рекомендации
        <svg class="w-4 h-4 ml-2"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7" />
        </svg>
      </SecondaryButton>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Master } from '@/src/entities/master/model/types'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'
import RecommendationService from '@/src/shared/services/RecommendationService'
import { useIntersectionAnimation } from '@/src/shared/composables/useIntersectionAnimation'

// Расширенный тип для мастера с рекомендациями
interface MasterWithRecommendation extends Master {
  _recommendationReasons?: string[]
}

interface Props {
  // Данные
  masters: MasterWithRecommendation[]
  recommendations?: MasterWithRecommendation[]
  
  // Настройки отображения
  title?: string
  subtitle?: string
  sectionId?: string
  itemsPerPage?: number
  showNavigation?: boolean
  showIndicators?: boolean
  showViewAll?: boolean
  
  // Тип рекомендаций
  type?: 'personalized' | 'similar' | 'popular' | 'new'
  currentMaster?: MasterWithRecommendation // Для похожих рекомендаций
  
  // Функции
  isFavorite?: (id: number) => boolean
}

const props = withDefaults(defineProps<Props>(), {
  recommendations: () => [],
  title: 'Рекомендуем для вас',
  subtitle: '',
  sectionId: 'recommendations',
  itemsPerPage: 4,
  showNavigation: true,
  showIndicators: false,
  showViewAll: false,
  type: 'personalized',
  isFavorite: () => false
})

const emit = defineEmits<{
  'toggle-favorite': [id: number]
  'booking': [master: Master]
  'quick-view': [master: Master]
  'view-all': []
  'interaction': [data: { master: Master; action: string }]
}>()

// Refs
const scrollContainer = ref<HTMLElement>()
const currentPage = ref(1)
const scrollPosition = ref(0)

// Computed
const recommendations = computed<MasterWithRecommendation[]>(() => {
  if (props.recommendations.length > 0) {
    return props.recommendations
  }
  
  // Генерируем рекомендации на основе типа
  switch (props.type) {
    case 'personalized':
      return RecommendationService.getRecommendations(props.masters, {
        maxRecommendations: 12,
        includeReasons: true
      })
    
    case 'similar':
      if (props.currentMaster) {
        return RecommendationService.getSimilarMasters(
          props.currentMaster,
          props.masters,
          8
        )
      }
      return []
    
    case 'popular':
      return props.masters
        .filter(m => (m.rating ?? 0) >= 4.5 && (m.reviews_count ?? 0) > 20)
        .sort((a, b) => (b.reviews_count ?? 0) - (a.reviews_count ?? 0))
        .slice(0, 12)
    
    case 'new':
      return props.masters
        .filter(m => m.is_verified)
        .slice(0, 12)
    
    default:
      return []
  }
})

const totalPages = computed(() => {
  return Math.ceil(recommendations.value.length / props.itemsPerPage)
})

const canScrollLeft = computed(() => {
  return scrollPosition.value > 0
})

const canScrollRight = computed(() => {
  if (!scrollContainer.value) return false
  const maxScroll = scrollContainer.value.scrollWidth - scrollContainer.value.clientWidth
  return scrollPosition.value < maxScroll - 10
})

// Methods
const handleScroll = () => {
  if (scrollContainer.value) {
    scrollPosition.value = scrollContainer.value.scrollLeft
    
    // Определяем текущую страницу
    const pageWidth = scrollContainer.value.clientWidth
    currentPage.value = Math.round(scrollPosition.value / pageWidth) + 1
  }
}

const scrollLeft = () => {
  if (scrollContainer.value) {
    const pageWidth = scrollContainer.value.clientWidth
    scrollContainer.value.scrollTo({
      left: Math.max(0, scrollPosition.value - pageWidth),
      behavior: 'smooth'
    })
  }
}

const scrollRight = () => {
  if (scrollContainer.value) {
    const pageWidth = scrollContainer.value.clientWidth
    const maxScroll = scrollContainer.value.scrollWidth - scrollContainer.value.clientWidth
    scrollContainer.value.scrollTo({
      left: Math.min(maxScroll, scrollPosition.value + pageWidth),
      behavior: 'smooth'
    })
  }
}

const scrollToPage = (page: number) => {
  if (scrollContainer.value) {
    const pageWidth = scrollContainer.value.clientWidth
    scrollContainer.value.scrollTo({
      left: (page - 1) * pageWidth,
      behavior: 'smooth'
    })
  }
}

const handleToggleFavorite = (masterId: number) => {
  emit('toggle-favorite', masterId)
  
  // Отслеживаем для рекомендаций
  const isFav = props.isFavorite(masterId)
  RecommendationService.trackFavorite(masterId, !isFav)
}

const handleBooking = (master: Master) => {
  emit('booking', master)
  RecommendationService.trackBooking(master.id)
}

const handleQuickView = (master: Master) => {
  emit('quick-view', master)
  trackInteraction(master, 'quick-view')
}

const handleViewAll = () => {
  emit('view-all')
  
  // Переход на страницу с фильтром по рекомендациям
  router.visit('/masters?recommended=true')
}

const trackInteraction = (master: MasterWithRecommendation, action: string) => {
  // Отслеживаем взаимодействие
  if (action === 'click' || action === 'quick-view') {
    RecommendationService.trackMasterView(master)
  }
  
  emit('interaction', { master, action })
}

// Анимация появления
useIntersectionAnimation(scrollContainer, {
  threshold: 0.1,
  stagger: 50
})

// Автопрокрутка (опционально)
let autoScrollInterval: number | null = null

const startAutoScroll = () => {
  if (totalPages.value <= 1) return
  
  autoScrollInterval = window.setInterval(() => {
    if (currentPage.value >= totalPages.value) {
      scrollToPage(1)
    } else {
      scrollRight()
    }
  }, 5000) // Каждые 5 секунд
}

const stopAutoScroll = () => {
  if (autoScrollInterval) {
    clearInterval(autoScrollInterval)
    autoScrollInterval = null
  }
}

// Lifecycle
onMounted(() => {
  // Можно включить автопрокрутку
  // startAutoScroll()
  
  // Останавливаем при взаимодействии
  scrollContainer.value?.addEventListener('mouseenter', stopAutoScroll)
  scrollContainer.value?.addEventListener('mouseleave', startAutoScroll)
})

onUnmounted(() => {
  stopAutoScroll()
})

// Обновление рекомендаций при изменении данных
watch(() => props.masters, () => {
  // Рекомендации автоматически пересчитаются через computed
})
</script>

<style scoped>
.recommended-section {
  margin: 2rem 0;
  padding: 1.5rem 0;
  background: linear-gradient(to bottom, #f9fafb, transparent);
  border-radius: 1rem;
}

.recommended-section__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding: 0 1rem;
}

.recommended-section__title-group {
  flex: 1;
}

.recommended-section__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.recommended-section__subtitle {
  margin-top: 0.25rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.recommended-section__nav {
  display: flex;
  gap: 0.5rem;
}

.recommended-section__nav-btn {
  padding: 0.5rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  color: #6b7280;
  transition: all 0.2s;
  cursor: pointer;
}

.recommended-section__nav-btn:hover:not(:disabled) {
  background: #f3f4f6;
  color: #111827;
}

.recommended-section__nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.recommended-section__carousel {
  overflow-x: auto;
  scroll-behavior: smooth;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  
  /* Скрываем скроллбар */
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.recommended-section__carousel::-webkit-scrollbar {
  display: none;
}

.recommended-section__track {
  display: flex;
  gap: 1rem;
  padding: 0 1rem;
}

.recommended-section__item {
  flex: 0 0 calc(25% - 0.75rem);
  min-width: 280px;
  max-width: 320px;
  scroll-snap-align: start;
  position: relative;
}

@media (max-width: 1280px) {
  .recommended-section__item {
    flex: 0 0 calc(33.333% - 0.75rem);
  }
}

@media (max-width: 1024px) {
  .recommended-section__item {
    flex: 0 0 calc(50% - 0.75rem);
  }
}

@media (max-width: 640px) {
  .recommended-section__item {
    flex: 0 0 calc(100% - 0.75rem);
  }
}

.recommended-section__item--premium {
  position: relative;
}

.recommended-section__item--premium::before {
  content: '';
  position: absolute;
  inset: -2px;
  background: linear-gradient(45deg, #fbbf24, #f59e0b);
  border-radius: 0.5rem;
  opacity: 0.1;
  z-index: -1;
}

.recommended-section__badge {
  position: absolute;
  top: -0.5rem;
  left: 0.5rem;
  z-index: 10;
  padding: 0.25rem 0.75rem;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.recommended-section__indicators {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.recommended-section__indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: none;
  background: #d1d5db;
  transition: all 0.3s;
  cursor: pointer;
}

.recommended-section__indicator--active {
  width: 24px;
  border-radius: 4px;
  background: #3b82f6;
}

.recommended-section__footer {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
  padding: 0 1rem;
}

/* Анимация появления */
@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.recommended-section__item {
  animation: slide-up 0.6s ease-out backwards;
}

.recommended-section__item:nth-child(1) { animation-delay: 0ms; }
.recommended-section__item:nth-child(2) { animation-delay: 50ms; }
.recommended-section__item:nth-child(3) { animation-delay: 100ms; }
.recommended-section__item:nth-child(4) { animation-delay: 150ms; }
</style>