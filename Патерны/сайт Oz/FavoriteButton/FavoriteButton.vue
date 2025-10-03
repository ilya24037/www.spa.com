<template>
  <button
    class="favorite-button"
    :class="buttonClasses"
    :aria-label="ariaLabel"
    :aria-pressed="isFavorite"
    :disabled="isLoading"
    @click="handleClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Основная иконка -->
    <div class="favorite-icon-wrapper">
      <svg
        class="favorite-icon"
        :class="{
          'favorite-icon--animating': isAnimating,
          'favorite-icon--filled': isFavorite
        }"
        :width="iconSize"
        :height="iconSize"
        viewBox="0 0 24 24"
        :style="iconStyles"
      >
        <!-- Градиент для заполненного сердца -->
        <defs>
          <linearGradient id="heart-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" :stop-color="gradientStart" />
            <stop offset="100%" :stop-color="gradientEnd" />
          </linearGradient>
        </defs>
        
        <!-- Контур сердца -->
        <path
          class="favorite-icon-outline"
          :d="DEFAULT_ICONS.empty"
          :fill="outlineFill"
          :stroke="outlineStroke"
          stroke-width="2"
        />
        
        <!-- Заполненное сердце -->
        <path
          v-if="isFavorite || isAnimating"
          class="favorite-icon-filled"
          :d="DEFAULT_ICONS.filled"
          :fill="filledFill"
          :style="filledStyles"
        />
      </svg>
      
      <!-- Эффект burst при добавлении -->
      <Transition name="burst">
        <div v-if="showBurst" class="favorite-burst">
          <span v-for="i in 6" :key="i" class="burst-particle" />
        </div>
      </Transition>
      
      <!-- Индикатор загрузки -->
      <Transition name="fade">
        <div v-if="isLoading" class="favorite-loader">
          <svg class="loader-spinner" width="16" height="16" viewBox="0 0 24 24">
            <circle 
              cx="12" 
              cy="12" 
              r="10" 
              stroke="currentColor" 
              stroke-width="3" 
              fill="none"
              stroke-dasharray="31.4"
              stroke-dashoffset="10"
            />
          </svg>
        </div>
      </Transition>
    </div>
    
    <!-- Текстовая метка -->
    <Transition name="label">
      <span 
        v-if="showLabel"
        class="favorite-label"
        :class="`favorite-label--${labelPosition}`"
      >
        {{ currentLabel }}
      </span>
    </Transition>
    
    <!-- Счетчик -->
    <Transition name="count">
      <span 
        v-if="showCount && count > 0"
        class="favorite-count"
      >
        {{ formattedCount }}
      </span>
    </Transition>
    
    <!-- Tooltip -->
    <Transition name="tooltip">
      <div 
        v-if="showTooltip && isHovered && !isLoading"
        class="favorite-tooltip"
        :class="`favorite-tooltip--${tooltipPosition}`"
      >
        {{ tooltipText }}
      </div>
    </Transition>
  </button>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { 
  FavoriteButtonConfig,
  FavoriteButtonState,
  AnimationType
} from './FavoriteButton.types'
import { 
  BUTTON_SIZES,
  FAVORITE_COLORS,
  FAVORITE_LABELS,
  DEFAULT_ICONS,
  ANIMATION_CONFIGS
} from './FavoriteButton.types'

interface Props extends FavoriteButtonConfig {
  itemId: string
  initialFavorite?: boolean
  count?: number
  disabled?: boolean
  animationType?: AnimationType
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'default',
  shape: 'circle',
  showLabel: false,
  labelPosition: 'right',
  animated: true,
  showTooltip: true,
  tooltipPosition: 'top',
  showCount: false,
  confirmRemove: false,
  syncAcrossTabs: true,
  initialFavorite: false,
  animationType: 'heart-beat'
})

const emit = defineEmits<{
  'toggle': [isFavorite: boolean]
  'add': []
  'remove': []
  'error': [error: Error]
}>()

// Состояние
const isFavorite = ref(props.initialFavorite)
const isLoading = ref(false)
const isHovered = ref(false)
const isAnimating = ref(false)
const showBurst = ref(false)
const error = ref<string | null>(null)

// Размер иконки
const iconSize = computed(() => BUTTON_SIZES[props.size])

// Классы кнопки
const buttonClasses = computed(() => [
  `favorite-button--${props.size}`,
  `favorite-button--${props.variant}`,
  `favorite-button--${props.shape}`,
  {
    'favorite-button--favorite': isFavorite.value,
    'favorite-button--loading': isLoading.value,
    'favorite-button--hovered': isHovered.value,
    'favorite-button--disabled': props.disabled,
    'favorite-button--with-label': props.showLabel,
    'favorite-button--with-count': props.showCount
  }
])

