<template>
  <Head title="Р’С‹Р±РµСЂРёС‚Рµ СЃСЂРѕРє РїСѓР±Р»РёРєР°С†РёРё" />
  
  <AuthLayout>
    <div class="payment-select-plan">
      <div class="container">
        <!-- РљРЅРѕРїРєР° РЅР°Р·Р°Рґ -->
        <div class="back-button-wrapper">
          <Link 
            :href="route('my-ads.index')" 
            class="back-button"
          >
            <svg class="back-button__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="back-button__text">РќР°Р·Р°Рґ</span>
          </Link>
        </div>

        <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
        <h1 class="page-title">Р’С‹Р±РµСЂРёС‚Рµ СЃСЂРѕРє РїСѓР±Р»РёРєР°С†РёРё</h1>

        <!-- РўР°СЂРёС„РЅС‹Рµ РїР»Р°РЅС‹ -->
        <div class="plans-grid">
          <!-- РџР»Р°РЅ 7 РґРЅРµР№ -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 7 }"
            @click="selectPlan(getPlanByDays(7))"
          >
            <div class="plan-content">
              <h3 class="plan-title">7 РґРЅРµР№</h3>
              <div class="plan-price">{{ getPlanByDays(7)?.price || 1235 }} в‚Ѕ</div>
              <p class="plan-description">
                Р•СЃР»Рё РЅСѓР¶РЅРѕ Р±С‹СЃС‚СЂРѕ РїСЂРёРІР»РµС‡СЊ РІРЅРёРјР°РЅРёРµ Рє СЃРІРѕРµРјСѓ РѕР±СЉСЏРІР»РµРЅРёСЋ
              </p>
            </div>
          </div>

          <!-- РџР»Р°РЅ 14 РґРЅРµР№ -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 14 }"
            @click="selectPlan(getPlanByDays(14))"
          >
            <div class="plan-content">
              <h3 class="plan-title">14 РґРЅРµР№</h3>
              <div class="plan-price">{{ getPlanByDays(14)?.price || 253 }} в‚Ѕ</div>
              <p class="plan-description">
                РљРѕРіРґР° РЅСѓР¶РЅРѕ С‡СѓС‚СЊ Р±РѕР»СЊС€Рµ РІСЂРµРјРµРЅРё
              </p>
            </div>
          </div>

          <!-- РџР»Р°РЅ 30 РґРЅРµР№ (РІС‹Р±СЂР°РЅ) -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 30 }"
            @click="selectPlan(getPlanByDays(30))"
          >
            <div class="plan-content">
              <h3 class="plan-title">30 РґРЅРµР№</h3>
              <div class="plan-price">{{ getPlanByDays(30)?.price || 389 }} в‚Ѕ</div>
              <p class="plan-description">
                РЎР°РјС‹Р№ РїРѕРїСѓР»СЏСЂРЅС‹Р№ РІР°СЂРёР°РЅС‚
              </p>
            </div>
          </div>
        </div>

        <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РєРѕРЅС‚Р°РєС‚Р°С… -->
        <div class="contacts-info">
          <div class="contacts-info__content">
            <div class="contacts-circle">
              <svg class="contacts-circle__svg" width="52" height="52" viewBox="0 0 52 52" fill="none">
                <circle cx="26" cy="26" r="23.5" stroke="#e8e8e8" stroke-width="5" fill="none"/>
                <circle cx="26" cy="26" r="23.5" 
                        stroke="#04e061" 
                        stroke-width="5" 
                        fill="none"
                        stroke-dasharray="22.148228207808042 125.50662651091224"
                        transform="rotate(-90 26 26)"/>
              </svg>
              <div class="contacts-circle__number">17</div>
            </div>
            
            <p class="contacts-text">
              РљРѕРіРґР° РѕР±СЉСЏРІР»РµРЅРёРµ СЃРѕР±РµСЂС‘С‚ СЃС‚РѕР»СЊРєРѕ РєРѕРЅС‚Р°РєС‚РѕРІ, РѕРЅРѕ РѕРїСѓСЃС‚РёС‚СЃСЏ РІ РїРѕРёСЃРєРµ
            </p>

            <button 
              @click="proceedToCheckout"
              :disabled="!selectedPlan"
              class="continue-button"
            >
              РџСЂРѕРґРѕР»Р¶РёС‚СЊ
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { route } from 'ziggy-js'

