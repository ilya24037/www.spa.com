<template>
  <div 
    v-if="shouldRender"
    ref="controlContainer"
    :class="containerClasses"
    :style="containerStyle"
  >
    <!-- Skeleton loader во время инициализации -->
    <div 
      v-if="isLoading" 
      class="type-selector-skeleton"
      :class="`skeleton--${currentMode}`"
    >
      <div v-if="currentMode === 'buttons'" class="skeleton-button-group">
        <div 
          v-for="i in availableTypesCount" 
          :key="i" 
          class="skeleton-button"
        />
      </div>
      <div v-else class="skeleton-dropdown">
        <div class="skeleton-dropdown-button" />
      </div>
    </div>

    <!-- Основной контрол (невидим во время загрузки) -->
    <div 
      :style="{ visibility: isLoading ? 'hidden' : 'visible' }"
      class="type-selector-content"
    >
      <!-- Контрол создается и управляется JavaScript -->
    </div>

    <!-- Индикатор загрузки типов карт -->
    <div 
      v-if="isDetectingTypes"
      class="type-detection-indicator"
      :title="'Определение доступных типов карт...'"
    >
      <div class="detection-spinner" />
      <span class="detection-text">Загрузка...</span>
    </div>

    <!-- Оверлей для состояний disabled/error -->
    <div 
      v-if="hasOverlay"
      class="type-selector-overlay"
      :class="overlayClasses"
    >
      <div v-if="error" class="error-message">
        <span class="error-icon">⚠️</span>
        <span class="error-text">{{ error }}</span>
        <button 
          v-if="canRetry"
          @click="handleRetry"
          class="error-retry-button"
          type="button"
        >
          Повторить
        </button>
      </div>
      <div v-else-if="!enabled" class="disabled-message">
        <span class="disabled-icon">🔒</span>
        <span class="disabled-text">Недоступно</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Vue 3 компонент для TypeSelector
 * Composition API с полной TypeScript типизацией
 * Соответствует принципам CLAUDE.md
 * 
 * @component TypeSelectorVue
 * @version 1.0.0
 * @author SPA Platform
 */

import { 
  ref, 
  computed, 
  onMounted, 
  onBeforeUnmount, 
  watch,
  nextTick,
  type Ref,
  type ComputedRef
} from 'vue'
import TypeSelector from './TypeSelector.js'
import type { 
  TypeSelectorOptions, 
  TypeSelectorMode,
  TypeSelectorDirection,
  MapTypeConfig,
  TypeSelectorEventHandler,
  ExtendedTypeSelectorOptions
} from './TypeSelector.d.ts'

// Props с типизацией и значениями по умолчанию
interface Props {
  /** Экземпляр карты */
  map?: any
  /** Доступные типы карт */
  mapTypes?: MapTypeConfig[]
  /** Режим отображения */
  mode?: TypeSelectorMode
  /** Направление для кнопочного режима */
  direction?: TypeSelectorDirection
  /** Показывать названия типов */
  showLabels?: boolean
  /** Показывать иконки */
  showIcons?: boolean
  /** Позиция на карте */
  position?: string
  /** Видимость контрола */
  visible?: boolean
  /** Активность контрола */
  enabled?: boolean
  /** Текущий тип карты */
  currentType?: string | null
  /** Тип по умолчанию */
  defaultType?: string
  /** Автоопределение доступных типов */
  autoDetect?: boolean
  /** Кастомные типы */
  customTypes?: Record<string, MapTypeConfig>
  /** Компактный режим на мобильных */
  compactOnMobile?: boolean
  /** Отступы */
  margin?: { top?: number; right?: number; bottom?: number; left?: number }
  /** Z-index */
  zIndex?: number
  /** Дополнительные CSS классы */
  class?: string | string[] | Record<string, boolean>
  /** Inline стили */
  style?: string | Record<string, string | number>
  /** Колбэк валидации смены типа */
  validateTypeChange?: (oldType: string | null, newType: string) => boolean | Promise<boolean>
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'dropdown',
  direction: 'horizontal',
  showLabels: true,
  showIcons: true,
  position: 'topRight',
  visible: true,
  enabled: true,
  autoDetect: true,
  compactOnMobile: true,
  zIndex: 1000
})

