/**
 * TypeScript типы для фичи профильной навигации
 * Типы для элементов навигации, состояния и уведомлений
 */

// import type { ComputedRef, Ref } from 'vue'

// =================== ОСНОВНЫЕ ТИПЫ ===================

export interface NavigationItem {
  id: string
  title: string
  icon: string
  route: string
  badge: NotificationBadge | null
  children: NavigationItem[]
  isVisible?: boolean
  isDisabled?: boolean
  onClick?: () => void
  permissions?: string[]
  meta?: Record<string, any>
}

export interface NotificationBadge {
  count: number
  type: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info'
  urgent: boolean
  text?: string
  tooltip?: string
}

export interface NavigationState {
  isCollapsed: boolean
  isMobileOpen: boolean
  activeSection: string
  hoveredItem: string | null
  isLoading: boolean
  error: string | null
  notifications: Record<string, NotificationBadge>
  navigationItems: NavigationItem[]
}

// =================== СОБЫТИЯ НАВИГАЦИИ ===================

export interface NavigationEvents {
  'section-changed': [sectionId: string, item: NavigationItem]
  'mobile-toggled': [isOpen: boolean]
  'collapsed-toggled': [isCollapsed: boolean]
  'item-clicked': [item: NavigationItem]
  'notification-updated': [sectionId: string, badge: NotificationBadge]
}

// =================== КОМПОНЕНТЫ ===================

export interface NavigationMenuProps {
  items: NavigationItem[]
  collapsed?: boolean
  activeSection?: string
  showBadges?: boolean
  variant?: 'sidebar' | 'tabs' | 'breadcrumbs'
}

export interface NavigationItemProps {
  item: NavigationItem
  active?: boolean
  collapsed?: boolean
  level?: number
  showBadge?: boolean
  onClick?: (item: NavigationItem) => void
}

export interface NavigationBadgeProps {
  badge: NotificationBadge
  size?: 'sm' | 'md' | 'lg'
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'inline'
  animated?: boolean
}

export interface BreadcrumbsProps {
  items: NavigationItem[]
  separator?: string
  maxItems?: number
  showHome?: boolean
  onItemClick?: (item: NavigationItem) => void
}

export interface MobileMenuProps {
  isOpen: boolean
  items: NavigationItem[]
  activeSection?: string
  onClose?: () => void
  onItemClick?: (item: NavigationItem) => void
}

// =================== НАВИГАЦИОННЫЕ РАЗДЕЛЫ ===================

export interface ProfileSection {
  id: string
  title: string
  description?: string
  icon: string
  route: string
  component?: string
  requiredPermissions?: string[]
  isVisible: boolean
  order: number
  badge?: NotificationBadge
  children?: ProfileSection[]
}

// Предопределенные разделы профиля
export const PROFILE_SECTIONS = {
  DASHBOARD: 'dashboard',
  ADS: 'ads',
  ADS_ACTIVE: 'ads-active',
  ADS_DRAFTS: 'ads-drafts',
  ADS_ARCHIVED: 'ads-archived',
  BOOKINGS: 'bookings',
  BOOKINGS_UPCOMING: 'bookings-upcoming',
  BOOKINGS_PENDING: 'bookings-pending',
  BOOKINGS_HISTORY: 'bookings-history',
  MESSAGES: 'messages',
  REVIEWS: 'reviews',
  REVIEWS_RECEIVED: 'reviews-received',
  REVIEWS_LEFT: 'reviews-left',
  FAVORITES: 'favorites',
  WALLET: 'wallet',
  WALLET_BALANCE: 'wallet-balance',
  WALLET_TRANSACTIONS: 'wallet-transactions',
  WALLET_PAYOUTS: 'wallet-payouts',
  SETTINGS: 'settings',
  SETTINGS_PROFILE: 'settings-profile',
  SETTINGS_NOTIFICATIONS: 'settings-notifications',
  SETTINGS_PRIVACY: 'settings-privacy',
  SETTINGS_SECURITY: 'settings-security'
} as const

export type ProfileSectionId = typeof PROFILE_SECTIONS[keyof typeof PROFILE_SECTIONS]

// =================== ПРАВА ДОСТУПА ===================

export interface NavigationPermission {
  sectionId: string
  requiredRoles: string[]
  requiredPermissions: string[]
  isVisible: (user: any) => boolean
  isAccessible: (user: any) => boolean
}

export const USER_ROLES = {
  GUEST: 'guest',
  CLIENT: 'client',
  MASTER: 'master',
  ADMIN: 'admin',
  MODERATOR: 'moderator'
} as const

export type UserRole = typeof USER_ROLES[keyof typeof USER_ROLES]

