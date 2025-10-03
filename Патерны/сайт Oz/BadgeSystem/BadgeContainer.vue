<template>
  <div class="badge-container" :class="containerClasses">
    <!-- Группа бейджей -->
    <TransitionGroup
      name="badge-transition"
      tag="div"
      class="badge-group"
      :class="`badge-group--${layout}`"
    >
      <ProductBadge
        v-for="(badge, index) in displayBadges"
        :key="badge.type"
        :type="badge.type"
        :text="badge.text"
        :icon="badge.icon"
        :theme="theme"
        :animation="enableAnimations ? badge.animation : 'none'"
        :icon-position="badge.iconPosition || 'left'"
        :custom-colors="badge.colors"
        :priority="badge.priority"
        :style="getBadgeStyles(index)"
        :class="getBadgeClasses(index)"
        @click="handleBadgeClick(badge)"
      />
    </TransitionGroup>
    
    <!-- Индикатор скрытых бейджей -->
    <div 
      v-if="hiddenCount > 0" 
      class="badge-more"
      @click="showAllBadges"
    >
      <span class="badge-more-text">+{{ hiddenCount }}</span>
      <Transition name="tooltip">
        <div v-if="showTooltip" class="badge-tooltip">
          <ProductBadge
            v-for="badge in hiddenBadges"
            :key="badge.type"
            :type="badge.type"
            :text="badge.text"
            :theme="'STYLE_TYPE_SMALL'"
            :animation="'none'"
          />
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import ProductBadge from './ProductBadge.vue'
import type { BadgeConfig, BadgeTheme } from './BadgeSystem.types'
import { BADGE_POSITIONS } from './BadgeSystem.types'

interface Props {
  badges: BadgeConfig[]
  maxVisible?: number
  layout?: 'stack' | 'row' | 'grid' | 'absolute'
  theme?: BadgeTheme
  enableAnimations?: boolean
  position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'overlay'
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 3,
  layout: 'stack',
  theme: 'STYLE_TYPE_MEDIUM',
  enableAnimations: true,
  position: 'top-left',
  compact: false
})

const emit = defineEmits<{
  'badge-click': [badge: BadgeConfig]
  'show-all': []
}>()

// Состояние
const showAll = ref(false)
const showTooltip = ref(false)

// Сортированные бейджи по приоритету
const sortedBadges = computed(() => {
  return [...props.badges].sort((a, b) => {
    const priorityA = a.priority || 0
    const priorityB = b.priority || 0
    return priorityB - priorityA
  })
})

// Видимые бейджи
const displayBadges = computed(() => {
  if (showAll.value) return sortedBadges.value
  return sortedBadges.value.slice(0, props.maxVisible)
})

// Скрытые бейджи
const hiddenBadges = computed(() => {
  if (showAll.value) return []
  return sortedBadges.value.slice(props.maxVisible)
})

// Количество скрытых
const hiddenCount = computed(() => hiddenBadges.value.length)

// Классы контейнера
const containerClasses = computed(() => {
  return [
    `badge-container--${props.position}`,
    `badge-container--${props.layout}`,
    {
      'badge-container--compact': props.compact,
      'badge-container--has-hidden': hiddenCount.value > 0
    }
  ]
})

// Стили для каждого бейджа в зависимости от layout
const getBadgeStyles = (index: number) => {
  const styles: Record<string, any> = {}
  
  if (props.layout === 'stack') {
    // Стековое расположение с небольшим смещением
    styles.zIndex = props.badges.length - index
    if (!props.compact) {
      styles.marginTop = index > 0 ? '-4px' : '0'
    }
  } else if (props.layout === 'absolute') {
    // Абсолютное позиционирование по углам
    const positions = [
      BADGE_POSITIONS.TOP_LEFT,
      BADGE_POSITIONS.TOP_RIGHT,
      BADGE_POSITIONS.BOTTOM_LEFT,
      BADGE_POSITIONS.BOTTOM_RIGHT
    ]
    const position = positions[index % 4]
    
    styles.position = 'absolute'
    styles[position.vertical] = `${position.offsetY}px`
    styles[position.horizontal] = `${position.offsetX}px`
  }
  
  return styles
}

