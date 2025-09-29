<template>
  <div class="tooltip-wrapper" ref="triggerRef">
    <!-- Триггер для показа подсказки -->
    <div 
      class="tooltip-trigger"
      @mouseenter="showTooltip"
      @mouseleave="hideTooltip"
      @click="handleClick"
    >
      <slot name="trigger">
        <span class="tooltip-default-trigger">
          {{ text }}
          <InfoIcon v-if="showIcon" :size="16" />
        </span>
      </slot>
    </div>

    <!-- Подсказка: простой режим (без Teleport, абсолютное позиционирование рядом с иконкой) -->
    <Transition name="tooltip">
      <div
        v-if="isVisible && simple"
        ref="tooltipRef"
        class="tooltip-content tooltip-simple"
        :class="`tooltip-${actualPlacement}`"
        :style="simpleStyles"
        @mouseenter="keepTooltipOpen"
        @mouseleave="hideTooltip"
      >
        <div class="tooltip-body">
          <slot name="content">
            {{ content }}
          </slot>
        </div>
        <button
          v-if="trigger === 'click'"
          @click="closeTooltip"
          type="button"
          class="tooltip-close"
          aria-label="Закрыть"
        >
          <svg width="16"
               height="16"
               viewBox="0 0 16 16"
               fill="none"
               xmlns="http://www.w3.org/2000/svg">
            <path d="M12 4L4 12M4 4l8 8"
                  stroke="currentColor"
                  stroke-width="1.5"
                  stroke-linecap="round"/>
          </svg>
        </button>
      </div>
    </Transition>

    <!-- Подсказка: продвинутый режим (через Teleport к body, фиксированное позиционирование) -->
    <Teleport to="body">
      <Transition name="tooltip">
        <div
          v-if="isVisible && !simple"
          ref="tooltipRef"
          class="tooltip-content tooltip-fixed"
          :style="tooltipStyles"
          @mouseenter="keepTooltipOpen"
          @mouseleave="hideTooltip"
        >
          <div class="tooltip-arrow" :class="`tooltip-arrow--${actualPlacement}`" :style="arrowStyles">
            <svg width="16" height="7" xmlns="http://www.w3.org/2000/svg">
              <path fill="currentColor" d="m0 0 5.33 5.313c.935.932 1.402 1.397 1.94 1.572a2.38 2.38 0 0 0 1.46 0c.538-.175 1.005-.64 1.94-1.572L16 0H0Z"/>
            </svg>
          </div>
          <div class="tooltip-body">
            <slot name="content">
              {{ content }}
            </slot>
          </div>
          <button v-if="trigger === 'click'"
                  @click="closeTooltip"
                  type="button"
                  class="tooltip-close"
                  aria-label="Закрыть">
            <svg width="16"
                 height="16"
                 viewBox="0 0 16 16"
                 fill="none"
                 xmlns="http://www.w3.org/2000/svg">
              <path d="M12 4L4 12M4 4l8 8"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import InfoIcon from '@/src/shared/ui/atoms/InfoIcon/InfoIcon.vue'

interface Props {
  text?: string
  content?: string
  placement?: 'top' | 'bottom' | 'left' | 'right' | 'auto'
  trigger?: 'hover' | 'click'
  showIcon?: boolean
  delay?: number
  offset?: number
  // Простой режим без Teleport (подсказка как сосед триггера)
  simple?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  text: '',
  content: '',
  placement: 'right',
  trigger: 'hover',
  showIcon: true,
  delay: 200,
  offset: 12,
  // По умолчанию используем фиксированное позиционирование через Teleport,
  // чтобы не было обрезаний соседними секциями
  simple: false
})

const emit = defineEmits<{
  show: []
  hide: []
}>()

const isVisible = ref(false)
const triggerRef = ref<HTMLElement>()
const tooltipRef = ref<HTMLElement>()
const tooltipStyles = ref<any>({})
const simpleStyles = ref<any>({})
const arrowStyles = ref<any>({})
const actualPlacement = ref(props.placement)
const hoverTimeout = ref<number>()
const intersectionObserver = ref<IntersectionObserver>()
const scrollTimeout = ref<number>()
// Упрощенная логика: без бесконечного rAF

const showTooltip = async () => {
  if (props.trigger !== 'hover') return
  
  clearTimeout(hoverTimeout.value)
  hoverTimeout.value = window.setTimeout(async () => {
    isVisible.value = true
    emit('show')
    await nextTick()
    updatePosition()
  }, props.delay)
}

