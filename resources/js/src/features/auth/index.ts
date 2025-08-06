// Auth Feature exports
export { AuthWidget } from './ui/AuthWidget'
export { UserDropdown } from './ui/UserDropdown'
export { NotificationButton } from './ui/NotificationButton'
export { WalletButton } from './ui/WalletButton'

// Store
export { useAuthStore } from './model/auth.store'

// Types
export type { 
  User, 
  LoginCredentials, 
  RegisterData, 
  AuthError 
} from './model/auth.store'