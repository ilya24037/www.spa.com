<template>
  <Head title="РћРїР»Р°С‚Р° РїРѕ РЎР‘Рџ" />
  
  <div class="payment-page">
    <!-- Р›РѕРіРѕС‚РёРї -->
    <div class="logo">
      <span class="logo-text">MASSAGIST</span>
    </div>

    <div class="payment-container">
      <!-- РљРЅРѕРїРєР° РЅР°Р·Р°Рґ -->
      <Link 
        :href="route('payment.checkout', { ad: ad.id })" 
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

      <!-- QR-РєРѕРґ Р±Р»РѕРє -->
      <div class="qr-payment-box">
        <div class="qr-content">
          <div class="qr-text">
            <h2 class="qr-title">
              РџРѕРґС‚РІРµСЂРґРёС‚Рµ РїР»Р°С‚С‘Р¶ РїРѕ РЎР‘Рџ
            </h2>
            <p class="qr-instruction">
              Р”Р»СЏ СЌС‚РѕРіРѕ РѕС‚СЃРєР°РЅРёСЂСѓР№С‚Рµ QR-РєРѕРґ РєР°РјРµСЂРѕР№ РёР»Рё РІ РїСЂРёР»РѕР¶РµРЅРёРё Р±Р°РЅРєР°
            </p>
          </div>
          
          <div class="qr-code">
            <!-- QR-РєРѕРґ -->
            <div class="qr-code__image">
              <svg
                width="200"
                height="200"
                viewBox="0 0 200 200"
                fill="none"
              >
                <!-- РџСЂРѕСЃС‚РѕР№ QR-РєРѕРґ (РІ СЂРµР°Р»СЊРЅРѕРј РїСЂРёР»РѕР¶РµРЅРёРё Р·РґРµСЃСЊ Р±СѓРґРµС‚ РіРµРЅРµСЂРёСЂРѕРІР°С‚СЊСЃСЏ РЅР°СЃС‚РѕСЏС‰РёР№ QR) -->
                <rect width="200" height="200" fill="white" />
                <rect
                  x="10"
                  y="10"
                  width="180"
                  height="180"
                  fill="black"
                />
                <rect
                  x="20"
                  y="20"
                  width="160"
                  height="160"
                  fill="white"
                />
                <rect
                  x="30"
                  y="30"
                  width="140"
                  height="140"
                  fill="black"
                />
                <rect
                  x="40"
                  y="40"
                  width="120"
                  height="120"
                  fill="white"
                />
                <rect
                  x="50"
                  y="50"
                  width="100"
                  height="100"
                  fill="black"
                />
                <rect
                  x="60"
                  y="60"
                  width="80"
                  height="80"
                  fill="white"
                />
                <rect
                  x="70"
                  y="70"
                  width="60"
                  height="60"
                  fill="black"
                />
                <rect
                  x="80"
                  y="80"
                  width="40"
                  height="40"
                  fill="white"
                />
                <rect
                  x="90"
                  y="90"
                  width="20"
                  height="20"
                  fill="black"
                />
                
                <!-- Р›РѕРіРѕС‚РёРї РІ С†РµРЅС‚СЂРµ -->
                <circle
                  cx="100"
                  cy="100"
                  r="15"
                  fill="white"
                />
                <circle
                  cx="100"
                  cy="100"
                  r="12"
                  fill="#4285F4"
                />
                <circle
                  cx="100"
                  cy="100"
                  r="8"
                  fill="#34A853"
                />
                <circle
                  cx="100"
                  cy="100"
                  r="4"
                  fill="#FBBC05"
                />
                <circle
                  cx="100"
                  cy="100"
                  r="2"
                  fill="#EA4335"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ СЃС‚Р°С‚СѓСЃРµ -->
      <div class="payment-status">
        <div class="status-indicator">
          <div class="status-dot" />
          <span class="status-text">РћР¶РёРґР°РЅРёРµ РѕРїР»Р°С‚С‹...</span>
        </div>
      </div>

      <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ -->
      <div class="payment-actions">
        <button 
          :disabled="isChecking"
          class="check-button"
          @click="checkPaymentStatus"
        >
          {{ isChecking ? 'РџСЂРѕРІРµСЂСЏРµРј...' : 'РџСЂРѕРІРµСЂРёС‚СЊ РѕРїР»Р°С‚Сѓ' }}
        </button>
        
        <button 
          class="cancel-button"
          @click="cancelPayment"
        >
          РћС‚РјРµРЅРёС‚СЊ
        </button>
      </div>

      <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РЎР‘Рџ -->
      <div class="sbp-info">
        <p class="sbp-text">
          РЎР‘Рџ вЂ” РЎРёСЃС‚РµРјР° Р±С‹СЃС‚СЂС‹С… РїР»Р°С‚РµР¶РµР№. РћРїР»Р°С‚Р° РїСЂРѕРёСЃС…РѕРґРёС‚ РјРіРЅРѕРІРµРЅРЅРѕ С‡РµСЂРµР· РІР°С€ Р±Р°РЅРє.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
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

// РђРІС‚РѕРјР°С‚РёС‡РµСЃРєР°СЏ РїСЂРѕРІРµСЂРєР° СЃС‚Р°С‚СѓСЃР° РєР°Р¶РґС‹Рµ 5 СЃРµРєСѓРЅРґ
let statusInterval = null

onMounted(() => {
    // Р—Р°РїСѓСЃРєР°РµРј Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєСѓСЋ РїСЂРѕРІРµСЂРєСѓ
    statusInterval = setInterval(checkPaymentStatus, 5000)
})

const checkPaymentStatus = async () => {
    if (isChecking.value) return
  
    isChecking.value = true
  
    try {
    // Р—Р°РїСЂРѕСЃ Рє РЅР°С€РµРјСѓ API РґР»СЏ РїСЂРѕРІРµСЂРєРё СЃС‚Р°С‚СѓСЃР°
        const response = await fetch(route('payment.check-status', { payment: props.payment.id }))
        const data = await response.json()
    
        if (data.status === 'completed') {
            paymentStatus.value = 'completed'
            clearInterval(statusInterval)
      
            // РџРµСЂРµРЅР°РїСЂР°РІР»СЏРµРј РЅР° СЃС‚СЂР°РЅРёС†Сѓ СѓСЃРїРµС…Р°
            router.visit(route('payment.success', { payment: props.payment.id }))
        }
    } catch (_error) {
        // Ошибка обработки платежа
    } finally {
        isChecking.value = false
    }
}

const cancelPayment = () => {
    clearInterval(statusInterval)
    router.visit(route('payment.checkout', { ad: props.ad.id }))
}

// РћС‡РёС‰Р°РµРј РёРЅС‚РµСЂРІР°Р» РїСЂРё СЂР°Р·РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
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