const hideTooltip = () => {
  if (props.trigger !== 'hover') return
  
  clearTimeout(hoverTimeout.value)
  hoverTimeout.value = window.setTimeout(() => {
    isVisible.value = false
    emit('hide')
  }, 100)
}

const keepTooltipOpen = () => {
  if (props.trigger !== 'hover') return
  clearTimeout(hoverTimeout.value)
}

const handleClick = async () => {
  if (props.trigger !== 'click') return
  
  isVisible.value = !isVisible.value
  
  if (isVisible.value) {
    emit('show')
    await nextTick()
    updatePosition()
  } else {
    emit('hide')
    // no-op
  }
}

const closeTooltip = () => {
  isVisible.value = false
  emit('hide')
  // no-op
}

const updatePosition = () => {
  if (!triggerRef.value || !tooltipRef.value) return
  
  // Получаем точный элемент триггера (может быть кнопка внутри wrapper)
  const triggerElement = triggerRef.value.querySelector('[type="button"]') || triggerRef.value
  const triggerRect = triggerElement.getBoundingClientRect()
  const tooltipRect = tooltipRef.value.getBoundingClientRect()
  const windowWidth = window.innerWidth
  const windowHeight = window.innerHeight
  
  // Учитываем скролл страницы для точного позиционирования
  const scrollX = window.pageXOffset || document.documentElement.scrollLeft
  const scrollY = window.pageYOffset || document.documentElement.scrollTop
  
  let placement = props.placement
  
  // Auto placement
  if (placement === 'auto') {
    const spaceTop = triggerRect.top
    const spaceBottom = windowHeight - triggerRect.bottom
    const spaceLeft = triggerRect.left
    const spaceRight = windowWidth - triggerRect.right
    
    if (spaceRight >= tooltipRect.width + props.offset) {
      placement = 'right'
    } else if (spaceBottom >= tooltipRect.height + props.offset) {
      placement = 'bottom'
    } else if (spaceTop >= tooltipRect.height + props.offset) {
      placement = 'top'
    } else {
      placement = 'left'
    }
  }
  
  actualPlacement.value = placement
  
  // Calculate position
  let top = 0
  let left = 0
  let arrowLeft = '50%'
  let arrowTop = '50%'
  
  switch (placement) {
    case 'bottom':
      top = triggerRect.bottom + props.offset
      left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2
      arrowLeft = '50%'
      arrowTop = '-7px'
      break
    case 'top':
      top = triggerRect.top - tooltipRect.height - props.offset
      left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2
      arrowLeft = '50%'
      arrowTop = 'calc(100% - 1px)'
      break
    case 'left':
      top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2
      left = triggerRect.left - tooltipRect.width - props.offset
      arrowLeft = 'calc(100% - 1px)'
      arrowTop = '50%'
      break
    case 'right':
      // Для правого расположения центрируем по вертикали относительно иконки
      const centerY = triggerRect.top + triggerRect.height / 2
      top = centerY - tooltipRect.height / 2
      left = triggerRect.right + props.offset
      
      // Позиционируем стрелку по центру видимой области подсказки
      const visibleTop = Math.max(10, top)
      const visibleBottom = Math.min(windowHeight - 10, top + tooltipRect.height)
      const arrowOffset = centerY - visibleTop
      
      arrowLeft = '-7px'
      arrowTop = `${Math.min(Math.max(20, arrowOffset), tooltipRect.height - 20)}px`
      break
  }
  
  // Prevent overflow: ограничиваем только ось, ортогональную направлению
  if (placement === 'right' || placement === 'left') {
    // Ограничиваем только по горизонтали, чтобы подсказка не выходила за экран
    left = Math.max(10, Math.min(left, windowWidth - tooltipRect.width - 10))
  } else {
    // Для top/bottom ограничиваем только по вертикали
    top = Math.max(10, Math.min(top, windowHeight - tooltipRect.height - 10))
  }
  
  // Для правого и левого размещения не ограничиваем горизонтально
  // Для правого/левого оставляем неизменным top, чтобы не отрывать от центра иконки
  
  if (props.simple) {
    // Абсолютное позиционирование относительно ближайшего позиционированного родителя
    // Пересчитываем координаты из viewport в локальные относительно wrapper
    const wrapperRect = (triggerRef.value as HTMLElement).getBoundingClientRect()
    const localTop = top - wrapperRect.top
    const localLeft = left - wrapperRect.left
    simpleStyles.value = {
      position: 'absolute',
      top: `${localTop}px`,
      left: `${localLeft}px`,
      zIndex: 10
    }
  } else {
    tooltipStyles.value = {
      position: 'fixed',
      top: `${top}px`,
      left: `${left}px`,
      // максимально высокий z-index чтобы перекрывать любые секции/оверлеи
      zIndex: 999999,
      pointerEvents: 'auto'
    }
  }
  
  arrowStyles.value = {
    left: arrowLeft,
    top: arrowTop,
    transform: placement === 'right' || placement === 'left' ? 'translateY(-50%)' : 'translateX(-50%)'
  }
}

