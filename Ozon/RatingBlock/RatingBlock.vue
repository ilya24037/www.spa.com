<template>
  <div 
    class="rating-block"
    :class="[
      `rating-block--${layout}`,
      `rating-block--size-${starSize}`,
      {
        'rating-block--interactive': interactive,
        'rating-block--animated': animated
      }
    ]"
    @click="handleClick"
  >
    <!-- Звезды рейтинга -->
    <div 
      v-if="showStars"
      class="rating-stars"
      @mouseleave="handleMouseLeave"
    >
      <div
        v-for="star in 5"
        :key="star"
        class="rating-star-wrapper"
        @mouseenter="handleStarHover(star)"
        @click.stop="handleStarClick(star)"
      >
        <svg
          class="rating-star"
          :class="{
            'rating-star--filled': getStarFilled(star) === 1,
            'rating-star--half': getStarFilled(star) > 0 && getStarFilled(star) < 1,
            'rating-star--empty': getStarFilled(star) === 0,
            'rating-star--hovered': isHovered && hoveredValue >= star
          }"
          :width="starSizeValue"
          :height="starSizeValue"
          viewBox="0 0 24 24"
          :style="getStarStyles(star)"
        >
          <!-- Звезда с градиентным заполнением для частичного рейтинга -->
          <defs>
            <linearGradient :id="`star-gradient-${star}`" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop :offset="`${getStarFilled(star) * 100}%`" :stop-color="starColor" />
              <stop :offset="`${getStarFilled(star) * 100}%`" :stop-color="emptyColor" />
            </linearGradient>
          </defs>
          
          <path
            :fill="`url(#star-gradient-${star})`"
            :stroke="starBorderColor"
            stroke-width="1"
            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
          />
        </svg>
      </div>
    </div>
    
    <!-- Числовой рейтинг -->
    <span 
      v-if="showNumeric"
      class="rating-numeric"
      :style="{ color: ratingColor }"
    >
      {{ formattedRating }}
    </span>
    
    <!-- Количество отзывов -->
    <span 
      v-if="showReviews && reviewsCount > 0"
      class="rating-reviews"
      @click.stop="handleReviewClick"
    >
      {{ formattedReviews }}
    </span>
    
    <!-- Процент рекомендаций -->
    <span 
      v-if="showRecommendation && recommendation"
      class="rating-recommendation"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" class="recommendation-icon">
        <path 
          fill="currentColor" 
          d="M23 10C23 8.89 22.1 8 21 8H14.68L15.64 3.43C15.66 3.33 15.67 3.22 15.67 3.11C15.67 2.7 15.5 2.32 15.23 2.05L14.17 1L7.59 7.58C7.22 7.95 7 8.45 7 9V19C7 20.1 7.9 21 9 21H18C18.83 21 19.54 20.5 19.84 19.78L22.86 12.73C22.95 12.5 23 12.26 23 12V10M1 21H5V9H1V21Z"
        />
      </svg>
      {{ formattedRecommendation }}
    </span>
    
    <!-- Детальное распределение (для расширенного вида) -->
    <Transition name="distribution">
      <div 
        v-if="showDistribution && distribution"
        class="rating-distribution"
      >
        <div 
          v-for="stars in [5, 4, 3, 2, 1]"
          :key="stars"
          class="distribution-row"
        >
          <span class="distribution-label">{{ stars }}</span>
          <div class="distribution-bar">
            <div 
              class="distribution-fill"
              :style="{ 
                width: `${distribution[stars].percentage}%`,
                backgroundColor: getDistributionColor(stars)
              }"
            />
          </div>
          <span class="distribution-count">{{ distribution[stars].count }}</span>
        </div>
      </div>
    </Transition>
    
    <!-- Tooltip для интерактивного режима -->
    <Transition name="tooltip">
      <div 
        v-if="showTooltip && isHovered && hoveredValue"
        class="rating-tooltip"
      >
        {{ RATING_LABELS[hoveredValue] }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { 
  RatingData,
  RatingDisplayConfig,
  RatingDistribution,
  StarSize
} from './RatingBlock.types'
import { 
  RATING_COLORS,
  STAR_SIZES,
  RATING_THRESHOLDS,
  RATING_LABELS,
  DEFAULT_FORMATTERS
} from './RatingBlock.types'

interface Props extends RatingDisplayConfig {
  rating: number
  reviewsCount?: number
  recommendation?: number
  distribution?: RatingDistribution
  readonly?: boolean
  allowHalfStars?: boolean
  showTooltip?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rating: 0,
  reviewsCount: 0,
  showStars: true,
  showNumeric: true,
  showReviews: true,
  showDistribution: false,
  showRecommendation: false,
  starSize: 'md',
  colorScheme: 'gold',
  layout: 'inline',
  interactive: false,
  animated: true,
  readonly: true,
  allowHalfStars: true,
  showTooltip: false
})

