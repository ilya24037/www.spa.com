<template>
  <article 
    class="product-card"
    :class="{ 
      'product-card--adult': product.isAdult,
      'product-card--blur': product.shouldBlur 
    }"
    @click="handleClick"
    @contextmenu="handleRightClick"
    @auxclick="handleAuxClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Изображение товара -->
    <div class="product-card__image-wrapper">
      <img
        v-if="mainImage"
        :src="imageUrl"
        :alt="productName"
        :loading="lazy ? 'lazy' : 'eager'"
        class="product-card__image"
        :class="`product-card__image--${mainImage.contentMode.toLowerCase()}`"
        @error="handleImageError"
      />
      
      <!-- Бейдж -->
      <div 
        v-if="product.leftBottomBadge"
        class="product-card__badge"
        :style="badgeStyles"
      >
        <span v-if="product.leftBottomBadge.image" class="badge-icon">
          {{ getBadgeIcon(product.leftBottomBadge.image) }}
        </span>
        <span class="badge-text">{{ product.leftBottomBadge.text }}</span>
      </div>
      
      <!-- Кнопка избранного -->
      <button
        class="product-card__favorite"
        :class="{ 'is-favorite': isFavorite }"
        @click.stop="toggleFavorite"
        :aria-label="isFavorite ? 'Удалить из избранного' : 'Добавить в избранное'"
      >
        <svg width="20" height="20" viewBox="0 0 24 24">
          <path 
            :fill="isFavorite ? '#f91155' : 'none'"
            :stroke="isFavorite ? '#f91155' : '#70757a'"
            stroke-width="2"
            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
          />
        </svg>
      </button>
    </div>

    <!-- Контент карточки -->
    <div class="product-card__content">
      <!-- Обработка состояний из Ozon -->
      <div 
        v-for="(state, index) in product.state" 
        :key="index"
        class="product-card__state"
      >
        <!-- Блок цены -->
        <div v-if="state.type === 'priceV2'" class="price-block">
          <div class="price-wrapper">
            <span 
              v-for="(price, idx) in state.priceV2.price" 
              :key="idx"
              :class="`price price--${price.textStyle.toLowerCase()}`"
            >
              {{ price.text }}
            </span>
          </div>
          <span v-if="state.priceV2.discount" class="price-discount">
            {{ state.priceV2.discount }}
          </span>
        </div>

        <!-- Текстовый блок (название) -->
        <div v-else-if="state.type === 'textAtom'" class="text-block">
          <p 
            class="product-name"
            :style="`-webkit-line-clamp: ${state.textAtom.maxLines}`"
          >
            {{ state.textAtom.text }}
          </p>
        </div>

        <!-- Список меток (рейтинг, отзывы) -->
        <div v-else-if="state.type === 'labelList'" class="label-list">
          <div 
            v-for="(item, idx) in state.labelList.items" 
            :key="idx"
            class="label-item"
          >
            <span v-if="item.icon" class="label-icon">
              {{ getLabelIcon(item.icon.image) }}
            </span>
            <span :class="`label-text label-text--${item.titleColor}`">
              {{ item.title }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Product, ProductState, TrackingAction } from './ProductCard.types'

interface Props {
  product: Product
  lazy?: boolean
  trackingEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  lazy: true,
  trackingEnabled: true
})

const emit = defineEmits<{
  'click': [product: Product, event: MouseEvent]
  'right-click': [product: Product, event: MouseEvent]
  'aux-click': [product: Product, event: MouseEvent]
  'favorite-toggle': [skuId: string, isFavorite: boolean]
  'tracking': [action: TrackingAction]
}>()

// Состояние
const isFavorite = ref(props.product.favButton.isFav)
const isHovered = ref(false)
const imageError = ref(false)

// Основное изображение
const mainImage = computed(() => props.product.images[0])

// URL изображения с fallback
const imageUrl = computed(() => {
  if (imageError.value) {
    return '/placeholder-product.svg'
  }
  return mainImage.value?.link || '/placeholder-product.svg'
})

// Название товара для alt
const productName = computed(() => {
  const nameState = props.product.state.find(s => s.type === 'textAtom')
  return (nameState as any)?.textAtom?.text || 'Товар'
})

// Стили для бейджа
const badgeStyles = computed(() => {
  const badge = props.product.leftBottomBadge
  if (!badge) return {}
  
  return {
    backgroundColor: badge.backgroundColor,
    color: badge.tintColor || '#ffffff'
  }
})

// Обработчики событий с трекингом
const handleClick = (event: MouseEvent) => {
  emit('click', props.product, event)
  
  if (props.trackingEnabled && props.product.trackingInfo.click) {
    sendTracking(props.product.trackingInfo.click)
  }
  
  // Переход по ссылке
  if (props.product.action.link) {
    window.location.href = props.product.action.link
  }
}