export const PERMISSIONS = {
  VIEW_ADS: 'view_ads',
  CREATE_ADS: 'create_ads',
  EDIT_ADS: 'edit_ads',
  DELETE_ADS: 'delete_ads',
  VIEW_BOOKINGS: 'view_bookings',
  MANAGE_BOOKINGS: 'manage_bookings',
  VIEW_MESSAGES: 'view_messages',
  SEND_MESSAGES: 'send_messages',
  VIEW_REVIEWS: 'view_reviews',
  MODERATE_REVIEWS: 'moderate_reviews',
  VIEW_WALLET: 'view_wallet',
  MANAGE_WALLET: 'manage_wallet',
  VIEW_SETTINGS: 'view_settings',
  MANAGE_SETTINGS: 'manage_settings'
} as const

export type Permission = typeof PERMISSIONS[keyof typeof PERMISSIONS]

// =================== УВЕДОМЛЕНИЯ ===================

export interface NotificationConfig {
  type: NotificationBadge['type']
  showCount: boolean
  maxCount: number
  urgent: boolean
  blinking: boolean
  sound: boolean
  desktop: boolean
}

export const NOTIFICATION_TYPES: Record<string, NotificationConfig> = {
  'new-booking': {
    type: 'primary',
    showCount: true,
    maxCount: 99,
    urgent: true,
    blinking: true,
    sound: true,
    desktop: true
  },
  'new-message': {
    type: 'info',
    showCount: true,
    maxCount: 99,
    urgent: false,
    blinking: false,
    sound: true,
    desktop: true
  },
  'new-review': {
    type: 'success',
    showCount: true,
    maxCount: 99,
    urgent: false,
    blinking: false,
    sound: false,
    desktop: true
  },
  'payment-required': {
    type: 'warning',
    showCount: false,
    maxCount: 1,
    urgent: true,
    blinking: true,
    sound: true,
    desktop: true
  },
  'system-alert': {
    type: 'danger',
    showCount: false,
    maxCount: 1,
    urgent: true,
    blinking: true,
    sound: true,
    desktop: true
  }
}

// =================== НАСТРОЙКИ НАВИГАЦИИ ===================

export interface NavigationSettings {
  // Поведение
  autoCollapse: boolean
  rememberState: boolean
  highlightActive: boolean
  showTooltips: boolean
  
  // Внешний вид
  theme: 'light' | 'dark' | 'auto'
  variant: 'sidebar' | 'tabs' | 'minimal'
  iconSize: 'sm' | 'md' | 'lg'
  showLabels: boolean
  
  // Уведомления
  showBadges: boolean
  animateBadges: boolean
  groupSimilar: boolean
  
  // Мобильная версия
  swipeToOpen: boolean
  overlayClose: boolean
  fullHeight: boolean
  
  // Быстрые действия
  showQuickActions: boolean
  quickActionsPosition: 'top' | 'bottom'
  customActions: QuickAction[]
}

export interface QuickAction {
  id: string
  title: string
  icon: string
  action: () => void
  shortcut?: string
  badge?: NotificationBadge
  isVisible?: boolean
  order?: number
}

// =================== ИСТОРИЯ НАВИГАЦИИ ===================

export interface NavigationHistory {
  items: NavigationHistoryItem[]
  maxItems: number
  currentIndex: number
}

export interface NavigationHistoryItem {
  sectionId: string
  title: string
  route: string
  timestamp: Date
  params?: Record<string, any>
}

// =================== ПОИСК ПО НАВИГАЦИИ ===================

export interface NavigationSearchResult {
  item: NavigationItem
  score: number
  matchType: 'title' | 'description' | 'route'
  highlightRanges: Array<{ start: number, end: number }>
}

export interface NavigationSearchOptions {
  query: string
  includeHidden?: boolean
  includeDisabled?: boolean
  maxResults?: number
  fuzzy?: boolean
}

// =================== АНАЛИТИКА НАВИГАЦИИ ===================

export interface NavigationAnalytics {
  mostVisited: Array<{ sectionId: string, visits: number }>
  averageTime: Record<string, number>
  bounceRate: Record<string, number>
  conversionPaths: Array<{ path: string[], count: number }>
  searchQueries: Array<{ query: string, count: number }>
}

// =================== КОНФИГУРАЦИЯ ===================

export const DEFAULT_NAVIGATION_SETTINGS: NavigationSettings = {
  autoCollapse: false,
  rememberState: true,
  highlightActive: true,
  showTooltips: true,
  theme: 'auto',
  variant: 'sidebar',
  iconSize: 'md',
  showLabels: true,
  showBadges: true,
  animateBadges: true,
  groupSimilar: true,
  swipeToOpen: true,
  overlayClose: true,
  fullHeight: true,
  showQuickActions: true,
  quickActionsPosition: 'bottom',
  customActions: []
}

export const MOBILE_BREAKPOINT = 768
export const TABLET_BREAKPOINT = 1024
export const COLLAPSED_WIDTH = 64
export const EXPANDED_WIDTH = 256

// =================== УТИЛИТЫ ===================

export type NavigationTheme = 'light' | 'dark' | 'auto'
export type NavigationVariant = 'sidebar' | 'tabs' | 'minimal' | 'breadcrumbs'
export type BadgePosition = 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'inline'
export type IconSize = 'sm' | 'md' | 'lg'