const handleClickOutside = (event: MouseEvent) => {
  if (props.trigger !== 'click') return
  
  if (
    triggerRef.value &&
    !triggerRef.value.contains(event.target as Node) &&
    tooltipRef.value &&
    !tooltipRef.value.contains(event.target as Node)
  ) {
    isVisible.value = false
    emit('hide')
  }
}

const handleScroll = () => {
  if (!isVisible.value) return
  
  // Немедленно обновляем позицию для первого вызова
  updatePosition()
  
  // Throttling для последующих вызовов
  clearTimeout(scrollTimeout.value)
  scrollTimeout.value = window.setTimeout(() => {
    if (isVisible.value) {
      updatePosition()
    }
  }, 16) // ~60fps
}

// rAF удален ради простоты. Позиция обновляется при открытии, scroll и resize

const setupIntersectionObserver = () => {
  if (!triggerRef.value) return
  
  intersectionObserver.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && isVisible.value) {
          updatePosition()
        }
      })
    },
    {
      threshold: [0, 0.1, 0.5, 1],
      rootMargin: '50px'
    }
  )
  
  intersectionObserver.value.observe(triggerRef.value)
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // Используем passive: false для лучшей производительности
  window.addEventListener('scroll', handleScroll, { passive: true, capture: true })
  window.addEventListener('resize', handleScroll, { passive: true })
  // Добавляем обработчик для скролла внутри контейнеров
  document.addEventListener('scroll', handleScroll, { passive: true, capture: true })
  
  // Настраиваем IntersectionObserver для более точного отслеживания
  nextTick(() => {
    setupIntersectionObserver()
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll, { capture: true })
  window.removeEventListener('resize', handleScroll)
  document.removeEventListener('scroll', handleScroll, { capture: true })
  
  // Очищаем IntersectionObserver
  if (intersectionObserver.value) {
    intersectionObserver.value.disconnect()
  }
  
  clearTimeout(hoverTimeout.value)
  clearTimeout(scrollTimeout.value)
  // no-op
})
</script>

<style scoped>
.tooltip-wrapper {
  display: inline-flex;
  position: relative;
  align-items: center;
  vertical-align: middle;
}

.tooltip-trigger {
  display: inline-flex;
  align-items: center;
  cursor: help;
  line-height: 1;
}

.tooltip-default-trigger {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #001a34;
  font-size: 14px;
}

.tooltip-content {
  background-color: #1a1a1a;
  color: white;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  line-height: 1.5;
  max-width: 286px;
  min-width: 100px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  pointer-events: auto;
  z-index: 2147483647; /* поверх всего */
}

.tooltip-fixed { position: fixed; will-change: transform; }
.tooltip-simple { position: absolute; }

.tooltip-body {
  position: relative;
  z-index: 1;
}

.tooltip-arrow {
  position: absolute;
  color: #1a1a1a;
  pointer-events: none;
}

/* Вспомогательные стили для случаев, когда в слот кладут картинку */
.tooltip-image img {
  display: block;
  width: 240px;
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

.tooltip-arrow--bottom {
  transform: translateX(-50%) rotate(180deg);
}

.tooltip-arrow--top {
  transform: translateX(-50%);
}

.tooltip-arrow--left {
  transform: translateY(-50%) rotate(90deg);
}

.tooltip-arrow--right {
  transform: translateY(-50%) rotate(-90deg);
}

/* Кнопка закрытия */
.tooltip-close {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
  padding: 0;
  z-index: 2;
}

.tooltip-close:hover {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
}

/* Анимация появления/исчезновения */
.tooltip-enter-active,
.tooltip-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.tooltip-enter-from {
  opacity: 0;
  transform: translateY(-4px);
}

.tooltip-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>