// ARIA label для доступности
const ariaLabel = computed(() => {
  if (isLoading.value) return FAVORITE_LABELS.loading
  return isFavorite.value ? FAVORITE_LABELS.remove : FAVORITE_LABELS.add
})

// Текущая метка
const currentLabel = computed(() => {
  if (isLoading.value) return FAVORITE_LABELS.loading
  if (error.value) return FAVORITE_LABELS.error
  return isFavorite.value ? FAVORITE_LABELS.added : FAVORITE_LABELS.add
})

// Текст tooltip
const tooltipText = computed(() => {
  return isFavorite.value ? FAVORITE_LABELS.remove : FAVORITE_LABELS.add
})

// Форматированный счетчик
const formattedCount = computed(() => {
  const count = props.count || 0
  if (count > 999) return `${Math.floor(count / 1000)}k`
  return count.toString()
})

// Цвета и стили
const gradientStart = computed(() => '#ff6b6b')
const gradientEnd = computed(() => '#f91155')

const outlineFill = computed(() => {
  if (isFavorite.value) return 'none'
  return 'none'
})

const outlineStroke = computed(() => {
  if (isHovered.value) return FAVORITE_COLORS.hover
  if (isFavorite.value) return FAVORITE_COLORS.active
  return FAVORITE_COLORS.default
})

const filledFill = computed(() => {
  if (props.variant === 'filled') return 'url(#heart-gradient)'
  return FAVORITE_COLORS.active
})

const iconStyles = computed(() => ({
  transition: `all ${ANIMATION_CONFIGS[props.animationType].duration}ms ${ANIMATION_CONFIGS[props.animationType].easing}`
}))

const filledStyles = computed(() => ({
  opacity: isFavorite.value ? 1 : 0,
  transform: isFavorite.value ? 'scale(1)' : 'scale(0)',
  transformOrigin: 'center',
  transition: `all ${ANIMATION_CONFIGS[props.animationType].duration}ms ${ANIMATION_CONFIGS[props.animationType].easing}`
}))

// Обработчики
const handleClick = async () => {
  if (isLoading.value || props.disabled) return
  
  // Проверка подтверждения удаления
  if (props.confirmRemove && isFavorite.value) {
    if (!confirm(FAVORITE_LABELS.confirmRemove)) return
  }
  
  isLoading.value = true
  error.value = null
  
  try {
    // Анимация
    if (props.animated) {
      isAnimating.value = true
      if (!isFavorite.value) {
        showBurst.value = true
        setTimeout(() => {
          showBurst.value = false
        }, 500)
      }
    }
    
    // Переключение состояния
    const newState = !isFavorite.value
    
    // Эмуляция API вызова
    await new Promise(resolve => setTimeout(resolve, 300))
    
    isFavorite.value = newState
    
    // События
    emit('toggle', newState)
    if (newState) {
      emit('add')
    } else {
      emit('remove')
    }
    
    // Синхронизация между вкладками
    if (props.syncAcrossTabs && typeof window !== 'undefined') {
      const channel = new BroadcastChannel('favorites')
      channel.postMessage({
        itemId: props.itemId,
        isFavorite: newState
      })
    }
    
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Ошибка'
    emit('error', err as Error)
  } finally {
    isLoading.value = false
    setTimeout(() => {
      isAnimating.value = false
    }, ANIMATION_CONFIGS[props.animationType].duration)
  }
}

const handleMouseEnter = () => {
  isHovered.value = true
}

const handleMouseLeave = () => {
  isHovered.value = false
}

// Синхронизация между вкладками
if (props.syncAcrossTabs && typeof window !== 'undefined') {
  const channel = new BroadcastChannel('favorites')
  channel.onmessage = (event) => {
    if (event.data.itemId === props.itemId) {
      isFavorite.value = event.data.isFavorite
    }
  }
}

// Слежение за внешними изменениями
watch(() => props.initialFavorite, (newVal) => {
  isFavorite.value = newVal
})
</script>

<style scoped>
/* Основная кнопка */
.favorite-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 0;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.favorite-button:focus {
  outline: none;
}

.favorite-button:focus-visible {
  outline: 2px solid #005bff;
  outline-offset: 2px;
}

.favorite-button--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Размеры */
.favorite-button--xs {
  width: 20px;
  height: 20px;
}

.favorite-button--sm {
  width: 24px;
  height: 24px;
}

.favorite-button--md {
  width: 32px;
  height: 32px;
}

.favorite-button--lg {
  width: 40px;
  height: 40px;
}

.favorite-button--xl {
  width: 48px;
  height: 48px;
}

/* Варианты */
.favorite-button--default {
  background: transparent;
}

