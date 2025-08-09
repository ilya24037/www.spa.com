<template>
  <Head title="РћРїР»Р°С‚Р° СѓСЃР»СѓРі" />
  
  <div class="payment-page">
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="form"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
    <template v-else>
      <!-- Р›РѕРіРѕС‚РёРї -->
      <div class="logo">
        <span class="logo-text">MASSAGIST</span>
      </div>

      <div class="payment-container">
        <!-- РљРЅРѕРїРєР° РЅР°Р·Р°Рґ -->
        <Link 
          :href="route('payment.select-plan', { ad: ad.id })" 
          class="back-link"
        >
          в†ђ РќР°Р·Р°Рґ
        </Link>

        <!-- Р—Р°РіРѕР»РѕРІРѕРє Рё СЃСѓРјРјР° -->
        <div class="payment-header">
          <h1 class="payment-title">
            РћРїР»Р°С‚Р° СѓСЃР»СѓРі
          </h1>
          <div class="payment-amount">
            {{ payment.formatted_amount }}
          </div>
        </div>

        <!-- Р’С‹Р±РѕСЂ СЃРїРѕСЃРѕР±Р° РѕРїР»Р°С‚С‹ -->
        <div class="payment-methods">
          <!-- РЎР‘Рџ -->
          <div 
            class="payment-method"
            :class="{ 'payment-method--active': paymentMethod === 'sbp' }"
            @click="paymentMethod = 'sbp'"
          >
            <div class="payment-method__icon payment-method__icon--sbp">
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#8B5CF6" />
                <path d="M2 17L12 22L22 17" stroke="#8B5CF6" stroke-width="2" />
                <path d="M2 12L12 17L22 12" stroke="#8B5CF6" stroke-width="2" />
              </svg>
            </div>
            <span class="payment-method__text">РЎР‘Рџ</span>
          </div>

          <!-- РљРѕС€РµР»С‘Рє -->
          <div 
            class="payment-method"
            :class="{ 'payment-method--active': paymentMethod === 'wallet' }"
            @click="paymentMethod = 'wallet'"
          >
            <div class="payment-method__icon payment-method__icon--wallet">
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path d="M3 10H21M7 15H9M15 15H17M3 6H21C22.1046 6 23 6.89543 23 8V18C23 19.1046 22.1046 20 21 20H3C1.89543 20 1 19.1046 1 18V8C1 6.89543 1.89543 6 3 6Z" stroke="#3B82F6" stroke-width="2" />
              </svg>
            </div>
            <span class="payment-method__text">РљРѕС€РµР»С‘Рє</span>
          </div>

          <!-- РќРѕРІР°СЏ РєР°СЂС‚Р° -->
          <div 
            class="payment-method"
            :class="{ 'payment-method--active': paymentMethod === 'card' }"
            @click="paymentMethod = 'card'"
          >
            <div class="payment-method__icon payment-method__icon--card">
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path d="M12 5V19M5 12H19" stroke="#6B7280" stroke-width="2" />
              </svg>
            </div>
            <span class="payment-method__text">РќРѕРІР°СЏ РєР°СЂС‚Р°</span>
          </div>
        </div>

        <!-- РРЅСЃС‚СЂСѓРєС†РёСЏ -->
        <div class="payment-instruction">
          Р”Р°Р»СЊС€Рµ Р±СѓРґРµС‚ QR РґР»СЏ РѕРїР»Р°С‚С‹
        </div>

        <!-- РљРЅРѕРїРєР° РѕРїР»Р°С‚С‹ -->
        <button 
          :disabled="!paymentMethod || isProcessing"
          class="payment-button"
          @click="processPayment"
        >
          {{ isProcessing ? 'РћР±СЂР°Р±РѕС‚РєР°...' : 'РџРµСЂРµР№С‚Рё Рє РѕРїР»Р°С‚Рµ' }}
        </button>

        <!-- РџРѕР»РёС‚РёРєР° Р±РµР·РѕРїР°СЃРЅРѕСЃС‚Рё -->
        <div class="security-info">
          РРЅС‚РµСЂРЅРµС‚-РїР»Р°С‚РµР¶Рё Р·Р°С‰РёС‰РµРЅС‹ СЃРµСЂС‚РёС„РёРєР°С‚РѕРј SSL Рё РїСЂРѕС‚РѕРєРѕР»РѕРј 3D Secure. 
          <Link href="#" class="security-link">
            РџРѕР»РёС‚РёРєР° РєРѕРЅС„РёРґРµРЅС†РёР°Р»СЊРЅРѕСЃС‚Рё
          </Link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// РўРёРїРёР·Р°С†РёСЏ props
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

// РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РіСЂСѓР·РєРѕР№ СЃС‚СЂР°РЅРёС†С‹
const pageLoader = usePageLoading({
    type: 'form',
    autoStart: true,
    timeout: 5000,
    onStart: () => {
    // Payment checkout loading started
    },
    onComplete: () => {
    // Payment checkout loading completed
    },
    onError: (error) => {
        logger.error('Payment checkout loading error:', error)
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

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onMounted(() => {
    // РџСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ РґР°РЅРЅС‹С… РїР»Р°С‚РµР¶Р°
    if (!props.payment || !props.payment.id) {
        const noDataError = {
            type: 'client' as const,
            message: 'Р”Р°РЅРЅС‹Рµ РїР»Р°С‚РµР¶Р° РЅРµ РЅР°Р№РґРµРЅС‹',
            code: 404
        }
        pageLoader.errorLoading(noDataError)
        return
    }

    // Р‘С‹СЃС‚СЂР°СЏ Р·Р°РіСЂСѓР·РєР° С„РѕСЂРјС‹ РѕРїР»Р°С‚С‹
    setTimeout(() => {
        pageLoader.setProgress(50, 'РРЅРёС†РёР°Р»РёР·РёСЂСѓРµРј СЃРїРѕСЃРѕР±С‹ РѕРїР»Р°С‚С‹...')
    }, 100)

    setTimeout(() => {
        pageLoader.setProgress(80, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј Р·Р°С‰РёС‰РµРЅРЅСѓСЋ С„РѕСЂРјСѓ...')
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

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
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
