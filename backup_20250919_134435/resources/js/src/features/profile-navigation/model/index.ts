/**
 * Экспорты модели фичи profile-navigation
 * Централизованный доступ к store и типам
 */

// Store
export { useProfileNavigationStore, type TabKey, type Tab } from './navigation.store'

// Типы
export type {
  NavigationItem,
  NotificationBadge,
  NavigationState,
  NavigationEvents,
  NavigationMenuProps,
  NavigationItemProps,
  NavigationBadgeProps,
  BreadcrumbsProps,
  MobileMenuProps,
  ProfileSection,
  ProfileSectionId,
  NavigationPermission,
  UserRole,
  Permission,
  NotificationConfig,
  NavigationSettings,
  QuickAction,
  NavigationHistory,
  NavigationHistoryItem,
  NavigationSearchResult,
  NavigationSearchOptions,
  NavigationAnalytics,
  NavigationTheme,
  NavigationVariant,
  BadgePosition,
  IconSize
} from './types'

// Константы
export {
  PROFILE_SECTIONS,
  USER_ROLES,
  PERMISSIONS,
  NOTIFICATION_TYPES,
  DEFAULT_NAVIGATION_SETTINGS,
  MOBILE_BREAKPOINT,
  TABLET_BREAKPOINT,
  COLLAPSED_WIDTH,
  EXPANDED_WIDTH
} from './types'