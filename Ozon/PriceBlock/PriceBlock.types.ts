/**
 * Типы для PriceBlock из структуры Ozon
 */

// Состояние цены из Ozon JSON
export interface PriceState {
  type: "priceV2"
  priceV2: PriceV2Data
}

export interface PriceV2Data {
  price: PriceItem[]
  discount?: string // "−64%", "−57%"
  priceStyle: PriceStyle
  preset: PricePreset
  paddingBottom: PaddingSize
}

// Элемент цены
export interface PriceItem {
  text: string // "400 ₽", "1 119 ₽"
  textStyle: "PRICE" | "ORIGINAL_PRICE"
}

// Стиль цены
export interface PriceStyle {
  styleType: PriceStyleType
  gradient?: {
    startColor: string // "#F1117E"
    endColor: string // "#F1117E"
  }
}

// Типы стилей из Ozon
export type PriceStyleType = 
  | "SALE_PRICE"     // Цена со скидкой (красная)
  | "CARD_PRICE"     // Обычная цена в карточке
  | "ACTUAL_PRICE"   // Актуальная цена (зеленая)

// Размеры пресетов
export type PricePreset = 
  | "SIZE_300"
  | "SIZE_400" 
  | "SIZE_500"

// Отступы
export type PaddingSize = 
  | "PADDING_200"
  | "PADDING_300"
  | "PADDING_400"

// Конфигурация компонента
export interface PriceBlockConfig {
  showDiscount?: boolean
  showOriginalPrice?: boolean
  animated?: boolean
  flashSale?: boolean
  bundlePrice?: boolean
  withCashback?: boolean
  cashbackAmount?: string
}

// Данные для анимации изменения цены
export interface PriceChangeData {
  oldPrice: number
  newPrice: number
  direction: 'up' | 'down'
  timestamp: number
}

// Форматирование цены
export interface PriceFormatter {
  currency?: string // "₽"
  locale?: string // "ru-RU"
  minimumFractionDigits?: number
  maximumFractionDigits?: number
}

// Константы стилей Ozon
export const PRICE_COLORS = {
  SALE: '#f91155',
  NORMAL: '#001a34',
  ACTUAL: '#00a854',
  ORIGINAL: '#9ca0a5',
  DISCOUNT_BG: '#f91155',
  CASHBACK: '#005bff'
} as const

// Размеры шрифтов по пресетам
export const FONT_SIZES = {
  SIZE_500: {
    current: '20px',
    lineHeight: '28px'
  },
  SIZE_400: {
    current: '18px',
    lineHeight: '24px'
  },
  SIZE_300: {
    current: '16px',
    lineHeight: '22px'
  },
  ORIGINAL: {
    size: '14px',
    lineHeight: '20px'
  },
  DISCOUNT: {
    size: '12px',
    lineHeight: '16px'
  }
} as const

// Отступы в пикселях
export const PADDINGS = {
  PADDING_200: 8,
  PADDING_300: 12,
  PADDING_400: 16
} as const