<!-- Р’РёРґР¶РµС‚ Р°РІС‚РѕСЂРёР·Р°С†РёРё - FSD Feature -->
<template>
  <div class="flex items-center gap-3">
    <!-- Р”Р»СЏ Р°РІС‚РѕСЂРёР·РѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ -->
    <template v-if="isAuthenticated && user">
      <!-- РЈРІРµРґРѕРјР»РµРЅРёСЏ -->
      <NotificationButton 
        v-if="showNotifications"
        :count="notificationCount"
        @click="openNotifications"
      />
      
      <!-- РљРѕС€РµР»РµРє/Р±Р°Р»Р°РЅСЃ -->
      <WalletButton 
        v-if="showWallet"
        :balance="user.balance || 0"
        @click="openWallet"
      />
      
      <!-- РњРµРЅСЋ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ -->
      <UserDropdown 
        :user="user"
        :show-online-status="showOnlineStatus"
        @profile-click="goToProfile"
        @logout="handleLogout"
      />
      
      <!-- РљРЅРѕРїРєР° СЃРѕР·РґР°РЅРёСЏ РѕР±СЉСЏРІР»РµРЅРёСЏ -->
      <Link 
        v-if="showCreateAd"
        href="/additem"
        :class="createAdButtonClasses"
        :aria-label="createAdAriaLabel"
      >
        <svg 
          v-if="!hideIconOnMobile"
          class="w-5 h-5 mr-2" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span :class="createAdTextClasses">
          Р Р°Р·РјРµСЃС‚РёС‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ
        </span>
      </Link>
    </template>

    <!-- Р”Р»СЏ РЅРµР°РІС‚РѕСЂРёР·РѕРІР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ -->
    <template v-else>
      <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
      <div v-if="isLoading" class="flex gap-2">
        <div class="w-20 h-10 bg-gray-200 rounded-lg animate-pulse"></div>
        <div class="w-28 h-10 bg-gray-200 rounded-lg animate-pulse"></div>
      </div>
      
      <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
      <div v-else-if="error" class="text-red-500 text-sm">
        {{ error }}
      </div>
      
      <!-- РћР±С‹С‡РЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ -->
      <div v-else class="flex items-center gap-2">
        <!-- РљРЅРѕРїРєР° РІС…РѕРґР° -->
        <button 
          @click="showLogin"
          :class="loginButtonClasses"
          :aria-label="loginAriaLabel"
        >
          <svg 
            class="w-5 h-5 mr-2" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
          </svg>
          <span class="hidden sm:inline">Р’РѕР№С‚Рё</span>
        </button>
        
        <!-- РљРЅРѕРїРєР° СЂРµРіРёСЃС‚СЂР°С†РёРё -->
        <button 
          @click="showRegister"
          :class="registerButtonClasses"
          :aria-label="registerAriaLabel"
        >
          <svg 
            class="w-5 h-5 mr-2" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
          <span>Р РµРіРёСЃС‚СЂР°С†РёСЏ</span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuthStore } from '../../model/auth.store'

// Components
import UserDropdown from '../UserDropdown/UserDropdown.vue'
import NotificationButton from '../NotificationButton/NotificationButton.vue'
import WalletButton from '../WalletButton/WalletButton.vue'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface User {
  id: number
  name: string
  email: string
  avatar?: string
  balance?: number
  role?: 'user' | 'master' | 'admin'
  isOnline?: boolean
}

interface Props {
  user?: User | null
  isAuthenticated?: boolean
  showNotifications?: boolean
  showWallet?: boolean
  showOnlineStatus?: boolean
  showCreateAd?: boolean
  hideIconOnMobile?: boolean
  isLoading?: boolean
  error?: string
  notificationCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  user: null,
  isAuthenticated: false,
  showNotifications: true,
  showWallet: true,
  showOnlineStatus: true,
  showCreateAd: true,
  hideIconOnMobile: false,
  isLoading: false,
  error: '',
  notificationCount: 0
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'show-login': []
  'show-register': []
  'logout': []
  'notification-click': []
  'wallet-click': []
  'profile-click': []
}>()

// Store
const authStore = useAuthStore()

// Computed properties
const createAdButtonClasses = computed(() => [
  'bg-blue-600 text-white px-4 lg:px-6 py-2.5 rounded-lg font-medium transition-colors',
  'hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  'flex items-center whitespace-nowrap'
])

const createAdTextClasses = computed(() => [
  'hidden sm:inline lg:inline'
])

const loginButtonClasses = computed(() => [
  'text-gray-700 px-3 lg:px-4 py-2.5 rounded-lg font-medium transition-colors',
  'hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2',
  'flex items-center'
])

const registerButtonClasses = computed(() => [
  'bg-blue-600 text-white px-4 lg:px-6 py-2.5 rounded-lg font-medium transition-colors',
  'hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  'flex items-center whitespace-nowrap'
])

const createAdAriaLabel = computed(() => 
  'Р Р°Р·РјРµСЃС‚РёС‚СЊ РЅРѕРІРѕРµ РѕР±СЉСЏРІР»РµРЅРёРµ'
)

const loginAriaLabel = computed(() => 
  'Р’РѕР№С‚Рё РІ Р»РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚'
)

const registerAriaLabel = computed(() => 
  'Р—Р°СЂРµРіРёСЃС‚СЂРёСЂРѕРІР°С‚СЊСЃСЏ РЅР° РїР»Р°С‚С„РѕСЂРјРµ'
)

// Methods
const showLogin = (): void => {
  emit('show-login')
}

const showRegister = (): void => {
  emit('show-register')
}

const handleLogout = async (): Promise<void> => {
  try {
    await authStore.logout()
    emit('logout')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

const openNotifications = (): void => {
  emit('notification-click')
}

const openWallet = (): void => {
  emit('wallet-click')
}

const goToProfile = (): void => {
  if (props.user) {
    router.visit('/profile')
    emit('profile-click')
  }
}
</script>

<style scoped>
/* РђРЅРёРјР°С†РёСЏ РґР»СЏ РєРЅРѕРїРѕРє */
.auth-button {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.auth-button:hover {
  transform: translateY(-1px);
}

/* Responsive РёРєРѕРЅРєРё */
@media (max-width: 640px) {
  .mobile-hide-icon svg {
    display: none;
  }
  
  .mobile-hide-text {
    display: none;
  }
}

/* Focus styles РґР»СЏ Р»СѓС‡С€РµР№ РґРѕСЃС‚СѓРїРЅРѕСЃС‚Рё */
.auth-button:focus-visible {
  outline: 2px solid theme('colors.blue.500');
  outline-offset: 2px;
}

/* РЎРѕСЃС‚РѕСЏРЅРёРµ Р·Р°РіСЂСѓР·РєРё */
.loading-shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .hover\:bg-gray-100:hover {
    background-color: theme('colors.gray.800');
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}
</style>

