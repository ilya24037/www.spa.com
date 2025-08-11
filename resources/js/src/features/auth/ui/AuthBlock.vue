<template>
  <div class="auth-block">
    <!-- Для авторизованных пользователей -->
    <UserDropdown v-if="isAuthenticated" />
    
    <!-- Для неавторизованных -->
    <div v-else class="flex items-center gap-2">
      <button 
        @click="openLoginModal"
        class="text-sm text-gray-700 hover:text-gray-900 font-medium transition-colors"
      >
        Войти
      </button>
      <span class="text-gray-400">/</span>
      <button 
        @click="openRegisterModal"
        class="text-sm text-gray-700 hover:text-gray-900 font-medium transition-colors"
      >
        Регистрация
      </button>
    </div>
    
    <!-- Модалки авторизации -->
    <LoginModal 
      v-if="showLoginModal" 
      @close="closeLoginModal"
      @switch-to-register="switchToRegister"
    />
    
    <RegisterModal 
      v-if="showRegisterModal" 
      @close="closeRegisterModal"
      @switch-to-login="switchToLogin"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import UserDropdown from './UserDropdown.vue'
import LoginModal from './LoginModal.vue'
import RegisterModal from './RegisterModal.vue'

// Store
const authStore = useAuthStore()

// Computed
const isAuthenticated = computed(() => authStore.isAuthenticated)

// State
const showLoginModal = ref(false)
const showRegisterModal = ref(false)

// Methods
const openLoginModal = () => {
  showLoginModal.value = true
  showRegisterModal.value = false
}

const closeLoginModal = () => {
  showLoginModal.value = false
}

const openRegisterModal = () => {
  showRegisterModal.value = true
  showLoginModal.value = false
}

const closeRegisterModal = () => {
  showRegisterModal.value = false
}

const switchToRegister = () => {
  showLoginModal.value = false
  showRegisterModal.value = true
}

const switchToLogin = () => {
  showRegisterModal.value = false
  showLoginModal.value = true
}
</script>

<style scoped>
.auth-block {
  @apply flex items-center;
}
</style>