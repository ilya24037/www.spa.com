<template>
  <Head title="Оплата услуг" />
  
  <div class="payment-page">
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="form"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- Основной контент -->
    <template v-else>
      <!-- Логотип -->
      <div class="logo">
        <span class="logo-text">MASSAGIST</span>
      </div>

      <div class="payment-container">
      <!-- Кнопка назад -->
      <Link 
        :href="route('payment.select-plan', { ad: ad.id })" 
        class="back-link"
      >
        ← Назад
      </Link>

      <!-- Заголовок и сумма -->
      <div class="payment-header">
        <h1 class="payment-title">Оплата услуг</h1>
        <div class="payment-amount">{{ payment.formatted_amount }}</div>
      </div>

      <!-- Выбор способа оплаты -->
      <div class="payment-methods">
        <!-- СБП -->
        <div 
          class="payment-method"
          :class="{ 'payment-method--active': paymentMethod === 'sbp' }"
          @click="paymentMethod = 'sbp'"
        >
          <div class="payment-method__icon payment-method__icon--sbp">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#8B5CF6"/>
              <path d="M2 17L12 22L22 17" stroke="#8B5CF6" stroke-width="2"/>
              <path d="M2 12L12 17L22 12" stroke="#8B5CF6" stroke-width="2"/>
            </svg>
          </div>
          <span class="payment-method__text">СБП</span>
        </div>

        <!-- Кошелёк -->
        <div 
          class="payment-method"
          :class="{ 'payment-method--active': paymentMethod === 'wallet' }"
          @click="paymentMethod = 'wallet'"
        >
          <div class="payment-method__icon payment-method__icon--wallet">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M3 10H21M7 15H9M15 15H17M3 6H21C22.1046 6 23 6.89543 23 8V18C23 19.1046 22.1046 20 21 20H3C1.89543 20 1 19.1046 1 18V8C1 6.89543 1.89543 6 3 6Z" stroke="#3B82F6" stroke-width="2"/>
            </svg>
          </div>
          <span class="payment-method__text">Кошелёк</span>
        </div>

        <!-- Новая карта -->
        <div 
          class="payment-method"
          :class="{ 'payment-method--active': paymentMethod === 'card' }"
          @click="paymentMethod = 'card'"
        >
          <div class="payment-method__icon payment-method__icon--card">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M12 5V19M5 12H19" stroke="#6B7280" stroke-width="2"/>
            </svg>
          </div>
          <span class="payment-method__text">Новая карта</span>
        </div>
      </div>

      <!-- Инструкция -->
      <div class="payment-instruction">
        Дальше будет QR для оплаты
      </div>

      <!-- Кнопка оплаты -->
      <button 
        @click="processPayment"
        :disabled="!paymentMethod || isProcessing"
        class="payment-button"
      >
        {{ isProcessing ? 'Обработка...' : 'Перейти к оплате' }}
      </button>

      <!-- Политика безопасности -->
      <div class="security-info">
        Интернет-платежи защищены сертификатом SSL и протоколом 3D Secure. 
        <Link href="#" class="security-link">Политика конфиденциальности</Link>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация props
interface Payment {
  id: number | string
  formatted_amount: string
  amount: number
  [key: string]: any
}

interface Ad {
  id: number | string
  title?: string
  [key: string]: any
}

interface Plan {
  id: number | string
  name: string
  price: number
  [key: string]: any
}

interface CheckoutProps {
  payment: Payment
  ad: Ad
  plan: Plan
}

const props = defineProps<CheckoutProps>()

// Управление загрузкой страницы
const pageLoader = usePageLoading({
  type: 'form',
  autoStart: true,
  timeout: 5000,
  onStart: () => {
    console.log(`Payment checkout loading started for payment: ${props.payment?.id}`)
  },
  onComplete: () => {
    console.log(`Payment checkout loading completed for amount: ${props.payment?.formatted_amount}`)
  },
  onError: (error) => {
    console.error('Payment checkout loading error:', error)
  }
})

const paymentMethod = ref<string>('sbp')
const isProcessing = ref<boolean>(false)

const processPayment = (): void => {
  if (!paymentMethod.value || isProcessing.value) return
  
  isProcessing.value = true
  
  router.post(route('payment.process', { payment: props.payment.id }), {
    payment_method: paymentMethod.value
  }, {
    onFinish: () => {
      isProcessing.value = false
    }
  })
}

// Инициализация при монтировании
onMounted(() => {
  // Проверяем наличие данных платежа
  if (!props.payment || !props.payment.id) {
    const noDataError = {
      type: 'client' as const,
      message: 'Данные платежа не найдены',
      code: 404
    }
    pageLoader.errorLoading(noDataError)
    return
  }

  // Быстрая загрузка формы оплаты
  setTimeout(() => {
    pageLoader.setProgress(50, 'Инициализируем способы оплаты...')
  }, 100)

  setTimeout(() => {
    pageLoader.setProgress(80, 'Подготавливаем защищенную форму...')
  }, 300)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 600)
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

.payment-methods {
  display: flex;
  gap: 12px;
  margin-bottom: 30px;
}

.payment-method {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #fff;
}

.payment-method:hover {
  border-color: #d1d5db;
}

.payment-method--active {
  border-color: #000;
  background: #f9fafb;
}

.payment-method__icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.payment-method__icon--sbp {
  background: #f3f4f6;
}

.payment-method__icon--wallet {
  background: #eff6ff;
}

.payment-method__icon--card {
  background: #f9fafb;
}

.payment-method__text {
  font-size: 14px;
  font-weight: 500;
  color: #000;
  text-align: center;
}

.payment-instruction {
  text-align: center;
  color: #666;
  font-size: 16px;
  margin-bottom: 30px;
}

.payment-button {
  width: 100%;
  background: #000;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 16px 24px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
  margin-bottom: 20px;
}

.payment-button:hover {
  background: #333;
}

.payment-button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.security-info {
  text-align: center;
  color: #666;
  font-size: 12px;
  line-height: 1.4;
}

.security-link {
  color: #000;
  text-decoration: underline;
}

.security-link:hover {
  text-decoration: none;
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
  
  .payment-methods {
    flex-direction: column;
  }
  
  .payment-method {
    flex-direction: row;
    justify-content: flex-start;
    text-align: left;
  }
}
</style> 