// Emits с типизацией
interface Emits {
  /** Изменение текущего типа карты */
  'update:currentType': [type: string | null]
  /** Изменение доступных типов */
  'update:mapTypes': [types: MapTypeConfig[]]
  /** Изменение типа карты */
  'typechange': [event: { oldType: string | null; newType: string }]
  /** Добавление типа */
  'typeadd': [event: { type: MapTypeConfig }]
  /** Удаление типа */
  'typeremove': [event: { type: MapTypeConfig }]
  /** Открытие выпадающего списка */
  'dropdownopen': []
  /** Закрытие выпадающего списка */
  'dropdownclose': []
  /** Ошибка */
  'error': [error: Error]
  /** Готовность контрола */
  'ready': [control: TypeSelector]
  /** Событие валидации */
  'validate': [event: { oldType: string | null; newType: string; isValid: boolean }]
}

const emit = defineEmits<Emits>()

// Reactive состояние
const controlContainer: Ref<HTMLElement | null> = ref(null)
const typeSelectorInstance: Ref<TypeSelector | null> = ref(null)
const isLoading: Ref<boolean> = ref(true)
const isDetectingTypes: Ref<boolean> = ref(false)
const error: Ref<string | null> = ref(null)
const canRetry: Ref<boolean> = ref(false)
const internalCurrentType: Ref<string | null> = ref(props.currentType || null)
const availableTypes: Ref<MapTypeConfig[]> = ref(props.mapTypes || [])

// Computed свойства
const shouldRender: ComputedRef<boolean> = computed(() => {
  return props.visible !== false
})

const currentMode: ComputedRef<TypeSelectorMode> = computed(() => {
  // Определяем режим с учетом мобильной адаптации
  if (props.compactOnMobile && isMobileDevice.value) {
    return 'compact'
  }
  return props.mode || 'dropdown'
})

const isMobileDevice: ComputedRef<boolean> = computed(() => {
  // В реальном проекте можно использовать более сложную логику
  if (typeof window !== 'undefined') {
    return window.innerWidth <= 768
  }
  return false
})

const availableTypesCount: ComputedRef<number> = computed(() => {
  return Math.max(availableTypes.value.length, 3) // Минимум 3 для skeleton
})

const containerClasses: ComputedRef<string[]> = computed(() => {
  const classes = ['ymaps-type-selector-vue']
  
  if (currentMode.value) {
    classes.push(`ymaps-type-selector-vue--${currentMode.value}`)
  }
  
  if (props.direction) {
    classes.push(`ymaps-type-selector-vue--${props.direction}`)
  }
  
  if (!props.enabled) {
    classes.push('ymaps-type-selector-vue--disabled')
  }
  
  if (error.value) {
    classes.push('ymaps-type-selector-vue--error')
  }
  
  if (isLoading.value) {
    classes.push('ymaps-type-selector-vue--loading')
  }

  if (isDetectingTypes.value) {
    classes.push('ymaps-type-selector-vue--detecting')
  }

  // Добавляем пользовательские классы
  if (props.class) {
    if (typeof props.class === 'string') {
      classes.push(...props.class.split(' ').filter(Boolean))
    } else if (Array.isArray(props.class)) {
      classes.push(...props.class.filter(Boolean))
    } else {
      Object.entries(props.class).forEach(([className, shouldAdd]) => {
        if (shouldAdd && className) classes.push(className)
      })
    }
  }
  
  return classes
})

const containerStyle: ComputedRef<Record<string, string | number>> = computed(() => {
  const style: Record<string, string | number> = {}
  
  if (props.zIndex) {
    style.zIndex = props.zIndex
  }

  // Применяем пользовательские стили
  if (props.style) {
    if (typeof props.style === 'string') {
      // Парсим строковые стили (упрощенная версия)
      props.style.split(';').forEach(rule => {
        const [property, value] = rule.split(':').map(s => s.trim())
        if (property && value) {
          style[property] = value
        }
      })
    } else {
      Object.assign(style, props.style)
    }
  }
  
  return style
})

const hasOverlay: ComputedRef<boolean> = computed(() => {
  return !props.enabled || !!error.value
})

const overlayClasses: ComputedRef<string[]> = computed(() => {
  const classes = []
  
  if (!props.enabled) {
    classes.push('overlay--disabled')
  }
  
  if (error.value) {
    classes.push('overlay--error')
  }
  
  return classes
})

