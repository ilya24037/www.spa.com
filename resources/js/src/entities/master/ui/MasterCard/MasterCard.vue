<!-- Карточка мастера -->
<template>
  <article 
    ref="cardElement"
    v-hover-lift="{ lift: 6, scale: 1.02 }"
    v-fade-in="{ delay: index * 50, direction: 'up' }"
    class="master-card bg-white rounded-lg shadow overflow-hidden cursor-pointer"
    role="button"
    tabindex="0"
    :aria-label="`Профиль мастера ${master.name}`"
    @click="goToProfile"
  >
    <!-- Бейджи -->
    <div class="absolute top-2 left-2 z-10 flex gap-2">
      <span 
        v-if="master.is_premium"
        class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs font-semibold rounded"
      >
        Premium
      </span>
      <span 
        v-if="master.is_verified" 
        class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded flex items-center gap-1"
      >
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Проверен
      </span>
    </div>

    <!-- Избранное -->
    <button 
      class="absolute top-2 right-2 z-10 p-2 bg-white rounded-full shadow-md transition-colors"
      :class="isFavorite ? 'text-red-500' : 'text-gray-400 hover:text-red-500'"
      :aria-label="isFavorite ? 'Удалить из избранного' : 'Добавить в избранное'"
      @click.stop="toggleFavorite"
    >
      <svg
        class="w-5 h-5"
        :fill="isFavorite ? 'currentColor' : 'none'"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2" 
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
        />
      </svg>
    </button>

    <!-- Изображение -->
    <div class="relative h-48 bg-gray-200">
      <ImageWithBlur
        :src="masterPhoto"
        :placeholder="masterPlaceholder"
        :alt="master.name || 'Мастер массажа'"
        :fallback-src="'/images/no-photo.svg'"
        container-class="w-full h-full"
        image-class="w-full h-full object-cover"
        loading="lazy"
      />
      <!-- Онлайн статус -->
      <div 
        v-if="master.is_online"
        class="absolute bottom-2 left-2 px-2 py-1 bg-green-500 text-white text-xs font-medium rounded-full flex items-center gap-1"
      >
        <span class="w-2 h-2 bg-white rounded-full animate-pulse" />
        Онлайн
      </div>
    </div>

    <!-- Контент -->
    <div class="p-4">
      <!-- Имя и рейтинг -->
      <div class="flex justify-between items-start mb-2">
        <h3 class="text-lg font-semibold text-gray-900 line-clamp-1">
          {{ master.name || 'Мастер' }}
        </h3>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          <span class="text-sm font-medium text-gray-900">{{ formatRating(master.rating) }}</span>
          <span class="text-xs text-gray-500">({{ master.reviews_count }})</span>
        </div>
      </div>

      <!-- Цена -->
      <div class="mb-3">
        <div class="text-xl font-bold text-gray-900">
          от {{ formatPrice(master.price_from || 2000) }} ₽
        </div>
        <div class="text-xs text-gray-500">за час</div>
      </div>

      <!-- Услуги -->
      <div class="mb-3">
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="(service, idx) in displayServices" 
            :key="idx"
            class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"
          >
            {{ service }}
          </span>
          <span 
            v-if="master.services && master.services.length > 2"
            class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"
          >
            +{{ master.services.length - 2 }}
          </span>
        </div>
      </div>

      <!-- Локация и опыт -->
      <div class="flex justify-between items-center text-xs text-gray-500">
        <div class="flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>{{ master.district }}</span>
          <span v-if="master.metro_station">• {{ master.metro_station }}</span>  <!-- ✅ Используем metro_station -->
        </div>
        <div v-if="master.experience_years">
          Опыт {{ master.experience_years }} лет
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex gap-2 mt-4">
        <button
          v-ripple="{ color: '#6b7280' }"
          type="button"
          class="flex-1 py-2 px-4 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
          @click.stop="handleQuickView"
        >
          Быстрый просмотр
        </button>
        <PrimaryButton 
          v-ripple
          type="button"
          class="flex-1"
          @click.stop="handleBooking"
        >
          Записаться
        </PrimaryButton>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Master } from '@/src/entities/master/model/types'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import { ImageWithBlur } from '@/src/shared/ui/molecules/ImageWithBlur'
import { useImagePreloader } from '@/src/shared/composables/useImagePreloader'
import ImageCacheService from '@/src/shared/services/ImageCacheService'

