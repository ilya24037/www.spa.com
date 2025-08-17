/**
 * Composable для управления системой бейджей
 * Логика приоритетов, группировки и отображения
 */

import { computed, ref, reactive } from 'vue'
import type { 
  ProductBadge, 
  BadgeType, 
  BadgeConfig,
  BadgePosition 
} from './BadgeSystem.types'
import { 
  BADGE_PRESETS, 
  BADGE_CATEGORIES,
  BADGE_POSITIONS 
} from './BadgeSystem.types'

export function useBadgeSystem() {
  // Активные бейджи
  const badges = ref<BadgeConfig[]>([])
  
  // Настройки системы
  const settings = reactive({
    maxBadgesPerProduct: 3,
    enableAnimations: true,
    groupByCategory: false,
    sortByPriority: true
  })
  
  // Добавление бейджа
  const addBadge = (badge: BadgeConfig) => {
    // Проверяем, нет ли уже такого бейджа
    const exists = badges.value.some(b => b.type === badge.type)
    if (!exists) {
      badges.value.push(badge)
    }
  }
  
  // Удаление бейджа
  const removeBadge = (type: BadgeType) => {
    const index = badges.value.findIndex(b => b.type === type)
    if (index !== -1) {
      badges.value.splice(index, 1)
    }
  }
  
  // Создание бейджа из пресета
  const createBadgeFromPreset = (
    type: BadgeType, 
    overrides?: Partial<BadgeConfig>
  ): BadgeConfig => {
    const preset = BADGE_PRESETS[type]
    return {
      ...preset,
      ...overrides
    }
  }
  
  // Сортировка бейджей по приоритету
  const sortedBadges = computed(() => {
    if (!settings.sortByPriority) return badges.value
    
    return [...badges.value].sort((a, b) => {
      const priorityA = a.priority || 0
      const priorityB = b.priority || 0
      return priorityB - priorityA
    })
  })
  
  // Группировка бейджей по категориям
  const groupedBadges = computed(() => {
    if (!settings.groupByCategory) return { all: sortedBadges.value }
    
    const groups: Record<string, BadgeConfig[]> = {}
    
    for (const [category, types] of Object.entries(BADGE_CATEGORIES)) {
      const categoryBadges = sortedBadges.value.filter(badge => 
        types.includes(badge.type)
      )
      if (categoryBadges.length > 0) {
        groups[category] = categoryBadges
      }
    }
    
    return groups
  })
  
  // Получение видимых бейджей с учетом лимита
  const visibleBadges = computed(() => {
    const sorted = sortedBadges.value
    return sorted.slice(0, settings.maxBadgesPerProduct)
  })
  
  // Проверка наличия определенного типа бейджа
  const hasBadge = (type: BadgeType): boolean => {
    return badges.value.some(b => b.type === type)
  }
  
  // Получение бейджа по типу
  const getBadge = (type: BadgeType): BadgeConfig | undefined => {
    return badges.value.find(b => b.type === type)
  }
  
  // Массовое добавление бейджей
  const setBadges = (newBadges: BadgeConfig[]) => {
    badges.value = newBadges
  }
  
  // Очистка всех бейджей
  const clearBadges = () => {
    badges.value = []
  }
  
  // Определение позиции бейджа на карточке
  const getBadgePosition = (
    index: number, 
    total: number
  ): BadgePosition => {
    // Распределяем бейджи по углам карточки
    if (index === 0) return BADGE_POSITIONS.TOP_LEFT
    if (index === 1 && total > 2) return BADGE_POSITIONS.TOP_RIGHT
    if (index === 2 || (index === 1 && total === 2)) {
      return BADGE_POSITIONS.BOTTOM_LEFT
    }
    return BADGE_POSITIONS.BOTTOM_RIGHT
  }
  
  // Фильтрация бейджей по категории
  const filterByCategory = (category: keyof typeof BADGE_CATEGORIES) => {
    const types = BADGE_CATEGORIES[category]
    return badges.value.filter(badge => types.includes(badge.type))
  }
  
  // Обновление приоритета бейджа
  const updatePriority = (type: BadgeType, priority: number) => {
    const badge = badges.value.find(b => b.type === type)
    if (badge) {
      badge.priority = priority
    }
  }
  
  // Переключение анимации для бейджа
  const toggleAnimation = (type: BadgeType) => {
    const badge = badges.value.find(b => b.type === type)
    if (badge) {
      badge.animation = badge.animation === 'none' 
        ? BADGE_PRESETS[type].animation || 'pulse'
        : 'none'
    }
  }
  
  // Создание бейджа скидки с процентом
  const createDiscountBadge = (percent: number): BadgeConfig => {
    return createBadgeFromPreset(BadgeType.DISCOUNT, {
      text: `−${percent}%`
    })
  }
  
  // Создание бейджа доставки с датой
  const createDeliveryBadge = (date: Date): BadgeConfig => {
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)
    
    if (date.toDateString() === today.toDateString()) {
      return createBadgeFromPreset(BadgeType.TODAY)
    } else if (date.toDateString() === tomorrow.toDateString()) {
      return createBadgeFromPreset(BadgeType.TOMORROW)
    } else {
      return createBadgeFromPreset(BadgeType.EXPRESS, {
        text: `До ${date.getDate()}.${date.getMonth() + 1}`
      })
    }
  }
  
  // Анализ товара и автоматическое добавление бейджей
  const analyzePr