// Методы для работы с контролом
const createControl = async (): Promise<void> => {
  try {
    if (!controlContainer.value || typeSelectorInstance.value) {
      return
    }

    isLoading.value = true
    error.value = null
    canRetry.value = false

    // Определяем доступные типы если включено автоопределение
    if (props.autoDetect && props.map) {
      isDetectingTypes.value = true
      await detectAvailableTypes()
    }

    // Подготавливаем опции
    const options: ExtendedTypeSelectorOptions = {
      position: props.position,
      mode: currentMode.value,
      direction: props.direction,
      showLabels: props.showLabels,
      showIcons: props.showIcons,
      visible: props.visible,
      enabled: props.enabled,
      defaultType: props.defaultType || internalCurrentType.value || undefined,
      autoDetect: props.autoDetect,
      compactOnMobile: props.compactOnMobile,
      mapTypes: availableTypes.value.length > 0 ? availableTypes.value : undefined,
      customTypes: props.customTypes,
      zIndex: props.zIndex,
      margin: props.margin,
      validateTypeChange: props.validateTypeChange
    }

    // Создаем экземпляр контрола
    const control = new TypeSelector(options)
    
    // Настраиваем обработчики событий
    setupControlEventHandlers(control)
    
    // Если есть карта, добавляем контрол на неё
    if (props.map) {
      await control.addToMap(props.map)
    }
    
    // Устанавливаем начальный тип если указан
    if (internalCurrentType.value && control.getCurrentType() !== internalCurrentType.value) {
      try {
        await control.setCurrentType(internalCurrentType.value)
      } catch (err) {
        console.warn('TypeSelectorVue: не удалось установить начальный тип:', err)
      }
    }

    typeSelectorInstance.value = control
    isLoading.value = false
    isDetectingTypes.value = false
    
    // Синхронизируем доступные типы
    availableTypes.value = control.getAvailableTypes()
    emit('update:mapTypes', availableTypes.value)
    
    // Уведомляем о готовности
    emit('ready', control)
    
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Неизвестная ошибка'
    error.value = errorMessage
    canRetry.value = true
    isLoading.value = false
    isDetectingTypes.value = false
    emit('error', err instanceof Error ? err : new Error(errorMessage))
  }
}

const detectAvailableTypes = async (): Promise<void> => {
  // Имитация определения доступных типов карт
  // В реальном проекте здесь был бы запрос к API карты
  return new Promise((resolve) => {
    setTimeout(() => {
      // Если типы не переданы в props, используем стандартные
      if (!props.mapTypes || props.mapTypes.length === 0) {
        availableTypes.value = [
          { key: 'yandex#map', name: 'Схема', icon: 'map', title: 'Схематическая карта' },
          { key: 'yandex#satellite', name: 'Спутник', icon: 'satellite', title: 'Спутниковые снимки' },
          { key: 'yandex#hybrid', name: 'Гибрид', icon: 'hybrid', title: 'Гибридная карта' }
        ]
      }
      resolve()
    }, 300) // Имитация задержки
  })
}

const setupControlEventHandlers = (control: TypeSelector): void => {
  // Изменение типа карты
  const handleTypeChange: TypeSelectorEventHandler<'typechange'> = async (event) => {
    const { oldType, newType } = event

    // Валидация изменения если есть колбэк
    if (props.validateTypeChange) {
      try {
        const isValid = await props.validateTypeChange(oldType, newType)
        emit('validate', { oldType, newType, isValid })
        
        if (!isValid) {
          console.warn('TypeSelectorVue: изменение типа отклонено валидацией')
          return
        }
      } catch (validationError) {
        console.error('TypeSelectorVue: ошибка валидации:', validationError)
        emit('validate', { oldType, newType, isValid: false })
        return
      }
    }

    internalCurrentType.value = newType
    emit('update:currentType', newType)
    emit('typechange', { oldType, newType })
  }
  
  const handleTypeAdd: TypeSelectorEventHandler<'typeadd'> = (event) => {
    availableTypes.value = control.getAvailableTypes()
    emit('update:mapTypes', availableTypes.value)
    emit('typeadd', { type: event.type })
  }
  
  const handleTypeRemove: TypeSelectorEventHandler<'typeremove'> = (event) => {
    availableTypes.value = control.getAvailableTypes()
    emit('update:mapTypes', availableTypes.value)
    emit('typeremove', { type: event.type })
  }
  
  const handleDropdownOpen: TypeSelectorEventHandler<'dropdownopen'> = () => {
    emit('dropdownopen')
  }
  
  const handleDropdownClose: TypeSelectorEventHandler<'dropdownclose'> = () => {
    emit('dropdownclose')
  }

  // Привязываем обработчики
  control.on('typechange', handleTypeChange)
  control.on('typeadd', handleTypeAdd)
  control.on('typeremove', handleTypeRemove)
  control.on('dropdownopen', handleDropdownOpen)
  control.on('dropdownclose', handleDropdownClose)
}

