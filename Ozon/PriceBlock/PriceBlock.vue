<template>
  <div 
    class="price-block"
    :class="`price-block--${styleType}`"
    :data-preset="preset"
  >
    <!-- Основная цена -->
    <div class="price-wrapper">
      <!-- Текущая цена -->
      <span 
        v-if="currentPrice"
        class="price price--current"
        :style="currentPriceStyles"
      >
        {{ currentPrice.text }}
      </span>
      
      <!-- Старая цена -->
      <span 
        v-if="originalPrice"
        class="price price--original"
      >
        {{ originalPrice.text }}
      </span>
    </div>
    
    <!-- Скидка -->
    <span 
      v-if="discount"
      class="price-discount"
      :style="discountStyles"
    >
      {{ discount }}
    </span>
    
    <!-- Дополнительная информация -->
    <div v-if="$slots.additional" class="price-additional">
      <slot name="additional" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PriceState } from './PriceBlock.types'

interface Props {
  priceData: PriceState['priceV2']
  animated?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  animated: true
})

// Парсинг цен из массива Ozon
const currentPrice = computed(() => {
  return props.priceData.price.find(p => p.textStyle === 'PRICE')
})

const originalPrice = computed(() => {
  return props.priceData.price.find(p => p.textStyle === 'ORIGINAL_PRICE')
})

// Скидка
const discount = computed(() => props.priceData.discount)

// Тип стиля из Ozon
const styleType = computed(() => {
  return props.priceData.priceStyle.styleType.toLowerCase().replace('_', '-')
})

// Пресет размера
const preset = computed(() => props.priceData.preset)

// Стили для текущей цены с градиентом (из Ozon)
const currentPriceStyles = computed(() => {
  const gradient = props.priceData.priceStyle.gradient
  if (!gradient) return {}
  
  // Создаем градиентный текст как в Ozon
  if (styleType.value === 'sale-price') {
    return {
      background: `linear-gradient(90deg, ${gradient.startColor}, ${gradient.endColor})`,
      '-webkit-background-clip': 'text',
      '-webkit-text-fill-color': 'transparent',
      'background-clip': 'text'
    }
  }
  
  return {}
})

// Стили для бейджа скидки
const discountStyles = computed(() => {
  const gradient = props.priceData.priceStyle.gradient
  if (!gradient) return {}
  
  return {
    backgroundColor: gradient.startColor || '#F1117E'
  }
})
</script>

<style scoped>
/* Основной блок цены */
.price-block {
  display: inline-flex;
  align-items: baseline;
  gap: 8px;
  position: relative;
}

/* Обертка цен */
.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

/* Базовые стили цены */
.price {
  font-weight: 600;
  line-height: 1.2;
  transition: all 0.3s ease;
}

/* Текущая цена */
.price--current {
  color: #001a34;
}

/* Размеры цен по пресетам Ozon */
[data-preset="SIZE_500"] .price--current {
  font-size: 20px;
  line-height: 28px;
}

[data-preset="SIZE_400"] .price--current {
  font-size: 18px;
  line-height: 24px;
}

[data-preset="SIZE_300"] .price--current {
  font-size: 16px;
  line-height: 22px;
}

/* Старая цена */
.price--original {
  font-size: 14px;
  line-height: 20px;
  color: #9ca0a5;
  text-decoration: line-through;
  font-weight: 400;
}

/* Бейдж скидки */
.price-discount {
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  background: #f91155;
  color: #ffffff;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  line-height: 16px;
  white-space: nowrap;
  animation: discountPulse 2s ease-in-out infinite;
}

@keyframes discountPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

/* Стили по типам из Ozon */

/* SALE_PRICE - цена со скидкой */
.price-block--sale-price .price--current {
  color: #f91155;
  font-weight: 700;
}

.price-block--sale-price .price-discount {
  animation: discountShake 0.5s ease;
}

@keyframes discountShake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  75% { transform: translateX(2px); }
}

/* CARD_PRICE - обычная цена в карточке */
.price-block--card-price .price--current {
  color: #001a34;
}

.price-block--card-price .price-discount {
  background: #001a34;
}

/* ACTUAL_PRICE - актуальная цена */
.price-block--actual-price .price--current {
  color: #00a854;
  font-weight: 700;
}

.price-block--actual-price::before {
  content: '';
  position: absolute;
  left: -20px;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 16px;
  background: #00a854;
  border-radius: 2px;
}

/* Padding bottom из Ozon */
[data-padding-bottom="PADDING_200"] {
  margin-bottom: 8px;
}

[data-padding-bottom="PADDING_300"] {
  margin-bottom: 12px;
}

[data-padding-bottom="PADDING_400"] {
  margin-bottom: 16px;
}

/* Дополнительная информация */
.price-additional {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 8px;
  font-size: 12px;
  color: #70757a;
}

/* Hover эффекты */
.price-block:hover .price--current {
  transform: scale(1.02);
}

.price-block:hover .price-discount {
  animation-play-state: paused;
  transform: scale(1.1);
}

/* Анимация изменения цены */
.price--current {
  position: relative;
}

.price--current::after {
  content: attr(data-change);
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 2px 6px;
  background: #00a854;
  color: #fff;
  border-radius: 4px;
  font-size: 10px;
  opacity: 0;
  pointer-events: none;
  transition: all 0.3s ease;
}

.price--current.price-up::after {
  content: '↑';
  background: #f91155;
  opacity: 1;
  animation: priceChange 1s ease;
}

.price--current.price-down::after {
  content: '↓';
  background: #00a854;
  opacity: 1;
  animation: priceChange 1s ease;
}

@keyframes priceChange {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
  }
  50% {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateX(-50%) translateY(-10px);
  }
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .price-block {
    gap: 6px;
  }
  
  [data-preset="SIZE_500"] .price--current {
    font-size: 18px;
    line-height: 24px;
  }
  
  .price--original {
    font-size: 12px;
    line-height: 18px;
  }
  
  .price-discount {
    font-size: 11px;
    padding: 1px 4px;
  }
}

/* Специальные эффекты для акций */
.price-block--sale-price.flash-sale .price-discount {
  background: linear-gradient(90deg, #f91155, #ff6b6b, #f91155);
  background-size: 200% 100%;
  animation: flashGradient 2s linear infinite;
}

@keyframes flashGradient {
  0% { background-position: 0% 50%; }
  100% { background-position: 200% 50%; }
}

/* Для Bundle цен */
.price-block.bundle-price::before {
  content: 'Комплект';
  position: absolute;
  top: -18px;
  left: 0;
  font-size: 10px;
  color: #70757a;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Цена с кешбэком */
.price-block.with-cashback .price-additional {
  color: #005bff;
  font-weight: 600;
}

.price-block.with-cashback .price-additional::before {
  content: '🪙';
  margin-right: 4px;
}
</style>