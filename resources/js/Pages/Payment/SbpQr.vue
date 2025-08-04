<template>
  <Head title="Оплата по СБП" />
  
  <div class="payment-page">
    <!-- Логотип -->
    <div class="logo">
      <span class="logo-text">MASSAGIST</span>
    </div>

    <div class="payment-container">
      <!-- Кнопка назад -->
      <Link 
        :href="route('payment.checkout', { ad: ad.id })" 
        class="back-link"
      >
        ← Назад
      </Link>

      <!-- Заголовок и сумма -->
      <div class="payment-header">
        <h1 class="payment-title">Оплата услуг</h1>
        <div class="payment-amount">{{ payment.formatted_amount }}</div>
      </div>

      <!-- QR-код блок -->
      <div class="qr-payment-box">
        <div class="qr-content">
          <div class="qr-text">
            <h2 class="qr-title">Подтвердите платёж по СБП</h2>
            <p class="qr-instruction">
              Для этого отсканируйте QR-код камерой или в приложении банка
            </p>
          </div>
          
          <div class="qr-code">
            <!-- QR-код -->
            <div class="qr-code__image">
              <svg width="200" height="200" viewBox="0 0 200 200" fill="none">
                <!-- Простой QR-код (в реальном приложении здесь будет генерироваться настоящий QR) -->
                <rect width="200" height="200" fill="white"/>
                <rect x="10" y="10" width="180" height="180" fill="black"/>
                <rect x="20" y="20" width="160" height="160" fill="white"/>
                <rect x="30" y="30" width="140" height="140" fill="black"/>
                <rect x="40" y="40" width="120" height="120" fill="white"/>
                <rect x="50" y="50" width="100" height="100" fill="black"/>
                <rect x="60" y="60" width="80" height="80" fill="white"/>
                <rect x="70" y="70" width="60" height="60" fill="black"/>
                <rect x="80" y="80" width="40" height="40" fill="white"/>
                <rect x="90" y="90" width="20" height="20" fill="black"/>
                
                <!-- Логотип в центре -->
                <circle cx="100" cy="100" r="15" fill="white"/>
                <circle cx="100" cy="100" r="12" fill="#4285F4"/>
                <circle cx="100" cy="100" r="8" fill="#34A853"/>
                <circle cx="100" cy="100" r="4" fill="#FBBC05"/>
                <circle cx="100" cy="100" r="2" fill="#EA4335"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Информация о статусе -->
      <div class="payment-status">
        <div class="status-indicator">
          <div class="status-dot"></div>
          <span class="status-text">Ожидание оплаты...</span>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="payment-actions">
        <button 
          @click="checkPaymentStatus"
          :disabled="isChecking"
          class="check-button"
        >
          {{ isChecking ? 'Проверяем...' : 'Проверить оплату' }}
        </button>
        
        <button 
          @click="cancelPayment"
          class="cancel-button"
        >
          Отменить
        </button>
      </div>

      <!-- Информация о СБП -->
      <div class="sbp-info">
        <p class="sbp-text">
          СБП — Система быстрых платежей. Оплата происходит мгновенно через ваш банк.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
  payment: Object,
  ad: Object,
  qrCode: String
})

const isChecking = ref(false)
const paymentStatus = ref('pending')

// Автоматическая проверка статуса каждые 5 секунд
let statusInterval = null

onMounted(() => {
  // Запускаем автоматическую проверку
  statusInterval = setInterval(checkPaymentStatus, 5000)
})

const checkPaymentStatus = async () => {
  if (isChecking.value) return
  
  isChecking.value = true
  
  try {
    // Запрос к нашему API для проверки статуса
    const response = await fetch(route('payment.check-status', { payment: props.payment.id }))
    const data = await response.json()
    
    if (data.status === 'completed') {
      paymentStatus.value = 'completed'
      clearInterval(statusInterval)
      
      // Перенаправляем на страницу успеха
      router.visit(route('payment.success', { payment: props.payment.id }))
    }
  } catch (error) {
  } finally {
    isChecking.value = false
  }
}

const cancelPayment = () => {
  clearInterval(statusInterval)
  router.visit(route('payment.checkout', { ad: props.ad.id }))
}

// Очищаем интервал при размонтировании
onUnmounted(() => {
  if (statusInterval) {
    clearInterval(statusInterval)
  }
})
</script>

<style scoped>
.payment-page {
  min-height: 100vh;
  background: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.logo {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.logo-text {
  font-size: 24px;
  font-weight: 700;
  color: #000;
}

.payment-container {
  max-width: 500px;
  margin: 0 auto;
  padding: 40px 20px;
}

.back-link {
  display: inline-block;
  color: #666;
  text-decoration: none;
  font-size: 16px;
  margin-bottom: 30px;
  transition: color 0.2s ease;
}

.back-link:hover {
  color: #000;
}

.payment-header {
  text-align: center;
  margin-bottom: 40px;
}

.payment-title {
  font-size: 32px;
  font-weight: 700;
  color: #000;
  margin: 0 0 10px;
}

.payment-amount {
  font-size: 36px;
  font-weight: 700;
  color: #000;
}

.qr-payment-box {
  background: #f9fafb;
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 30px;
}

.qr-content {
  display: flex;
  align-items: center;
  gap: 30px;
}

.qr-text {
  flex: 1;
}

.qr-title {
  font-size: 20px;
  font-weight: 700;
  color: #000;
  margin: 0 0 10px;
}

.qr-instruction {
  font-size: 16px;
  color: #666;
  line-height: 1.4;
  margin: 0;
}

.qr-code {
  flex-shrink: 0;
}

.qr-code__image {
  width: 200px;
  height: 200px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
}

.payment-status {
  text-align: center;
  margin-bottom: 30px;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: #f3f4f6;
  border-radius: 20px;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: #f59e0b;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.status-text {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.payment-actions {
  display: flex;
  gap: 12px;
  margin-bottom: 30px;
}

.check-button {
  flex: 1;
  background: #000;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 16px 24px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
}

.check-button:hover {
  background: #333;
}

.check-button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.cancel-button {
  background: #fff;
  color: #666;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px 24px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-button:hover {
  border-color: #d1d5db;
  color: #000;
}

.sbp-info {
  text-align: center;
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.sbp-text {
  font-size: 14px;
  color: #666;
  line-height: 1.4;
  margin: 0;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* Адаптивность */
@media (max-width: 640px) {
  .payment-container {
    padding: 20px;
  }
  
  .payment-title {
    font-size: 28px;
  }
  
  .payment-amount {
    font-size: 32px;
  }
  
  .qr-content {
    flex-direction: column;
    text-align: center;
  }
  
  .qr-code__image {
    width: 150px;
    height: 150px;
  }
  
  .payment-actions {
    flex-direction: column;
  }
}
</style> 