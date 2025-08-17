<template>
  <div class="login-form-container">
    <!-- Основная форма входа -->
    <form 
      class="login-form"
      @submit.prevent="handleLogin"
      novalidate
    >
      <h2 class="form-title">Вход в личный кабинет</h2>
      
      <!-- Сообщения об ошибках -->
      <div v-if="errorMessage" class="alert alert-error">
        <div class="alert-icon">⚠️</div>
        <div class="alert-message">{{ errorMessage }}</div>
      </div>
      
      <!-- Email поле с правильным inputmode для мобильных -->
      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input
          id="email"
          v-model="formData.email"
          type="email"
          inputmode="email"
          autocomplete="email"
          placeholder="example@mail.ru"
          maxlength="100"
          required
          :class="['form-input', { 'error': errors.email }]"
          @blur="validateEmail"
        />
        <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
      </div>
      
      <!-- Пароль с показом/скрытием -->
      <div class="form-group">
        <label for="password" class="form-label">Пароль</label>
        <div class="password-input-wrapper">
          <input
            id="password"
            v-model="formData.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Введите пароль"
            maxlength="100"
            required
            :class="['form-input', { 'error': errors.password }]"
            @blur="validatePassword"
          />
          <button
            type="button"
            class="password-toggle"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
          >
            {{ showPassword ? '👁️' : '👁️‍🗨️' }}
          </button>
        </div>
        <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
      </div>
      
      <!-- Запомнить меня -->
      <div class="form-group checkbox-group">
        <label class="checkbox-label">
          <input
            v-model="formData.rememberMe"
            type="checkbox"
            class="checkbox-input"
          />
          <span class="checkbox-text">Оставаться в системе</span>
        </label>
      </div>
      
      <!-- Кнопка входа -->
      <button
        type="submit"
        class="btn btn-primary btn-block"
        :disabled="isLoading || !isFormValid"
      >
        <span v-if="!isLoading">Войти</span>
        <span v-else class="loading-spinner">Вход...</span>
      </button>
      
      <!-- Дополнительные ссылки -->
      <div class="form-links">
        <router-link to="/reset-password" class="link">
          Забыли пароль?
        </router-link>
        <span class="separator">•</span>
        <router-link to="/registration" class="link">
          Регистрация
        </router-link>
      </div>
      
      <!-- Разделитель -->
      <div class="divider">
        <span>или</span>
      </div>
      
      <!-- Telegram авторизация -->
      <button
        type="button"
        class="btn btn-telegram"
        @click="loginWithTelegram"
      >
        <svg class="telegram-icon" width="24" height="24" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121L8.32 13.617l-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
        </svg>
        Войти через Telegram
      </button>
      
      <!-- Другие соц. сети (опционально) -->
      <div v-if="showSocialLogin" class="social-login">
        <button
          v-for="provider in socialProviders"
          :key="provider.name"
          type="button"
          class="btn btn-social"
          :class="`btn-${provider.name}`"
          @click="loginWithSocial(provider.name)"
        >
          <span class="social-icon">{{ provider.icon }}</span>
          {{ provider.label }}
        </button>
      </div>
    </form>
    
    <!-- Выпадающая форма для хедера (компактная версия) -->
    <form 
      v-if="isDropdown"
      class="login-form-dropdown"
      @submit.prevent="handleLogin"
    >
      <input
        v-model="formData.email"
        type="email"
        inputmode="email"
        placeholder="Email"
        maxlength="100"
        class="form-input-compact"
      />
      <input
        v-model="formData.password"
        type="password"
        placeholder="Пароль"
        maxlength="100"
        class="form-input-compact"
      />
      <div class="dropdown-actions">
        <button type="submit" class="btn-compact">Войти</button>
        <label class="remember-compact">
          <input v-model="formData.rememberMe" type="checkbox" />
          <span>Запомнить</span>
        </label>
      </div>
      <div class="dropdown-links">
        <router-link to="/reset-password">Забыли пароль?</router-link>
        <router-link to="/registration">Регистрация</router-link>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'

// Props
const props = defineProps<{
  isDropdown?: boolean
  showSocialLogin?: boolean
}>()

// Router
const router = useRouter()

// Form data
const formData = reactive({
  email: '',
  password: '',
  rememberMe: true
})

// State
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const errors = reactive({
  email: '',
  password: ''
})

// Social providers
const socialProviders = ref([
  { name: 'vk', label: 'ВКонтакте', icon: 'VK' },
  { name: 'google', label: 'Google', icon: 'G' },
  { name: 'yandex', label: 'Яндекс', icon: 'Я' }
])

// Computed
const isFormValid = computed(() => {
  return formData.email && 
         formData.password && 
         !errors.email && 
         !errors.password
})

// Validation
const validateEmail = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!formData.email) {
    errors.email = 'Email обязателен'
  } else if (!emailRegex.test(formData.email)) {
    errors.email = 'Неверный формат email'
  } else {
    errors.email = ''
  }
}

const validatePassword = () => {
  if (!formData.password) {
    errors.password = 'Пароль обязателен'
  } else if (formData.password.length < 6) {
    errors.password = 'Минимум 6 символов'
  } else {
    errors.password = ''
  }
}

