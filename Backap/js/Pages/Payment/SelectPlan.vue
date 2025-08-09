<template>
  <Head title="Выберите срок публикации" />
  
  <AuthLayout>
    <div class="payment-select-plan">
      <div class="container">
        <!-- Кнопка назад -->
        <div class="back-button-wrapper">
          <Link 
            :href="route('my-ads.index')" 
            class="back-button"
          >
            <svg class="back-button__icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="back-button__text">Назад</span>
          </Link>
        </div>

        <!-- Заголовок -->
        <h1 class="page-title">Выберите срок публикации</h1>

        <!-- Тарифные планы -->
        <div class="plans-grid">
          <!-- План 7 дней -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 7 }"
            @click="selectPlan(getPlanByDays(7))"
          >
            <div class="plan-content">
              <h3 class="plan-title">7 дней</h3>
              <div class="plan-price">{{ getPlanByDays(7)?.price || 1235 }} ₽</div>
              <p class="plan-description">
                Если нужно быстро привлечь внимание к своему объявлению
              </p>
            </div>
          </div>

          <!-- План 14 дней -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 14 }"
            @click="selectPlan(getPlanByDays(14))"
          >
            <div class="plan-content">
              <h3 class="plan-title">14 дней</h3>
              <div class="plan-price">{{ getPlanByDays(14)?.price || 253 }} ₽</div>
              <p class="plan-description">
                Когда нужно чуть больше времени
              </p>
            </div>
          </div>

          <!-- План 30 дней (выбран) -->
          <div 
            class="plan-card" 
            :class="{ 'plan-card--active': selectedPlan?.days === 30 }"
            @click="selectPlan(getPlanByDays(30))"
          >
            <div class="plan-content">
              <h3 class="plan-title">30 дней</h3>
              <div class="plan-price">{{ getPlanByDays(30)?.price || 389 }} ₽</div>
              <p class="plan-description">
                Самый популярный вариант
              </p>
            </div>
          </div>
        </div>

        <!-- Информация о контактах -->
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
              Когда объявление соберёт столько контактов, оно опустится в поиске
            </p>

            <button 
              @click="proceedToCheckout"
              :disabled="!selectedPlan"
              class="continue-button"
            >
              Продолжить
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

// По умолчанию выбран план на 30 дней
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

/* Кнопка назад */
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

/* Сетка планов */
.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 50px;
}

/* Карточка плана */
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

/* Бейдж продвижения */
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

/* Информация о контактах */
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

/* Кнопка продолжить */
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

/* Адаптивность */
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