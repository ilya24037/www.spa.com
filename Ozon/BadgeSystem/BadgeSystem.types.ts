/**
 * Типы для BadgeSystem из структуры Ozon
 * Основано на анализе бейджей в их карточках товаров
 */

// Основной интерфейс бейджа
export interface ProductBadge {
  text: string
  image?: string
  tintColor?: string
  iconTintColor?: string
  backgroundColor: string
  testInfo?: {
    automatizationId: string
  }
  theme?: BadgeTheme
  iconPosition?: IconPosition
  priority?: number
  animation?: BadgeAnimation
}

// Темы бейджей
export type BadgeTheme = 
  | "STYLE_TYPE_SMALL"
  | "STYLE_TYPE_MEDIUM" 
  | "STYLE_TYPE_LARGE"
  | "STYLE_TYPE_COMPACT"

// Позиция иконки
export type IconPosition = 
  | "ICON_POSITION_LEFT"
  | "ICON_POSITION_RIGHT"
  | "ICON_POSITION_TOP"
  | "ICON_POSITION_BOTTOM"

// Анимации бейджей
export type BadgeAnimation = 
  | "pulse"
  | "shake"
  | "bounce"
  | "flash"
  | "none"

// Типы бейджей в Ozon
export enum BadgeType {
  // Маркетинговые
  SALE = "sale",
  HOT = "hot",
  NEW = "new",
  HIT = "hit",
  BESTSELLER = "bestseller",
  
  // Ценовые
  PRICE_INDEX = "price_index",
  DISCOUNT = "discount",
  CASHBACK = "cashback",
  
  // Доставка
  EXPRESS = "express",
  TODAY = "today",
  TOMORROW = "tomorrow",
  FREE_DELIVERY = "free_delivery",
  
  // Качество
  PREMIUM = "premium",
  ORIGINAL = "original",
  VERIFIED = "verified",
  ECO = "eco",
  
  // Статусы
  LAST_ITEMS = "last_items",
  PREORDER = "preorder",
  EXCLUSIVE = "exclusive",
  LIMITED = "limited",
  
  // Ozon специфичные
  OZON_CHOICE = "ozon_choice",
  OZON_BANK = "ozon_bank",
  OZON_FRESH = "ozon_fresh",
  OZON_EXPRESS = "ozon_express"
}

// Конфигурация бейджа
export interface BadgeConfig {
  type: BadgeType
  text: string
  icon?: string
  colors: BadgeColors
  position?: BadgePosition
  size?: BadgeSize
  animation?: BadgeAnimation
  priority?: number
  duration?: number // для временных бейджей
}

// Цвета бейджа
export interface BadgeColors {
  background: string
  text: string
  icon?: string
  border?: string
  gradient?: {
    startColor: string
    endColor: string
    angle?: number
  }
}

// Позиционирование бейджа на карточке
export interface BadgePosition {
  vertical: "top" | "bottom"
  horizontal: "left" | "right" | "center"
  offsetX?: number
  offsetY?: number
}

// Размеры бейджей
export interface BadgeSize {
  height: number
  minWidth?: number
  maxWidth?: number
  padding: {
    horizontal: number
    vertical: number
  }
  fontSize: number
  iconSize?: number
}