const destroyControl = async (): Promise<void> => {
  if (typeSelectorInstance.value) {
    try {
      typeSelectorInstance.value.destroy()
    } catch (err) {
      console.error('TypeSelectorVue: ошибка уничтожения контрола:', err)
    } finally {
      typeSelectorInstance.value = null
    }
  }
}

const recreateControl = async (): Promise<void> => {
  await destroyControl()
  await nextTick()
  await createControl()
}

const handleRetry = async (): Promise<void> => {
  await recreateControl()
}

// Публичные методы компонента (через expose)
const getControl = (): TypeSelector | null => {
  return typeSelectorInstance.value
}

const getCurrentType = (): string | null => {
  return typeSelectorInstance.value?.getCurrentType() ?? internalCurrentType.value
}

const setCurrentType = async (type: string): Promise<void> => {
  if (typeSelectorInstance.value) {
    await typeSelectorInstance.value.setCurrentType(type)
  } else {
    internalCurrentType.value = type
  }
}

const getAvailableTypes = (): MapTypeConfig[] => {
  return typeSelectorInstance.value?.getAvailableTypes() ?? availableTypes.value
}

const addMapType = (typeConfig: MapTypeConfig, position?: number): void => {
  if (typeSelectorInstance.value) {
    typeSelectorInstance.value.addMapType(typeConfig, position)
  } else {
    // Добавляем в локальный массив если контрол еще не создан
    if (typeof position === 'number' && position >= 0 && position < availableTypes.value.length) {
      availableTypes.value.splice(position, 0, typeConfig)
    } else {
      availableTypes.value.push(typeConfig)
    }
  }
}

const removeMapType = (typeKey: string): void => {
  if (typeSelectorInstance.value) {
    typeSelectorInstance.value.removeMapType(typeKey)
  } else {
    // Удаляем из локального массива
    const index = availableTypes.value.findIndex(type => type.key === typeKey)
    if (index !== -1) {
      availableTypes.value.splice(index, 1)
    }
  }
}

// Expose методы для parent компонентов
defineExpose({
  getControl,
  getCurrentType,
  setCurrentType,
  getAvailableTypes,
  addMapType,
  removeMapType,
  recreate: recreateControl,
  retry: handleRetry
})

// Watchers для реактивных обновлений
watch(() => props.map, async (newMap, oldMap) => {
  if (newMap !== oldMap) {
    if (typeSelectorInstance.value) {
      if (oldMap) {
        await typeSelectorInstance.value.removeFromMap()
      }
      if (newMap) {
        await typeSelectorInstance.value.addToMap(newMap)
      }
    }
  }
}, { immediate: false })

watch(() => props.visible, (visible) => {
  if (typeSelectorInstance.value) {
    if (visible) {
      typeSelectorInstance.value.show()
    } else {
      typeSelectorInstance.value.hide()
    }
  }
})

watch(() => props.enabled, (enabled) => {
  if (typeSelectorInstance.value) {
    if (enabled) {
      typeSelectorInstance.value.enable()
    } else {
      typeSelectorInstance.value.disable()
    }
  }
})

watch(() => props.currentType, async (newType) => {
  if (newType !== internalCurrentType.value) {
    internalCurrentType.value = newType
    if (typeSelectorInstance.value && newType && newType !== typeSelectorInstance.value.getCurrentType()) {
      try {
        await typeSelectorInstance.value.setCurrentType(newType)
      } catch (err) {
        console.warn('TypeSelectorVue: не удалось обновить тип:', err)
      }
    }
  }
})

watch(() => props.mapTypes, (newTypes) => {
  if (newTypes && JSON.stringify(newTypes) !== JSON.stringify(availableTypes.value)) {
    availableTypes.value = [...newTypes]
    
    // Пересоздаем контрол если типы кардинально изменились
    if (typeSelectorInstance.value) {
      recreateControl()
    }
  }
}, { deep: true })

