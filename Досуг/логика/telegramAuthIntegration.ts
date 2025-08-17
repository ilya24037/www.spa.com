/**
 * Интеграция авторизации через Telegram Bot
 * Основано на идее из dosugbar_form
 * 
 * Для работы нужно:
 * 1. Создать бота через @BotFather
 * 2. Настроить webhook на вашем сервере
 * 3. Реализовать обработку команд бота
 */

import { ref, reactive } from 'vue'

// Конфигурация Telegram бота
export const TELEGRAM_BOT_CONFIG = {
  username: 'spa_platform_bot', // Замените на username вашего бота
  authUrl: 'https://t.me/', // Базовый URL Telegram
  webhookUrl: '/api/telegram/webhook', // Endpoint для webhook
  checkInterval: 2000, // Интервал проверки авторизации (мс)
  maxCheckTime: 300000, // Максимальное время ожидания (5 минут)
}

// Типы
export interface TelegramUser {
  id: number
  first_name: string
  last_name?: string
  username?: string
  photo_url?: string
  auth_date: number
  hash: string
}

export interface AuthSession {
  token: string
  status: 'pending' | 'authenticated' | 'failed' | 'expired'
  telegramUser?: TelegramUser
  createdAt: Date
  expiresAt: Date
}

// Composable для Telegram авторизации
export const useTelegramAuth = () => {
  // Состояние
  const isAuthenticating = ref(false)
  const authSession = ref<AuthSession | null>(null)
  const error = ref<string | null>(null)
  
  // Генерация уникального токена для сессии
  const generateAuthToken = (): string => {
    const timestamp = Date.now().toString(36)
    const randomStr = Math.random().toString(36).substring(2, 15)
    return `${timestamp}_${randomStr}`
  }
  
  // Создание deep link для авторизации
  const createAuthDeepLink = (token: string): string => {
    const botUsername = TELEGRAM_BOT_CONFIG.username
    const startParam = `auth_${token}`
    return `${TELEGRAM_BOT_CONFIG.authUrl}${botUsername}?start=${startParam}`
  }
  
  // Начало процесса авторизации
  const startTelegramAuth = async () => {
    isAuthenticating.value = true
    error.value = null
    
    try {
      // 1. Создаем сессию авторизации на сервере
      const token = generateAuthToken()
      const response = await fetch('/api/auth/telegram/session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token })
      })
      
      if (!response.ok) {
        throw new Error('Не удалось создать сессию авторизации')
      }
      
      const session = await response.json()
      authSession.value = {
        token: session.token,
        status: 'pending',
        createdAt: new Date(),
        expiresAt: new Date(Date.now() + TELEGRAM_BOT_CONFIG.maxCheckTime)
      }
      
      // 2. Открываем Telegram с deep link
      const authUrl = createAuthDeepLink(token)
      window.open(authUrl, '_blank')
      
      // 3. Начинаем проверку статуса авторизации
      pollAuthStatus(token)
      
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Ошибка авторизации'
      isAuthenticating.value = false
    }
  }
  
  // Проверка статуса авторизации
  const pollAuthStatus = (token: string) => {
    let checkCount = 0
    const maxChecks = TELEGRAM_BOT_CONFIG.maxCheckTime / TELEGRAM_BOT_CONFIG.checkInterval
    
    const checkInterval = setInterval(async () => {
      checkCount++
      
      try {
        const response = await fetch(`/api/auth/telegram/check/${token}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          
          if (data.status === 'authenticated') {
            // Успешная авторизация
            clearInterval(checkInterval)
            authSession.value = {
              ...authSession.value!,
              status: 'authenticated',
              telegramUser: data.user
            }
            
            // Сохраняем токен авторизации
            localStorage.setItem('auth_token', data.authToken)
            localStorage.setItem('telegram_user', JSON.stringify(data.user))
            
            // Вызываем callback успешной авторизации
            onAuthSuccess(data)
            
          } else if (data.status === 'failed') {
            // Авторизация отклонена
            clearInterval(checkInterval)
            authSession.value!.status = 'failed'
            error.value = 'Авторизация отклонена'
          }
        }
        
        // Проверка таймаута
        if (checkCount >= maxChecks) {
          clearInterval(checkInterval)
          authSession.value!.status = 'expired'
          error.value = 'Время ожидания истекло'
          isAuthenticating.value = false
        }
        
      } catch (err) {
        clearInterval(checkInterval)
        error.value = 'Ошибка при проверке статуса'
        isAuthenticating.value = false
      }
    }, TELEGRAM_BOT_CONFIG.checkInterval)
  }
  
  // Обработка успешной авторизации
  const onAuthSuccess = (data: any) => {
    isAuthenticating.value = false
    
    // Редирект на dashboard или обновление UI
    window.location.href = '/dashboard'
  }
  
  // Отмена авторизации
  const cancelAuth = () => {
    isAuthenticating.value = false
    authSession.value = null
    error.value = null
  }
  
  // Выход
  const logout = () => {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('telegram_user')
    authSession.value = null
  }
  
  // Проверка существующей авторизации
  const checkExistingAuth = (): TelegramUser | null => {
    const userStr = localStorage.getItem('telegram_user')
    if (userStr) {
      try {
        return JSON.parse(userStr)
      } catch {
        return null
      }
    }
    return null
  }
  
  return {
    // Состояние
    isAuthenticating,
    authSession,
    error,
    
    // Методы
    startTelegramAuth,
    cancelAuth,
    logout,
    checkExistingAuth,
    
    // Конфиг
    config: TELEGRAM_BOT_CONFIG
  }
}

/**
 * Валидация данных от Telegram (серверная часть)
 * ВАЖНО: Эта функция должна выполняться на сервере!
 */
export const validateTelegramAuth = (authData: TelegramUser, botToken: string): boolean => {
  // Создаем строку для проверки
  const checkString = Object.keys(authData)
    .filter(key => key !== 'hash')
    .sort()
    .map(key => `${key}=${authData[key as keyof TelegramUser]}`)
    .join('\n')
  
  // Здесь должна быть проверка хеша с использованием bot token
  // Это MUST выполняться на сервере!
  
  // Проверка времени авторизации (не старше 1 дня)
  const authTime = authData.auth_date * 1000
  const currentTime = Date.now()
  const dayInMs = 86400000
  
  if (currentTime - authTime > dayInMs) {
    return false
  }
  
  return true
}

/**
 * Пример серверного обработчика webhook от Telegram бота
 * (Node.js/Express пример)
 */
export const telegramWebhookHandler = `
// server.js
app.post('/api/telegram/webhook', async (req, res) => {
  const update = req.body
  
  // Обработка команды /start с параметром auth_
  if (update.message?.text?.startsWith('/start auth_')) {
    const token = update.message.text.replace('/start auth_', '')
    const userId = update.message.from.id
    const userName = update.message.from.first_name
    
    // Сохраняем связь токена с пользователем
    await saveAuthSession(token, {
      telegramId: userId,
      firstName: userName,
      lastName: update.message.from.last_name,
      username: update.message.from.username
    })
    
    // Отправляем сообщение пользователю
    await bot.sendMessage(userId, 
      '✅ Авторизация успешна!\\n' +
      'Вернитесь на сайт для продолжения работы.', 
      {
        reply_markup: {
          inline_keyboard: [[
            { text: '🌐 Вернуться на сайт', url: 'https://your-spa.com/dashboard' }
          ]]
        }
      }
    )
  }
  
  res.sendStatus(200)
})
`

/**
 * Компонент кнопки Telegram авторизации
 */
export const TelegramLoginButton = `
<template>
  <button 
    @click="loginWithTelegram"
    class="telegram-login-button"
    :disabled="isAuthenticating"
  >
    <svg class="telegram-icon" viewBox="0 0 24 24">
      <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121L8.32 13.617l-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
    </svg>
    <span v-if="!isAuthenticating">Войти через Telegram</span>
    <span v-else>Ожидание авторизации...</span>
  </button>
</template>

<script setup>
import { useTelegramAuth } from './telegramAuthIntegration'

const { startTelegramAuth, isAuthenticating } = useTelegramAuth()

const loginWithTelegram = () => {
  startTelegramAuth()
}
</script>

<style scoped>
.telegram-login-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #0088cc;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.telegram-login-button:hover:not(:disabled) {
  background: #0077b5;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
}

.telegram-login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.telegram-icon {
  width: 24px;
  height: 24px;
  fill: currentColor;
}
</style>
`