// Предустановленные конфигурации бейджей Ozon
export const BADGE_PRESETS: Record<BadgeType, BadgeConfig> = {
  [BadgeType.SALE]: {
    type: BadgeType.SALE,
    text: "Распродажа",
    icon: "🔥",
    colors: {
      background: "#f1117e",
      text: "#ffffff",
      gradient: {
        startColor: "#f1117e",
        endColor: "#ff4488",
        angle: 45
      }
    },
    animation: "pulse",
    priority: 10
  },
  
  [BadgeType.HOT]: {
    type: BadgeType.HOT,
    text: "Хит",
    icon: "🔥",
    colors: {
      background: "#ff4444",
      text: "#ffffff",
      icon: "#ffcc00"
    },
    animation: "flash",
    priority: 9
  },
  
  [BadgeType.NEW]: {
    type: BadgeType.NEW,
    text: "Новинка",
    icon: "✨",
    colors: {
      background: "#00a854",
      text: "#ffffff"
    },
    priority: 8
  },
  
  [BadgeType.PRICE_INDEX]: {
    type: BadgeType.PRICE_INDEX,
    text: "Цена что надо",
    icon: "👍",
    colors: {
      background: "#4caf50",
      text: "#ffffff"
    },
    priority: 7
  },
  
  [BadgeType.EXPRESS]: {
    type: BadgeType.EXPRESS,
    text: "Экспресс",
    icon: "⚡",
    colors: {
      background: "#005bff",
      text: "#ffffff"
    },
    priority: 6
  },
  
  [BadgeType.TODAY]: {
    type: BadgeType.TODAY,
    text: "Сегодня",
    icon: "📦",
    colors: {
      background: "#00875a",
      text: "#ffffff"
    },
    priority: 5
  },
  
  [BadgeType.PREMIUM]: {
    type: BadgeType.PREMIUM,
    text: "Premium",
    icon: "⭐",
    colors: {
      background: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
      text: "#ffffff",
      icon: "#ffd700"
    },
    priority: 8
  },
  
  [BadgeType.OZON_CHOICE]: {
    type: BadgeType.OZON_CHOICE,
    text: "Выбор Ozon",
    icon: "O",
    colors: {
      background: "#005bff",
      text: "#ffffff"
    },
    priority: 9
  },
  
  [BadgeType.DISCOUNT]: {
    type: BadgeType.DISCOUNT,
    text: "-50%",
    colors: {
      background: "#f91155",
      text: "#ffffff"
    },
    animation: "pulse",
    priority: 10
  },
  
  [BadgeType.CASHBACK]: {
    type: BadgeType.CASHBACK,
    text: "Кешбэк",
    icon: "🪙",
    colors: {
      background: "#ff9800",
      text: "#ffffff"
    },
    priority: 6
  },
  
  [BadgeType.FREE_DELIVERY]: {
    type: BadgeType.FREE_DELIVERY,
    text: "Бесплатная доставка",
    icon: "🚚",
    colors: {
      background: "#2196f3",
      text: "#ffffff"
    },
    priority: 4
  },
  
  [BadgeType.BESTSELLER]: {
    type: BadgeType.BESTSELLER,
    text: "Бестселлер",
    icon: "🏆",
    colors: {
      background: "#ffc107",
      text: "#000000"
    },
    priority: 8
  },
  
  [BadgeType.HIT]: {
    type: BadgeType.HIT,
    text: "Хит продаж",
    icon: "💥",
    colors: {
      background: "#e91e63",
      text: "#ffffff"
    },
    animation: "bounce",
    priority: 9
  },
  
  [BadgeType.LAST_ITEMS]: {
    type: BadgeType.LAST_ITEMS,
    text: "Последние",
    icon: "⏰",
    colors: {
      background: "#ff5722",
      text: "#ffffff"
    },
    animation: "shake",
    priority: 7
  },
  
  [BadgeType.PREORDER]: {
    type: BadgeType.PREORDER,
    text: "Предзаказ",
    icon: "📅",
    colors: {
      background: "#9c27b0",
      text: "#ffffff"
    },
    priority: 5
  },
  
  [BadgeType.EXCLUSIVE]: {
    type: BadgeType.EXCLUSIVE,
    text: "Эксклюзив",
    icon: "💎",
    colors: {
      background: "#3f51b5",
      text: "#ffffff"
    },
    priority: 8
  },
  
  [BadgeType.LIMITED]: {
    type: BadgeType.LIMITED,
    text: "Limited",
    icon: "🎯",
    colors: {
      background: "#424242",
      text: "#ffffff"
    },
    priority: 7
  },
  
  [BadgeType.ORIGINAL]: {
    type: BadgeType.ORIGINAL,
    text: "Оригинал",
    icon: "✓",
    colors: {
      background: "#00bcd4",
      text: "#ffffff"
    },
    priority: 6
  },
  
  [BadgeType.VERIFIED]: {
    type: BadgeType.VERIFIED,
    text: "Проверено",
    icon: "✓",
    colors: {
      background: "#4caf50",
      text: "#ffffff"
    },
    priority: 5
  },
  
  [BadgeType.ECO]: {
    type: BadgeType.ECO,
    text: "Эко",
    icon: "🌿",
    colors: {
      background: "#8bc34a",
      text: "#ffffff"
    },
    priority: 4
  },
  
  [BadgeType.TOMORROW]: {
    type: BadgeType.TOMORROW,
    text: "Завтра",
    icon: "📦",
    colors: {
      background: "#03a9f4",
      text: "#ffffff"
    },
    priority: 4
  },
  
  [BadgeType.OZON_BANK]: {
    type: BadgeType.OZON_BANK,
    text: "Ozon Карта",
    icon: "💳",
    colors: {
      background: "#7c4dff",
      text: "#ffffff"
    },
    priority: 5
  },
  
  [BadgeType.OZON_FRESH]: {
    type: BadgeType.OZON_FRESH,
    text: "Ozon fresh",
    icon: "🥬",
    colors: {
      background: "#00e676",
      text: "#ffffff"
    },
    priority: 6
  },
  
  [BadgeType.OZON_EXPRESS]: {
    type: BadgeType.OZON_EXPRESS,
    text: "Ozon Express",
    icon: "⚡",
    colors: {
      background: "#005bff",
      text: "#ffffff"
    },
    priority: 7
  }
}