// Watchers для пересоздания контрола при изменении критических опций
watch([
  () => props.mode,
  () => props.position,
  () => props.direction,
  () => props.showLabels,
  () => props.showIcons,
  () => props.margin
], async () => {
  if (typeSelectorInstance.value) {
    await recreateControl()
  }
}, { deep: true })

// Lifecycle hooks
onMounted(async () => {
  await createControl()
})

onBeforeUnmount(async () => {
  await destroyControl()
})
</script>

<style scoped>
.ymaps-type-selector-vue {
  position: relative;
  display: inline-block;
  pointer-events: auto;
}

.ymaps-type-selector-vue--loading {
  pointer-events: none;
}

.ymaps-type-selector-vue--disabled {
  opacity: 0.6;
}

.ymaps-type-selector-vue--error {
  opacity: 0.8;
}

.ymaps-type-selector-vue--detecting {
  position: relative;
}

/* Skeleton loader стили */
.type-selector-skeleton {
  display: inline-block;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 3px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.skeleton-dropdown {
  width: 120px;
  height: 34px;
}

.skeleton-dropdown-button {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-button-group {
  display: flex;
  flex-direction: row;
  gap: 1px;
  background: #e0e0e0;
}

.skeleton--vertical .skeleton-button-group {
  flex-direction: column;
}

.skeleton-button {
  width: 80px;
  height: 34px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-button:nth-child(2) {
  animation-delay: 0.1s;
}

.skeleton-button:nth-child(3) {
  animation-delay: 0.2s;
}

.skeleton--compact .skeleton-dropdown,
.skeleton--compact .skeleton-button {
  width: 44px;
  height: 44px;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Индикатор определения типов */
.type-detection-indicator {
  position: absolute;
  top: -8px;
  right: -8px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: rgba(33, 150, 243, 0.9);
  color: white;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  z-index: 1;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.detection-spinner {
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.detection-text {
  white-space: nowrap;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Overlay стили */
.type-selector-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  border-radius: 3px;
  z-index: 1;
}

.overlay--disabled {
  cursor: not-allowed;
}

.overlay--error {
  background: rgba(255, 245, 245, 0.95);
}

.error-message,
.disabled-message {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  text-align: center;
  padding: 8px;
}

.error-message {
  flex-direction: column;
  color: #d32f2f;
}

.disabled-message {
  color: #666;
}

.error-icon,
.disabled-icon {
  font-size: 16px;
}

.error-text,
.disabled-text {
  font-weight: 500;
  margin-bottom: 4px;
}

.error-retry-button {
  padding: 4px 12px;
  font-size: 11px;
  background: #d32f2f;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.error-retry-button:hover {
  background: #b71c1c;
}

/* Режимы отображения */
.ymaps-type-selector-vue--dropdown {
  min-width: 120px;
}

.ymaps-type-selector-vue--compact {
  min-width: 44px;
}

.ymaps-type-selector-vue--buttons.ymaps-type-selector-vue--horizontal {
  display: inline-flex;
}

.ymaps-type-selector-vue--buttons.ymaps-type-selector-vue--vertical {
  display: inline-flex;
  flex-direction: column;
}

/* Адаптивность */
@media (max-width: 768px) {
  .type-selector-skeleton,
  .type-selector-overlay {
    min-height: 44px;
  }
  
  .skeleton-dropdown,
  .skeleton-button {
    min-height: 44px;
  }
  
  .error-message,
  .disabled-message {
    font-size: 11px;
    padding: 6px;
  }

  .type-detection-indicator {
    font-size: 10px;
    padding: 2px 6px;
  }

  .detection-spinner {
    width: 10px;
    height: 10px;
    border-width: 1.5px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .skeleton-dropdown-button,
  .skeleton-button,
  .detection-spinner {
    animation: none;
  }
  
  .skeleton-dropdown-button,
  .skeleton-button {
    background: #e0e0e0;
  }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .skeleton-dropdown-button,
  .skeleton-button {
    background: linear-gradient(90deg, #444 25%, #555 50%, #444 75%);
  }
  
  .type-selector-overlay {
    background: rgba(30, 30, 30, 0.9);
    color: #fff;
  }
  
  .overlay--error {
    background: rgba(60, 20, 20, 0.95);
  }
  
  .error-message {
    color: #f44336;
  }

  .disabled-message {
    color: #bbb;
  }
}

/* Состояния фокуса */
.ymaps-type-selector-vue:focus-within {
  outline: 2px solid #1976d2;
  outline-offset: 2px;
}
</style>