const handleRightClick = (event: MouseEvent) => {
  event.preventDefault()
  emit('right-click', props.product, event)
  
  if (props.trackingEnabled && props.product.trackingInfo.right_click) {
    sendTracking(props.product.trackingInfo.right_click)
  }
}

const handleAuxClick = (event: MouseEvent) => {
  emit('aux-click', props.product, event)
  
  if (props.trackingEnabled && props.product.trackingInfo.aux_click) {
    sendTracking(props.product.trackingInfo.aux_click)
  }
}

const handleMouseEnter = () => {
  isHovered.value = true
}

const handleMouseLeave = () => {
  isHovered.value = false
}

// Переключение избранного
const toggleFavorite = async () => {
  const newState = !isFavorite.value
  const apiUrl = newState 
    ? props.product.favButton.favLink 
    : props.product.favButton.unfavLink
  
  try {
    // Здесь был бы API вызов
    console.log(`API call to: ${apiUrl}`)
    
    isFavorite.value = newState
    emit('favorite-toggle', props.product.skuId, newState)
    
    // Трекинг избранного
    if (props.trackingEnabled && props.product.favButton.trackingInfo?.click) {
      sendTracking(props.product.favButton.trackingInfo.click)
    }
  } catch (error) {
    console.error('Failed to toggle favorite:', error)
  }
}

// Обработка ошибки загрузки изображения
const handleImageError = () => {
  imageError.value = true
}

// Отправка трекинга
const sendTracking = (action: TrackingAction) => {
  emit('tracking', action)
  
  // Логика отправки в аналитику
  if (typeof window !== 'undefined' && window.performance) {
    performance.mark(`tracking-${action.actionType}-${Date.now()}`)
  }
  
  console.log('Tracking:', action)
}

// Получение иконки бейджа
const getBadgeIcon = (iconName: string): string => {
  const icons: Record<string, string> = {
    'ic_s_hot_filled_compact': '🔥',
    'ic_s_like_filled_compact': '👍'
  }
  return icons[iconName] || ''
}

// Получение иконки метки
const getLabelIcon = (iconName: string): string => {
  const icons: Record<string, string> = {
    'ic_s_star_filled_compact': '⭐',
    'ic_s_dialog_filled_compact': '💬',
    'ic_s_ozon_circle_filled_compact': 'O'
  }
  return icons[iconName] || ''
}

// Трекинг просмотра при появлении
onMounted(() => {
  if (props.trackingEnabled && props.product.trackingInfo.view) {
    // Используем IntersectionObserver для трекинга просмотра
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          sendTracking(props.product.trackingInfo.view)
          observer.disconnect()
        }
      },
      { threshold: 0.5 }
    )
    
    // Наблюдаем за элементом карточки
    const element = document.querySelector(`[data-sku="${props.product.skuId}"]`)
    if (element) {
      observer.observe(element)
    }
  }
})
</script>

<style scoped>
/* Карточка товара */
.product-card {
  position: relative;
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 26, 52, 0.08);
}

/* Изображение */
.product-card__image-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  background: #f6f7f8;
  overflow: hidden;
}

.product-card__image {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.product-card__image--scale_aspect_fit {
  object-fit: contain;
}

.product-card__image--scale_aspect_fill {
  object-fit: cover;
}

/* Бейдж */
.product-card__badge {
  position: absolute;
  left: 8px;
  bottom: 8px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  line-height: 16px;
}

.badge-icon {
  font-size: 14px;
}

/* Кнопка избранного */
.product-card__favorite {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
}

.product-card__favorite:hover {
  background: #fff;
  transform: scale(1.1);
}

.product-card__favorite.is-favorite svg path {
  animation: heartBeat 0.3s ease;
}

@keyframes heartBeat {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

/* Контент */
.product-card__content {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  flex: 1;
}

/* Блок цены */
.price-block {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.price {
  font-weight: 600;
}

.price--price {
  font-size: 18px;
  line-height: 24px;
  color: #001a34;
}

.price--original_price {
  font-size: 14px;
  line-height: 20px;
  color: #9ca0a5;
  text-decoration: line-through;
}

.price-discount {
  padding: 2px 4px;
  background: #f91155;
  color: #fff;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  line-height: 16px;
}

/* Текстовый блок */
.text-block {
  flex: 1;
}

.product-name {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 14px;
  line-height: 20px;
  color: #001a34;
  margin: 0;
}

/* Список меток */
.label-list {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  line-height: 16px;
}

.label-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.label-icon {
  font-size: 12px;
}

.label-text--textPremium {
  color: #001a34;
  font-weight: 600;
}

.label-text--textSecondary {
  color: #70757a;
}

.label-text--textOzon {
  color: #005bff;
  font-weight: 600;
}

/* Состояния */
.product-card--adult {
  filter: blur(8px);
}

.product-card--blur {
  opacity: 0.5;
}
</style>