// Размеры бейджей по умолчанию
export const BADGE_SIZES: Record<BadgeTheme, BadgeSize> = {
  STYLE_TYPE_SMALL: {
    height: 20,
    padding: { horizontal: 6, vertical: 2 },
    fontSize: 10,
    iconSize: 12
  },
  STYLE_TYPE_MEDIUM: {
    height: 24,
    padding: { horizontal: 8, vertical: 4 },
    fontSize: 12,
    iconSize: 14
  },
  STYLE_TYPE_LARGE: {
    height: 32,
    padding: { horizontal: 12, vertical: 6 },
    fontSize: 14,
    iconSize: 16
  },
  STYLE_TYPE_COMPACT: {
    height: 18,
    padding: { horizontal: 4, vertical: 1 },
    fontSize: 10,
    iconSize: 10
  }
}

// Позиции бейджей на карточке товара
export const BADGE_POSITIONS = {
  TOP_LEFT: { vertical: "top", horizontal: "left", offsetX: 8, offsetY: 8 },
  TOP_RIGHT: { vertical: "top", horizontal: "right", offsetX: 8, offsetY: 8 },
  BOTTOM_LEFT: { vertical: "bottom", horizontal: "left", offsetX: 8, offsetY: 8 },
  BOTTOM_RIGHT: { vertical: "bottom", horizontal: "right", offsetX: 8, offsetY: 8 }
} as const

// Группировка бейджей по категориям
export const BADGE_CATEGORIES = {
  marketing: [BadgeType.SALE, BadgeType.HOT, BadgeType.NEW, BadgeType.HIT, BadgeType.BESTSELLER],
  price: [BadgeType.PRICE_INDEX, BadgeType.DISCOUNT, BadgeType.CASHBACK],
  delivery: [BadgeType.EXPRESS, BadgeType.TODAY, BadgeType.TOMORROW, BadgeType.FREE_DELIVERY],
  quality: [BadgeType.PREMIUM, BadgeType.ORIGINAL, BadgeType.VERIFIED, BadgeType.ECO],
  status: [BadgeType.LAST_ITEMS, BadgeType.PREORDER, BadgeType.EXCLUSIVE, BadgeType.LIMITED],
  ozon: [BadgeType.OZON_CHOICE, BadgeType.OZON_BANK, BadgeType.OZON_FRESH, BadgeType.OZON_EXPRESS]
} as const