const emit = defineEmits<{
  'click': []
  'review-click': []
  'rate': [value: number]
}>()

// Состояние
const isHovered = ref(false)
const hoveredValue = ref<number | null>(null)

// Размер звезд
const starSizeValue = computed(() => STAR_SIZES[props.starSize])

// Форматированный рейтинг
const formattedRating = computed(() => {
  return DEFAULT_FORMATTERS.rating!(props.rating)
})

// Форматированные отзывы
const formattedReviews = computed(() => {
  return DEFAULT_FORMATTERS.reviews!(props.reviewsCount || 0)
})

// Форматированные рекомендации
const formattedRecommendation = computed(() => {
  if (!props.recommendation) return ''
  return DEFAULT_FORMATTERS.recommendation!(props.recommendation)
})

// Цвет рейтинга в зависимости от значения
const ratingColor = computed(() => {
  if (props.rating >= RATING_THRESHOLDS.excellent) return RATING_COLORS.green
  if (props.rating >= RATING_THRESHOLDS.good) return RATING_COLORS.goldDark
  if (props.rating >= RATING_THRESHOLDS.average) return RATING_COLORS.gold
  return RATING_COLORS.red
})

// Цвет звезд
const starColor = computed(() => {
  if (props.colorScheme === 'gradient') {
    return RATING_COLORS.gradient.start
  }
  if (props.colorScheme === 'monochrome') {
    return RATING_COLORS.textDark
  }
  return RATING_COLORS.gold
})

// Цвет пустых звезд
const emptyColor = computed(() => RATING_COLORS.gray)

// Цвет границы звезд
const starBorderColor = computed(() => {
  if (props.colorScheme === 'monochrome') {
    return RATING_COLORS.text
  }
  return 'transparent'
})

// Вычисление заполнения звезды
const getStarFilled = (star: number): number => {
  const value = hoveredValue.value !== null && !props.readonly 
    ? hoveredValue.value 
    : props.rating
  
  if (value >= star) return 1
  if (value > star - 1) return value - (star - 1)
  return 0
}

// Стили для звезды
const getStarStyles = (star: number) => {
  const styles: any = {}
  
  if (props.animated) {
    styles.animationDelay = `${star * 50}ms`
  }
  
  if (props.colorScheme === 'gradient') {
    const filled = getStarFilled(star)
    if (filled > 0) {
      styles.filter = `drop-shadow(0 1px 2px rgba(255, 165, 0, ${filled * 0.3}))`
    }
  }
  
  return styles
}

// Цвет для полосы распределения
const getDistributionColor = (stars: number): string => {
  if (stars >= 4) return RATING_COLORS.green
  if (stars >= 3) return RATING_COLORS.gold
  return RATING_COLORS.red
}

// Обработчики событий
const handleClick = () => {
  emit('click')
}

const handleReviewClick = () => {
  emit('review-click')
}

const handleStarHover = (star: number) => {
  if (!props.readonly && props.interactive) {
    isHovered.value = true
    hoveredValue.value = props.allowHalfStars 
      ? star 
      : Math.ceil(star)
  }
}

const handleMouseLeave = () => {
  if (!props.readonly && props.interactive) {
    isHovered.value = false
    hoveredValue.value = null
  }
}

const handleStarClick = (star: number) => {
  if (!props.readonly && props.interactive) {
    const value = props.allowHalfStars ? star : Math.ceil(star)
    emit('rate', value)
  }
}
</script>

<style scoped>
/* Основной блок рейтинга */
.rating-block {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  position: relative;
  user-select: none;
}