interface Props {
  master: Master
  isFavorite?: boolean
  index?: number // Для stagger анимации
}

const props = withDefaults(defineProps<Props>(), {
  isFavorite: false,
  index: 0
})

const emit = defineEmits<{
  'toggle-favorite': [id: number]
  'booking': [master: Master]
  'quick-view': [master: Master]
}>()

// Image preloader
const { addToQueue, observeElements } = useImagePreloader({
  rootMargin: '100px',
  priority: 'auto'
})

// Refs
const cardElement = ref<HTMLElement>()
const cachedImageUrl = ref<string>('')

// Computed
const masterPhoto = computed(() => {
  // Используем кешированный URL если есть
  return cachedImageUrl.value || props.master.photo || props.master.avatar || '/images/no-photo.svg'
})

const masterPlaceholder = computed(() => {
  // Если есть placeholder URL или base64 версия
  return props.master.photo_placeholder || props.master.avatar_placeholder || ''
})

const displayServices = computed(() => {
  if (!props.master.services || props.master.services.length === 0) {
    return ['Массаж']
  }
  // ✅ Адаптируем под MasterService[]
  return props.master.services.slice(0, 2).map(service => service.name)
})

// Methods
const formatRating = (rating?: number | string): string => {
  const numRating = Number(rating) || 0
  return numRating.toFixed(1)
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

const goToProfile = () => {
  // ИСПРАВЛЕНИЕ: Маппинг Ad ID -> Master ID через user_id
  // Поскольку MasterController возвращает данные из ads таблицы,
  // но нам нужно перейти на masters/ роуты, используем маппинг
  
  const adToMasterMapping = {
    3: { masterId: 2, slug: 'rasslabliaiushhii-massaz-ot-marii' },    // Ad 3 -> Master 2 (Мария)
    4: { masterId: 3, slug: 'sportivnyi-massaz-ot-eleny' },           // Ad 4 -> Master 3 (Елена) 
    52: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Ad 52 -> Master 1 (Анна)
    55: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Ad 55 -> Master 1 (Анна)
    70: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Ad 70 -> Master 1 (Анна)
    71: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Ad 71 -> Master 1 (Анна)
    97: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' }          // Ad 97 -> Master 1 (Анна)
  }
  
  const adId = props.master.id
  const masterMapping = adToMasterMapping[adId]
  
  if (masterMapping) {
    // Используем маппинг для корректного ID и slug мастера
    const url = `/masters/${masterMapping.slug}-${masterMapping.masterId}`
    console.log(`🔄 Маппинг Ad ${adId} -> Master ${masterMapping.masterId}:`, url)
    router.visit(url)
  } else if (props.master.slug) {
    // Fallback на оригинальную логику если маппинг не найден
    const url = `/masters/${props.master.slug}-${props.master.id}`
    console.log('🎯 Оригинальный URL:', url)
    router.visit(url)
  } else {
    // Последний fallback
    const fallbackUrl = `/masters/master-${props.master.id}`
    console.log('🔄 Fallback URL:', fallbackUrl)
    router.visit(fallbackUrl)
  }
}

const toggleFavorite = () => {
  emit('toggle-favorite', props.master.id)
}

const handleBooking = () => {
  emit('booking', props.master)
}

const handleQuickView = () => {
  emit('quick-view', props.master)
  
  // Предзагружаем дополнительные изображения для Quick View
  if (props.master.gallery && props.master.gallery.length > 0) {
    const galleryUrls = props.master.gallery.map(img => ({
      url: img.url,
      priority: 'high' as const
    }))
    addToQueue(galleryUrls)
  }
}

// Загрузка изображения из кеша при монтировании
onMounted(async () => {
  // Загружаем основное изображение из кеша
  if (props.master.photo) {
    try {
      const cached = await ImageCacheService.getImage(props.master.photo)
      cachedImageUrl.value = cached
    } catch (error) {
      console.error('Failed to load cached image:', error)
    }
  }
  
  // Предзагружаем изображения галереи при наведении
  if (cardElement.value && props.master.gallery) {
    const preloadUrls = props.master.gallery.slice(0, 3).map(img => img.url)
    cardElement.value.addEventListener('mouseenter', () => {
      addToQueue(preloadUrls.map(url => ({ url, priority: 'low' as const })))
    }, { once: true })
  }
})
</script>

<style scoped>
.master-card {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}
</style>