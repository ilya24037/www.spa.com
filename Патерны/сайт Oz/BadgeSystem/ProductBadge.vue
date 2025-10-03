<template>
  <div
    class="product-badge"
    :class="[
      `badge--${theme}`,
      `badge--${type}`,
      { 
        'badge--animated': hasAnimation,
        'badge--gradient': hasGradient
      }
    ]"
    :style="badgeStyles"
    :data-badge-type="type"
    :data-priority="priority"
  >
    <!-- Иконка бейджа -->
    <span 
      v-if="icon && iconPosition === 'left'"
      class="badge-icon badge-icon--left"
      :style="iconStyles"
    >
      {{ icon }}
    </span>
    
    <!-- Текст бейджа -->
    <span class="badge-text" :style="textStyles">
      {{ displayText }}
    </span>
    
    <!-- Иконка справа -->
    <span 
      v-if="icon && iconPosition === 'right'"
      class="badge-icon badge-icon--right"
      :style="iconStyles"
    >
      {{ icon }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  BadgeType, 
  BadgeTheme, 
  BadgeAnimation,
  BADGE_PRESETS,
  BADGE_SIZES 
} from './BadgeSystem.types'

interface Props {
  type: BadgeType
  text?: string
  icon?: string
  theme?: BadgeTheme
  animation?: BadgeAnimation
  iconPosition?: 'left' | 'right'
  customColors?: {
    background?: string
    text?: string
    icon?: string
  }
  priority?: number
}

const props = withDefaults(defineProps<Props>(), {
  theme: 'STYLE_TYPE_MEDIUM',
  iconPosition: 'left',
  animation: 'none'
})

// Получаем пресет для типа бейджа
const preset = computed(() => BADGE_PRESETS[props.type])

// Текст для отображения
const displayText = computed(() => props.text || preset.value.text)

// Иконка
const icon = computed(() => props.icon || preset.value.icon)

// Приоритет
const priority = computed(() => props.priority || preset.value.priority || 0)

// Проверяем наличие градиента
const hasGradient = computed(() => {
  return !!preset.value.colors.gradient || 
         !!props.customColors?.background?.includes('gradient')
})

// Проверяем наличие анимации
const hasAnimation = computed(() => {
  const animation = props.animation !== 'none' 
    ? props.animation 
    : preset.value.animation
  return animation && animation !== 'none'
})

// Стили бейджа
const badgeStyles = computed(() => {
  const colors = { ...preset.value.colors, ...props.customColors }
  const size = BADGE_SIZES[props.theme]
  
  const styles: any = {
    height: `${size.height}px`,
    padding: `${size.padding.vertical}px ${size.padding.horizontal}px`,
    fontSize: `${size.fontSize}px`
  }
  
  // Фон с поддержкой градиента
  if (colors.gradient) {
    const { startColor, endColor, angle = 90 } = colors.gradient
    styles.background = `linear-gradient(${angle}deg, ${startColor}, ${endColor})`
  } else if (colors.background?.includes('gradient')) {
    styles.background = colors.background
  } else {
    styles.backgroundColor = colors.background
  }
  
  // Граница
  if (colors.border) {
    styles.border = `1px solid ${colors.border}`
  }
  
  return styles
})

// Стили текста
const textStyles = computed(() => {
  const colors = { ...preset.value.colors, ...props.customColors }
  return {
    color: colors.text
  }
})

// Стили иконки
const iconStyles = computed(() => {
  const colors = { ...preset.value.colors, ...props.customColors }
  const size = BADGE_SIZES[props.theme]
  
  return {
    fontSize: `${size.iconSize}px`,
    color: colors.icon || colors.text
  }
})

// Класс темы
const theme = computed(() => props.theme.toLowerCase().replace(/_/g, '-'))

// Класс типа
const type = computed(() => props.type.toLowerCase().replace(/_/g, '-'))
</script>

<style scoped>
/* Базовые стили бейджа */
.product-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-radius: 4px;
  font-weight: 600;
  white-space: nowrap;
  user-select: none;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

/* Размеры тем */
.badge--style-type-small {
  border-radius: 3px;
  font-size: 10px;
  line-height: 1.2;
}

.badge--style-type-medium {
  border-radius: 4px;
  font-size: 12px;
  line-height: 1.3;
}

.badge--style-type-large {
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.4;
}

.badge--style-type-compact {
  border-radius: 2px;
  font-size: 10px;
  line-height: 1.1;
}

/* Текст бейджа */
.badge-text {
  position: relative;
  z-index: 1;
  font-weight: 700;
  letter-spacing: 0.3px;
}

