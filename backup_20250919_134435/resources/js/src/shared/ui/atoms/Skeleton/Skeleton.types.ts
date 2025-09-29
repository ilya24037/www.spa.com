/**
 * TypeScript типы для Skeleton компонента
 */

export type SkeletonVariant = 
  | 'text'      // Строка текста
  | 'heading'   // Заголовок
  | 'paragraph' // Абзац
  | 'button'    // Кнопка
  | 'avatar'    // Аватар
  | 'image'     // Изображение
  | 'card'      // Карточка
  | 'circular'  // Круглый элемент

export type SkeletonSize = 'small' | 'medium' | 'large'

export interface SkeletonProps {
  /** Тип скелетона */
  variant?: SkeletonVariant
  
  /** Размер скелетона */
  size?: SkeletonSize
  
  /** Ширина (px или строка с единицами) */
  width?: number | string
  
  /** Высота (px или строка с единицами) */
  height?: number | string
  
  /** Анимированный эффект */
  animated?: boolean
  
  /** Закругленные углы */
  rounded?: boolean
  
  /** Дополнительные CSS классы */
  customClass?: string
  
  /** ARIA-метка для доступности */
  ariaLabel?: string
}

export interface SkeletonGroupProps {
  /** Количество строк для группы */
  lines?: number
  
  /** Вариант для всех строк */
  variant?: SkeletonVariant
  
  /** Размер для всех строк */
  size?: SkeletonSize
  
  /** Анимация для всех строк */
  animated?: boolean
  
  /** Случайная ширина строк (для более реалистичного вида) */
  randomWidth?: boolean
}

export interface SkeletonOptions {
  /** Базовый вариант */
  variant?: SkeletonVariant
  
  /** Анимация по умолчанию */
  animated?: boolean
  
  /** Время показа скелетона в мс */
  duration?: number
}

// Предустановленные конфигурации скелетонов
export interface SkeletonPresets {
  /** Скелетон для карточки пользователя */
  userCard: {
    avatar: SkeletonProps
    name: SkeletonProps
    description: SkeletonProps
  }
  
  /** Скелетон для статьи */
  article: {
    title: SkeletonProps
    image: SkeletonProps
    paragraph: SkeletonProps[]
  }
  
  /** Скелетон для списка */
  listItem: {
    icon: SkeletonProps
    title: SkeletonProps
    subtitle: SkeletonProps
  }
  
  /** Скелетон для таблицы */
  tableRow: {
    cells: SkeletonProps[]
  }
}