/* Макеты */
.rating-block--inline {
  flex-direction: row;
}

.rating-block--stacked {
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.rating-block--detailed {
  flex-direction: column;
  gap: 12px;
}

.rating-block--compact {
  gap: 4px;
}

.rating-block--card {
  flex-direction: column;
  padding: 16px;
  background: #fff;
  border: 1px solid #e1e3e6;
  border-radius: 8px;
  gap: 12px;
}

/* Интерактивный режим */
.rating-block--interactive {
  cursor: pointer;
}

.rating-block--interactive .rating-star-wrapper {
  cursor: pointer;
  transition: transform 0.2s ease;
}

.rating-block--interactive .rating-star-wrapper:hover {
  transform: scale(1.1);
}

/* Звезды */
.rating-stars {
  display: flex;
  gap: 2px;
  position: relative;
}

.rating-star-wrapper {
  position: relative;
}

.rating-star {
  transition: all 0.3s ease;
}

.rating-star--filled {
  filter: drop-shadow(0 1px 2px rgba(255, 165, 0, 0.3));
}

.rating-star--hovered {
  transform: scale(1.15);
  filter: drop-shadow(0 2px 4px rgba(255, 165, 0, 0.5));
}

/* Анимация появления звезд */
.rating-block--animated .rating-star {
  animation: starAppear 0.3s ease-out backwards;
}

@keyframes starAppear {
  from {
    opacity: 0;
    transform: scale(0) rotate(-180deg);
  }
  to {
    opacity: 1;
    transform: scale(1) rotate(0);
  }
}

/* Числовой рейтинг */
.rating-numeric {
  font-size: 16px;
  font-weight: 700;
  line-height: 1;
}

.rating-block--size-xs .rating-numeric {
  font-size: 12px;
}

.rating-block--size-sm .rating-numeric {
  font-size: 14px;
}

.rating-block--size-lg .rating-numeric {
  font-size: 18px;
}

.rating-block--size-xl .rating-numeric {
  font-size: 24px;
}

/* Количество отзывов */
.rating-reviews {
  font-size: 14px;
  color: #70757a;
  cursor: pointer;
  transition: color 0.2s ease;
  text-decoration: none;
  border-bottom: 1px dashed transparent;
}

.rating-reviews:hover {
  color: #005bff;
  border-bottom-color: #005bff;
}

/* Рекомендации */
.rating-recommendation {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: #00a854;
  font-weight: 500;
}

.recommendation-icon {
  color: currentColor;
}

/* Распределение рейтинга */
.rating-distribution {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
  min-width: 200px;
}

.distribution-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.distribution-label {
  display: flex;
  align-items: center;
  min-width: 20px;
  font-size: 12px;
  color: #70757a;
}

.distribution-label::after {
  content: '★';
  margin-left: 2px;
  color: #ffa500;
}

.distribution-bar {
  flex: 1;
  height: 8px;
  background: #f0f2f5;
  border-radius: 4px;
  overflow: hidden;
}

.distribution-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.distribution-count {
  min-width: 30px;
  text-align: right;
  font-size: 12px;
  color: #9ca0a5;
}

/* Tooltip */
.rating-tooltip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  padding: 4px 8px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  font-size: 12px;
  border-radius: 4px;
  white-space: nowrap;
  pointer-events: none;
  z-index: 1000;
}

.rating-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: rgba(0, 0, 0, 0.8);
}

/* Анимации */
.distribution-enter-active,
.distribution-leave-active {
  transition: all 0.3s ease;
}

.distribution-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.distribution-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(4px);
}

/* Адаптивность */
@media (max-width: 768px) {
  .rating-block--inline {
    flex-wrap: wrap;
  }
  
  .rating-distribution {
    min-width: 100%;
  }
  
  .rating-block--card {
    padding: 12px;
  }
}

/* Режим высокой контрастности */
@media (prefers-contrast: high) {
  .rating-star {
    stroke-width: 2;
  }
  
  .rating-numeric {
    font-weight: 800;
  }
}

/* Уменьшение движения */
@media (prefers-reduced-motion: reduce) {
  .rating-star,
  .rating-star-wrapper,
  .distribution-fill {
    transition: none;
  }
  
  .rating-block--animated .rating-star {
    animation: none;
  }
}
</style>