oduct = (product: any) => {
    const detectedBadges: BadgeConfig[] = []
    
    // Проверка скидки
    if (product.discount && product.discount > 20) {
      detectedBadges.push(createDiscountBadge(product.discount))
    }
    
    // Проверка новинки (товар добавлен в последние 7 дней)
    if (product.createdAt) {
      const daysSinceCreated = Math.floor(
        (Date.now() - new Date(product.createdAt).getTime()) / (1000 * 60 * 60 * 24)
      )
      if (daysSinceCreated <= 7) {
        detectedBadges.push(createBadgeFromPreset(BadgeType.NEW))
      }
    }
    
    // Проверка популярности
    if (product.salesCount > 1000) {
      detectedBadges.push(createBadgeFromPreset(BadgeType.BESTSELLER))
    } else if (product.salesCount > 500) {
      detectedBadges.push(createBadgeFromPreset(BadgeType.HIT))
    }
    
    // Проверка остатков
    if (product.stock && product.stock < 5) {
      detectedBadges.push(createBadgeFromPreset(BadgeType.LAST_ITEMS))
    }
    
    // Проверка рейтинга для "Выбор Ozon"
    if (product.rating >= 4.5 && product.reviewsCount >= 100) {
      detectedBadges.push(createBadgeFromPreset(BadgeType.OZON_CHOICE))
    }
    
    // Проверка бесплатной доставки
    if (product.freeDelivery || product.price >= 1000) {
      detectedBadges.push(createBadgeFromPreset(BadgeType.FREE_DELIVERY))
    }
    
    // Проверка кешбэка
    if (product.cashback && product.cashback > 0) {
      detectedBadges.push(
        createBadgeFromPreset(BadgeType.CASHBACK, {
          text: `${product.cashback}% кешбэк`
        })
      )
    }
    
    return detectedBadges
  }
  
  // Получение CSS классов для позиционирования
  const getPositionClasses = (position: BadgePosition): string => {
    const classes = []
    
    // Вертикальная позиция
    classes.push(`badge-position--${position.vertical}`)
    
    // Горизонтальная позиция
    classes.push(`badge-position--${position.horizontal}`)
    
    return classes.join(' ')
  }
  
  // Получение inline стилей для позиционирования
  const getPositionStyles = (position: BadgePosition): Record<string, string> => {
    const styles: Record<string, string> = {}
    
    // Вертикальная позиция
    if (position.vertical === 'top') {
      styles.top = `${position.offsetY || 8}px`
    } else {
      styles.bottom = `${position.offsetY || 8}px`
    }
    
    // Горизонтальная позиция
    if (position.horizontal === 'left') {
      styles.left = `${position.offsetX || 8}px`
    } else if (position.horizontal === 'right') {
      styles.right = `${position.offsetX || 8}px`
    } else {
      styles.left = '50%'
      styles.transform = 'translateX(-50%)'
    }
    
    return styles
  }
  
  return {
    // Состояние
    badges,
    settings,
    
    // Computed
    sortedBadges,
    groupedBadges,
    visibleBadges,
    
    // Методы
    addBadge,
    removeBadge,
    setBadges,
    clearBadges,
    hasBadge,
    getBadge,
    createBadgeFromPreset,
    getBadgePosition,
    filterByCategory,
    updatePriority,
    toggleAnimation,
    
    // Утилиты
    createDiscountBadge,
    createDeliveryBadge,
    analyzeProduct,
    getPositionClasses,
    getPositionStyles
  }
}

// Экспорт для использования в качестве глобального store
let globalBadgeSystem: ReturnType<typeof useBadgeSystem> | null = null

export function useGlobalBadgeSystem() {
  if (!globalBadgeSystem) {
    globalBadgeSystem = useBadgeSystem()
  }
  return globalBadgeSystem
}