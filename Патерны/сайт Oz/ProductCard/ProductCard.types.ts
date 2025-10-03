/**
 * Типы для ProductCard из структуры Ozon
 * Точное соответствие JSON структуре
 */

// Основная структура товара из Ozon
export interface Product {
  skuId: string
  action: ProductAction
  favButton: FavoriteButton
  images: ProductImage[]
  isAdult: boolean
  leftBottomBadge?: ProductBadge
  shouldBlur: boolean
  state: ProductState[]
  trackingInfo: ProductTrackingInfo
}

// Action товара
export interface ProductAction {
  behavior: "BEHAVIOR_TYPE_REDIRECT"
  link: string
}

// Кнопка избранного
export interface FavoriteButton {
  id: string
  isFav: boolean
  favLink: string // "favoriteBatchAddItems"
  unfavLink: string // "favoriteBatchDeleteItems"
  trackingInfo?: {
    click: TrackingAction
  }
  testInfo?: {
    favoriteButton?: {
      automatizationId: string // "favorite-simple-button"
    }
    unFavoriteButton?: {
      automatizationId: string // "unfavorite-simple-button"
    }
  }
}

// Изображение товара
export interface ProductImage {
  link: string
  contentMode: "SCALE_ASPECT_FIT" | "SCALE_ASPECT_FILL"
}

// Бейдж товара
export interface ProductBadge {
  text: string // "Распродажа", "Цена что надо"
  image?: string // "ic_s_hot_filled_compact", "ic_s_like_filled_compact"
  tintColor?: string // "#ffffffff"
  iconTintColor?: string // "#ffffffff", "graphicLightKey"
  backgroundColor: string // "#f1117eff", "bgPositivePrimary"
  testInfo?: {
    automatizationId: string // "badge-marketingSuperHigh", "badge-priceIndex"
  }
  theme?: "STYLE_TYPE_MEDIUM"
  iconPosition?: "ICON_POSITION_LEFT"
}

// Полиморфные состояния товара
export type ProductState = PriceState | TextState | LabelListState

// Состояние цены
export interface PriceState {
  type: "priceV2"
  priceV2: {
    price: Array<{
      text: string
      textStyle: "PRICE" | "ORIGINAL_PRICE"
    }>
    discount?: string // "−64%"
    priceStyle: {
      styleType: "SALE_PRICE" | "CARD_PRICE" | "ACTUAL_PRICE"
      gradient?: {
        startColor: string // "#F1117E"
        endColor: string // "#F1117E"
      }
    }
    preset: string // "SIZE_500"
    paddingBottom: string // "PADDING_200"
  }
}

// Текстовое состояние (название товара)
export interface TextState {
  type: "textAtom"
  textAtom: {
    text: string
    textStyle: string // "tsBodyL"
    maxLines: number // 2
    testInfo?: {
      automatizationId: string // "tile-name"
    }
  }
  id?: string // "name"
}

// Список меток (рейтинг и отзывы)
export interface LabelListState {
  type: "labelList"
  labelList: {
    items: LabelItem[]
    textStyle: string // "tsBodyMBold"
    maxLines: number // 1
    align: "ALIGN_LEFT"
    testInfo?: {
      automatizationId: string // "tile-list-rating"
    }
  }
}

// Элемент метки
export interface LabelItem {
  icon?: {
    image: string // "ic_s_star_filled_compact", "ic_s_dialog_filled_compact"
    tintColor: string // "graphicRating", "graphicTertiary", "graphicOzon"
  }
  title: string // "4.9", "68 392 отзыва", "Ozon"
  titleColor: string // "textPremium", "textSecondary", "textOzon"
  testInfo?: {
    automatizationId: string // "tile-list-rating", "tile-list-comments", "tile-list-ozon-in-rating"
  }
}

// Трекинг товара
export interface ProductTrackingInfo {
  aux_click?: TrackingAction
  click: TrackingAction
  right_click?: TrackingAction
  view: TrackingAction
}

// Действие трекинга
export interface TrackingAction {
  actionType: string // "click", "view", "aux_click", "right_click", "favorite"
  key: string
  custom?: {
    advertLite?: string // Для рекламных товаров
    [key: string]: any
  }
}

// Стили цен из Ozon
export const PRICE_STYLES = {
  SALE_PRICE: {
    gradient: {
      startColor: "#F1117E",
      endColor: "#F1117E"
    }
  },
  CARD_PRICE: {
    normal: true
  },
  ACTUAL_PRICE: {
    emphasis: true
  }
} as const

// Цвета текста из дизайн-системы Ozon
export const TEXT_COLORS = {
  textPremium: "#001a34",
  textSecondary: "#70757a",
  textOzon: "#005bff",
  textLightKey: "#ffffff"
} as const

// Цвета графики
export const GRAPHIC_COLORS = {
  graphicRating: "#ffa500",
  graphicTertiary: "#9ca0a5",
  graphicOzon: "#005bff",
  graphicLightKey: "#ffffff"
} as const

// Цвета фона
export const BACKGROUND_COLORS = {
  bgPositivePrimary: "#4caf50",
  marketingSuperHigh: "#f1117eff"
} as const

// Иконки из Ozon
export const ICON_NAMES = {
  // Бейджи
  HOT: "ic_s_hot_filled_compact",
  LIKE: "ic_s_like_filled_compact",
  
  // Метки
  STAR: "ic_s_star_filled_compact",
  DIALOG: "ic_s_dialog_filled_compact",
  OZON: "ic_s_ozon_circle_filled_compact"
} as const

// Тестовые ID для автоматизации
export const TEST_IDS = {
  FAVORITE_BUTTON: "favorite-simple-button",
  UNFAVORITE_BUTTON: "unfavorite-simple-button",
  TILE_NAME: "tile-name",
  TILE_RATING: "tile-list-rating",
  TILE_COMMENTS: "tile-list-comments",
  TILE_OZON: "tile-list-ozon-in-rating",
  BADGE_MARKETING: "badge-marketingSuperHigh",
  BADGE_PRICE: "badge-priceIndex"
} as const