const props = defineProps({
  ad: Object,
  plans: Array
})

// РџРѕ СѓРјРѕР»С‡Р°РЅРёСЋ РІС‹Р±СЂР°РЅ РїР»Р°РЅ РЅР° 30 РґРЅРµР№
const selectedPlan = ref(props.plans.find(p => p.days === 30))

const getPlanByDays = (days) => {
  return props.plans.find(p => p.days === days)
}

const selectPlan = (plan) => {
  if (plan) {
    selectedPlan.value = plan
  }
}

const proceedToCheckout = () => {
  if (!selectedPlan.value) return
  
  router.post(route('payment.checkout', { ad: props.ad.id }), {
    plan_id: selectedPlan.value.id
  })
}
</script>

<style scoped>
.payment-select-plan {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 40px 0;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 20px;
}

/* РљРЅРѕРїРєР° РЅР°Р·Р°Рґ */
.back-button-wrapper {
  margin-bottom: 30px;
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #666;
  text-decoration: none;
  font-size: 16px;
  font-weight: 500;
  transition: color 0.2s ease;
}

.back-button:hover {
  color: #000;
}

.back-button__icon {
  width: 20px;
  height: 20px;
}

.back-button__text {
  font-size: 16px;
}

.page-title {
  font-size: 40px;
  font-weight: 700;
  color: #000;
  margin: 0 0 40px;
  text-align: center;
  line-height: 1.2;
}

/* РЎРµС‚РєР° РїР»Р°РЅРѕРІ */
.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 50px;
}

/* РљР°СЂС‚РѕС‡РєР° РїР»Р°РЅР° */
.plan-card {
  position: relative;
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
  min-height: 200px;
}

.plan-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.plan-card--active {
  border-color: #000;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.plan-content {
  text-align: center;
}

.plan-title {
  font-size: 28px;
  font-weight: 700;
  color: #000;
  margin: 0 0 8px;
}

.plan-price {
  font-size: 24px;
  font-weight: 500;
  color: #000;
  margin: 0 0 16px;
}

.plan-description {
  font-size: 14px;
  color: #666;
  line-height: 1.4;
  margin: 0;
}

/* Р‘РµР№РґР¶ РїСЂРѕРґРІРёР¶РµРЅРёСЏ */
.promo-badge {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  background: #a8a2fc;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  height: 28px;
  padding: 0 16px;
  white-space: nowrap;
}

.promo-badge__corner {
  position: absolute;
  top: 0;
  color: #a8a2fc;
}

.promo-badge__corner--left {
  left: -13px;
}

.promo-badge__corner--right {
  right: -12px;
}

.promo-badge__text {
  position: relative;
  z-index: 1;
}

/* РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РєРѕРЅС‚Р°РєС‚Р°С… */
.contacts-info {
  background: #fff;
  border-radius: 12px;
  padding: 40px;
}

.contacts-info__content {
  display: flex;
  align-items: center;
  gap: 30px;
}

.contacts-circle {
  position: relative;
  width: 52px;
  height: 52px;
  flex-shrink: 0;
}

.contacts-circle__svg {
  width: 52px;
  height: 52px;
}

.contacts-circle__number {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 20px;
  font-weight: 700;
  color: #000;
}

.contacts-text {
  flex: 1;
  font-size: 18px;
  color: #000;
  line-height: 1.4;
  margin: 0;
}

/* РљРЅРѕРїРєР° РїСЂРѕРґРѕР»Р¶РёС‚СЊ */
.continue-button {
  background: #000;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 14px 32px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.continue-button:hover {
  background: #333;
}

.continue-button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .page-title {
    font-size: 28px;
    margin-bottom: 30px;
  }
  
  .plans-grid {
    grid-template-columns: 1fr;
  }
  
  .contacts-info__content {
    flex-direction: column;
    text-align: center;
  }
  
  .contacts-text {
    text-align: center;
  }
}
</style> 