// Login handlers
const handleLogin = async () => {
  // Валидация
  validateEmail()
  validatePassword()
  
  if (!isFormValid.value) return
  
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    // API запрос на авторизацию
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        email: formData.email,
        password: formData.password,
        remember: formData.rememberMe
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      // Сохранение токена
      localStorage.setItem('token', data.token)
      if (formData.rememberMe) {
        localStorage.setItem('rememberMe', 'true')
      }
      // Редирект
      router.push('/dashboard')
    } else {
      const error = await response.json()
      errorMessage.value = error.message || 'Неверный email или пароль'
    }
  } catch (error) {
    errorMessage.value = 'Ошибка соединения. Попробуйте позже.'
  } finally {
    isLoading.value = false
  }
}

// Telegram login
const loginWithTelegram = () => {
  // Открытие Telegram бота для авторизации
  const botUsername = 'your_spa_bot'
  const authUrl = `https://t.me/${botUsername}?start=auth_${generateAuthToken()}`
  window.open(authUrl, '_blank')
  
  // Слушаем webhook от бота
  listenForTelegramAuth()
}

// Generate auth token for Telegram
const generateAuthToken = (): string => {
  return Math.random().toString(36).substring(2, 15)
}

// Listen for Telegram auth webhook
const listenForTelegramAuth = () => {
  // WebSocket или polling для получения результата авторизации
  const checkInterval = setInterval(async () => {
    try {
      const response = await fetch('/api/auth/telegram/check', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('temp_token')}`
        }
      })
      
      if (response.ok) {
        const data = await response.json()
        if (data.authenticated) {
          clearInterval(checkInterval)
          localStorage.setItem('token', data.token)
          router.push('/dashboard')
        }
      }
    } catch (error) {
      console.error('Telegram auth check failed:', error)
    }
  }, 2000)
  
  // Остановить проверку через 5 минут
  setTimeout(() => clearInterval(checkInterval), 300000)
}

// Social login
const loginWithSocial = (provider: string) => {
  console.log(`Login with ${provider}`)
  // Реализация OAuth для соц. сетей
}
</script>

<style scoped>
/* Основные стили формы */
.login-form-container {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}

.login-form {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 24px;
  text-align: center;
  color: #333;
}

/* Группы формы */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #555;
}

/* Поля ввода с правильными inputmode */
.form-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px; /* Важно для мобильных - предотвращает zoom */
  border: 1px solid #ddd;
  border-radius: 8px;
  transition: all 0.3s;
  -webkit-appearance: none; /* Убирает стили iOS */
}

.form-input:focus {
  outline: none;
  border-color: #4CAF50;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.form-input.error {
  border-color: #f44336;
}

/* Поле пароля с кнопкой показа */
.password-input-wrapper {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  padding: 4px;
  opacity: 0.6;
  transition: opacity 0.3s;
}

.password-toggle:hover {
  opacity: 1;
}

/* Чекбокс */
.checkbox-group {
  margin-bottom: 24px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  margin-right: 8px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  color: #666;
  user-select: none;
}

/* Кнопки */
.btn {
  width: 100%;
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  background: #4CAF50;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #45a049;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Telegram button */
.btn-telegram {
  background: #0088cc;
  color: white;
  margin-top: 16px;
}

.btn-telegram:hover {
  background: #0077b5;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
}

.telegram-icon {
  width: 24px;
  height: 24px;
}

/* Social buttons */
.social-login {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 16px;
}

.btn-social {
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-vk {
  background: #4c75a3;
  color: white;
}

.btn-google {
  background: white;
  border: 1px solid #ddd;
  color: #333;
}

.btn-yandex {
  background: #ffcc00;
  color: #333;
}

/* Ссылки */
.form-links {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  font-size: 14px;
}

.link {
  color: #4CAF50;
  text-decoration: none;
  transition: color 0.3s;
}

.link:hover {
  color: #45a049;
  text-decoration: underline;
}

.separator {
  color: #ccc;
}

/* Разделитель */
.divider {
  position: relative;
  text-align: center;
  margin: 24px 0;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e0e0e0;
}

.divider span {
  position: relative;
  padding: 0 16px;
  background: white;
  color: #999;
  font-size: 14px;
}

/* Сообщения об ошибках */
.alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.alert-error {
  background: #ffebee;
  border: 1px solid #ffcdd2;
  color: #c62828;
}

.alert-icon {
  font-size: 20px;
}

.error-text {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #f44336;
}

/* Loading spinner */
.loading-spinner {
  display: inline-block;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Выпадающая форма (компактная) */
.login-form-dropdown {
  background: white;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-width: 280px;
}

.form-input-compact {
  width: 100%;
  padding: 8px 12px;
  margin-bottom: 12px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.dropdown-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.btn-compact {
  padding: 8px 16px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.remember-compact {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #666;
}

.dropdown-links {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.dropdown-links a {
  color: #4CAF50;
  text-decoration: none;
}

/* Мобильная адаптация */
@media (max-width: 480px) {
  .login-form {
    padding: 24px 20px;
  }
  
  .form-title {
    font-size: 20px;
  }
  
  /* Важно для iOS - предотвращает zoom при фокусе */
  .form-input {
    font-size: 16px !important;
  }
}
</style>