/* Иконка бейджа */
.badge-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
}

.badge-icon--left {
  margin-right: -2px;
}

.badge-icon--right {
  margin-left: -2px;
}

/* Анимации */
.badge--animated {
  animation-duration: 2s;
  animation-iteration-count: infinite;
}

/* Pulse анимация */
.badge--sale.badge--animated,
.badge--discount.badge--animated {
  animation-name: badgePulse;
}

@keyframes badgePulse {
  0%, 100% { 
    transform: scale(1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  50% { 
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }
}

/* Flash анимация */
.badge--hot.badge--animated {
  animation-name: badgeFlash;
}

@keyframes badgeFlash {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Bounce анимация */
.badge--hit.badge--animated {
  animation-name: badgeBounce;
}

@keyframes badgeBounce {
  0%, 100% { transform: translateY(0); }
  25% { transform: translateY(-2px); }
  75% { transform: translateY(1px); }
}

/* Shake анимация */
.badge--last-items.badge--animated {
  animation-name: badgeShake;
  animation-duration: 0.5s;
}

@keyframes badgeShake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  75% { transform: translateX(2px); }
}

/* Градиентный фон с анимацией */
.badge--gradient.badge--animated::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 200%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255,255,255,0.3) 50%,
    transparent 100%
  );
  animation: gradientShine 3s infinite;
}

@keyframes gradientShine {
  0% { left: -100%; }
  100% { left: 100%; }
}

/* Специальные стили для типов бейджей */

/* Маркетинговые бейджи */
.badge--sale {
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge--hot::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(
    circle,
    rgba(255,255,255,0.2) 0%,
    transparent 70%
  );
  animation: hotGlow 2s ease-in-out infinite;
}

@keyframes hotGlow {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

/* Premium бейдж */
.badge--premium {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.badge--premium .badge-icon {
  animation: starRotate 3s linear infinite;
}

@keyframes starRotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Ozon специфичные */
.badge--ozon-choice,
.badge--ozon-bank,
.badge--ozon-fresh,
.badge--ozon-express {
  font-family: 'Ozon Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Ценовые бейджи */
.badge--discount {
  font-weight: 800;
  min-width: 40px;
  text-align: center;
}

.badge--cashback .badge-icon {
  animation: coinFlip 2s ease-in-out infinite;
}

@keyframes coinFlip {
  0%, 100% { transform: rotateY(0); }
  50% { transform: rotateY(180deg); }
}

/* Доставка */
.badge--express,
.badge--today {
  position: relative;
  padding-left: 20px;
}

.badge--express::before {
  content: '⚡';
  position: absolute;
  left: 6px;
  animation: lightning 1s ease-in-out infinite;
}

@keyframes lightning {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Статусные бейджи */
.badge--last-items {
  background: linear-gradient(90deg, #ff5722, #ff7043);
}

.badge--exclusive {
  background: linear-gradient(135deg, #3f51b5, #5c6bc0);
  position: relative;
}

.badge--exclusive::after {
  content: '';
  position: absolute;
  top: 2px;
  right: 2px;
  width: 4px;
  height: 4px;
  background: white;
  border-radius: 50%;
  animation: exclusiveDot 2s ease-in-out infinite;
}

@keyframes exclusiveDot {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

/* Эко бейдж */
.badge--eco {
  background: linear-gradient(135deg, #8bc34a, #aed581);
}

.badge--eco .badge-icon {
  animation: leafWave 3s ease-in-out infinite;
}

@keyframes leafWave {
  0%, 100% { transform: rotate(-5deg); }
  50% { transform: rotate(5deg); }
}

/* Hover эффекты */
.product-badge:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-badge:hover .badge-text {
  text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Адаптивность */
@media (max-width: 768px) {
  .badge--style-type-large {
    font-size: 12px;
    height: 28px;
    padding: 4px 10px;
  }
  
  .badge--style-type-medium {
    font-size: 11px;
    height: 22px;
    padding: 3px 6px;
  }
  
  .badge--style-type-small {
    font-size: 9px;
    height: 18px;
    padding: 2px 4px;
  }
}

/* Высокий контраст для доступности */
@media (prefers-contrast: high) {
  .product-badge {
    border: 1px solid currentColor;
  }
}

/* Уменьшение движения для доступности */
@media (prefers-reduced-motion: reduce) {
  .badge--animated {
    animation: none;
  }
  
  .badge-icon {
    animation: none;
  }
  
  .badge--gradient.badge--animated::before {
    animation: none;
  }
}
</style>