// Классы для каждого бейджа
const getBadgeClasses = (index: number) => {
  const classes = []
  
  if (props.layout === 'grid') {
    classes.push(`badge-grid-item-${index + 1}`)
  }
  
  if (props.layout === 'stack' && index > 0) {
    classes.push('badge-stacked')
  }
  
  return classes
}

// Обработчики
const handleBadgeClick = (badge: BadgeConfig) => {
  emit('badge-click', badge)
}

const showAllBadges = () => {
  showAll.value = true
  emit('show-all')
}
</script>

<style scoped>
/* Контейнер бейджей */
.badge-container {
  position: relative;
  display: inline-flex;
  align-items: flex-start;
}

/* Группа бейджей */
.badge-group {
  display: flex;
  position: relative;
}

/* Layouts */

/* Stack layout - бейджи друг над другом */
.badge-group--stack {
  flex-direction: column;
  gap: 4px;
}

.badge-container--compact .badge-group--stack {
  gap: 2px;
}

.badge-group--stack .badge-stacked {
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Row layout - бейджи в ряд */
.badge-group--row {
  flex-direction: row;
  gap: 8px;
  flex-wrap: wrap;
}

.badge-container--compact .badge-group--row {
  gap: 4px;
}

/* Grid layout - сетка 2x2 */
.badge-group--grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 4px;
  max-width: 200px;
}

/* Absolute layout - по углам */
.badge-group--absolute {
  position: static;
}

.badge-group--absolute > * {
  position: absolute !important;
}

/* Позиционирование контейнера */
.badge-container--top-left {
  position: absolute;
  top: 8px;
  left: 8px;
}

.badge-container--top-right {
  position: absolute;
  top: 8px;
  right: 8px;
}

.badge-container--bottom-left {
  position: absolute;
  bottom: 8px;
  left: 8px;
}

.badge-container--bottom-right {
  position: absolute;
  bottom: 8px;
  right: 8px;
}

.badge-container--overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.badge-container--overlay .product-badge {
  pointer-events: auto;
}

/* Индикатор скрытых бейджей */
.badge-more {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  padding: 2px 6px;
  margin-left: 4px;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.badge-more:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: scale(1.1);
}

.badge-more:hover .badge-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

/* Tooltip со скрытыми бейджами */
.badge-tooltip {
  position: absolute;
  top: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%) translateY(-4px);
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px;
  background: white;
  border: 1px solid #e1e3e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
  z-index: 1000;
  min-width: 120px;
}

/* Анимации */
.badge-transition-enter-active,
.badge-transition-leave-active {
  transition: all 0.3s ease;
}

.badge-transition-enter-from {
  opacity: 0;
  transform: scale(0.8) translateY(-4px);
}

.badge-transition-leave-to {
  opacity: 0;
  transform: scale(0.8) translateY(4px);
}

.badge-transition-move {
  transition: transform 0.3s ease;
}

/* Анимация tooltip */
.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px);
}

/* Компактный режим */
.badge-container--compact .product-badge {
  transform: scale(0.9);
  transform-origin: top left;
}

.badge-container--compact .badge-more {
  min-width: 20px;
  height: 20px;
  font-size: 10px;
  padding: 1px 4px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .badge-group--grid {
    grid-template-columns: 1fr;
    max-width: 150px;
  }
  
  .badge-container--top-left,
  .badge-container--top-right,
  .badge-container--bottom-left,
  .badge-container--bottom-right {
    position: static;
    margin: 4px;
  }
  
  .badge-tooltip {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
}

/* Режим высокой контрастности */
@media (prefers-contrast: high) {
  .badge-more {
    border: 1px solid white;
  }
  
  .badge-tooltip {
    border-width: 2px;
  }
}
</style>