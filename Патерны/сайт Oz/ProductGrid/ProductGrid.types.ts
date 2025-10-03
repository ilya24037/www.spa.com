/**
 * Типы для ProductGrid из структуры Ozon
 * Основано на анализе widgetOptions и params из JSON
 */

// Из Ozon widgetOptions
export interface WidgetOptions {
  columnsCount: number // Количество колонок (default: 5)
  ratio: string // Соотношение сторон карточек (default: "3:4")
}

// Из Ozon params для skuGridSimple
export interface ProductGridParams {
  algo: string // Алгоритм сортировки (default: "1")
  itemsOnPage: number // Товаров на странице (default: 30)
  allowCPM: boolean // Разрешить CPM рекламу
  offset: number // Начальный offset (default: 40)
  paginationExtraEmptyPage: boolean // Дополнительная пустая страница
  usePagination: boolean // Использовать пагинацию
}

// Конфигурация виджета из Ozon
export interface WidgetConfig {
  component: string // "skuGridSimple"
  params: string // JSON строка с параметрами
  stateId: string // ID состояния виджета
  version: number // Версия виджета
  vertical: string // Вертикаль ("products")
  widgetToken?: string // Токен виджета
  widgetTrackingInfo?: string // JSON с информацией для трекинга
  trackingOn: boolean // Включен ли трекинг
  timeSpent?: number // Время на виджете (мс)
  name: string // Имя виджета ("shelf.infiniteScroll")
  id?: number // ID виджета
  isTrackView: boolean // Отслеживать просмотры
  isTrackingOn: boolean // Трекинг активен
}

// Информация для трекинга из Ozon
export interface WidgetTrackingInfo {
  name: string // "shelf.infiniteScroll"
  vertical: string // "products"
  component: string // "skuGridSimple"
  version: number // 1
  originName?: string // Оригинальное имя
  originVertical?: string // Оригинальная вертикаль
  originComponent?: string // Оригинальный компонент
  originVersion?: number // Оригинальная версия
  id: number // ID виджета
  configId?: number // ID конфигурации
  configDtId?: number // ID DT конфигурации
  revisionId?: number // ID ревизии
  index: number // Индекс на странице
  dtName?: string // Имя в DT ("sku.GridSimple")
}

// Опции для composable
export interface ProductGridOptions {
  itemsOnPage: number
  initialOffset: number
  algo: string
}

// Состояние сетки
export interface GridState {
  isLoading: boolean
  hasMore: boolean
  isEmpty: boolean
  currentOffset: number
  totalLoaded: number
  timeStarted: number
}

// Параметры загрузки
export interface LoadParams {
  offset: number
  limit: number
  algo?: string
  filters?: Record<string, any>
}

// Ответ от API (структура Ozon)
export interface GridApiResponse {
  products: Product[]
  hasMore: boolean
  nextOffset: number
  total?: number
}

// Структура товара из Ozon
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
  trackingInfo?: TrackingInfo
  testInfo?: TestInfo
}

// Изображение товара
export interface ProductImage {
  link: string
  contentMode: "SCALE_ASPECT_FIT" | "SCALE_ASPECT_FILL"
}

// Бейдж товара
export interface ProductBadge {
  text: string
  image?: string // "ic_s_hot_filled_compact"
  tintColor?: string
  iconTintColor?: string
  backgroundColor: string
  testInfo?: TestInfo
  theme?: "STYLE_TYPE_MEDIUM"
  iconPosition?: "ICON_POSITION_LEFT"
}

// Состояние товара (полиморфное)
export type ProductState = PriceState | TextState | LabelListState

// Состояние цены
export interface PriceState {
  type: "priceV2"
  priceV2: {
    price: Array<{
      text: string
      textStyle: "PRICE" | "ORIGINAL_PRICE"
    }>
    discount?: string
    priceStyle: {
      styleType: "SALE_PRICE" | "CARD_PRICE" | "ACTUAL_PRICE"
      gradient?: {
        startColor: string
        endColor: string
      }
    }
    preset: string // "SIZE_500"
    paddingBottom: string // "PADDING_200"
  }
}

// Текстовое состояние
export interface TextState {
  type: "textAtom"
  textAtom: {
    text: string
    textStyle: string // "tsBodyL"
    maxLines: number
    testInfo?: TestInfo
  }
  id?: string // "name"
}

// Список меток
export interface LabelListState {
  type: "labelList"
  labelList: {
    items: Array<{
      icon?: {
        image: string // "ic_s_star_filled_compact"
        tintColor: string // "graphicRating"
      }
      title: string
      titleColor: string // "textPremium"
      testInfo?: TestInfo
    }>
    textStyle: string // "tsBodyMBold"
    maxLines: number
    align: "ALIGN_LEFT"
    testInfo?: TestInfo
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
  actionType: string
  key: string
  custom?: Record<string, any>
}

// Информация трекинга
export interface TrackingInfo {
  click?: TrackingAction
  view?: TrackingAction
}

// Тестовая информация
export interface TestInfo {
  automatizationId?: string
  favoriteButton?: {
    automatizationId: string
  }
  unFavoriteButton?: {
    automatizationId: string
  }
}