.favorite-button--minimal {
  padding: 4px;
}

.favorite-button--filled {
  background: rgba(249, 17, 85, 0.1);
}

.favorite-button--filled.favorite-button--favorite {
  background: rgba(249, 17, 85, 0.15);
}

.favorite-button--outlined {
  border: 1px solid #e1e3e6;
  background: white;
}

.favorite-button--ghost {
  background: rgba(255, 255, 255, 0.9);
}

.favorite-button--floating {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Формы */
.favorite-button--circle {
  border-radius: 50%;
}

.favorite-button--rounded {
  border-radius: 8px;
}

.favorite-button--square {
  border-radius: 0;
}

/* Иконка */
.favorite-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.favorite-icon {
  position: relative;
  z-index: 1;
}

.favorite-icon-outline {
  transition: all 0.3s ease;
}

.favorite-icon-filled {
  position: absolute;
  top: 0;
  left: 0;
}

/* Анимации иконки */
.favorite-icon--animating {
  animation: heartBeat 0.3s ease;
}

@keyframes heartBeat {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.favorite-button--favorite .favorite-icon {
  animation: favoritePulse 0.4s ease;
}

@keyframes favoritePulse {
  0% { transform: scale(1) rotate(0deg); }
  25% { transform: scale(1.3) rotate(-5deg); }
  50% { transform: scale(1.1) rotate(5deg); }
  75% { transform: scale(1.2) rotate(-3deg); }
  100% { transform: scale(1) rotate(0deg); }
}

/* Эффект burst */
.favorite-burst {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.burst-particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #f91155;
  border-radius: 50%;
  animation: burstParticle 0.5s ease-out forwards;
}

.burst-particle:nth-child(1) { transform: rotate(0deg); }
.burst-particle:nth-child(2) { transform: rotate(60deg); }
.burst-particle:nth-child(3) { transform: rotate(120deg); }
.burst-particle:nth-child(4) { transform: rotate(180deg); }
.burst-particle:nth-child(5) { transform: rotate(240deg); }
.burst-particle:nth-child(6) { transform: rotate(300deg); }

@keyframes burstParticle {
  0% {
    opacity: 1;
    transform: translateX(0) scale(0);
  }
  100% {
    opacity: 0;
    transform: translateX(20px) scale(1);
  }
}

/* Загрузка */
.favorite-loader {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
}

.loader-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Метка */
.favorite-label {
  font-size: 14px;
  color: #001a34;
  white-space: nowrap;
}

.favorite-label--bottom {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-top: 4px;
}

/* Счетчик */
.favorite-count {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  background: #f91155;
  color: white;
  font-size: 10px;
  font-weight: 700;
  line-height: 16px;
  text-align: center;
  border-radius: 8px;
}

/* Tooltip */
.favorite-tooltip {
  position: absolute;
  padding: 4px 8px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  font-size: 12px;
  border-radius: 4px;
  white-space: nowrap;
  pointer-events: none;
  z-index: 1000;
}

.favorite-tooltip--top {
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
}

.favorite-tooltip--bottom {
  top: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
}

.favorite-tooltip--left {
  right: calc(100% + 8px);
  top: 50%;
  transform: translateY(-50%);
}

.favorite-tooltip--right {
  left: calc(100% + 8px);
  top: 50%;
  transform: translateY(-50%);
}

.favorite-tooltip::after {
  content: '';
  position: absolute;
  border: 4px solid transparent;
}

.favorite-tooltip--top::after {
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-top-color: rgba(0, 0, 0, 0.8);
}

/* Hover эффекты */
.favorite-button:hover:not(.favorite-button--disabled) {
  transform: scale(1.1);
}

.favorite-button:hover .favorite-icon-outline {
  stroke: #f91155;
}

.favorite-button:active:not(.favorite-button--disabled) {
  transform: scale(0.95);
}

/* Анимации переходов */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.burst-enter-active {
  transition: all 0.5s ease-out;
}

.burst-leave-active {
  transition: all 0.3s ease-in;
}

.burst-enter-from,
.burst-leave-to {
  opacity: 0;
  transform: translate(-50%, -50%) scale(0);
}

.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}

/* Адаптивность */
@media (max-width: 768px) {
  .favorite-button--xl {
    width: 40px;
    height: 40px;
  }
  
  .favorite-label {
    font-size: 12px;
  }
}

/* Режим высокой контрастности */
@media (prefers-contrast: high) {
  .favorite-button {
    border: 1px solid currentColor;
  }
}

/* Уменьшение движения */
@media (prefers-reduced-motion: reduce) {
  .favorite-button,
  .favorite-icon,
  .favorite-icon-outline,
  .favorite-icon-filled {
    animation: none !important;
    transition: none !important;
  }
}
</style>