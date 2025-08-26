<template>
  <Head title="Выбор плана" />
  
  <div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Заголовок -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-500 mb-4">
        Выберите план размещения
      </h1>
      <p class="text-lg text-gray-500">
        Выберите подходящий план для вашего объявления
      </p>
    </div>

    <!-- Планы -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div v-for="plan in plans" :key="plan.id" class="bg-white border border-gray-500 rounded-lg p-6 hover:shadow-lg transition-shadow">
        <!-- Заголовок плана -->
        <div class="text-center mb-6">
          <h3 class="text-xl font-semibold text-gray-500 mb-2">
            {{ plan.name }}
          </h3>
          <div class="text-3xl font-bold text-blue-600 mb-1">
            {{ plan.price }} ₽
          </div>
          <div class="text-sm text-gray-500">
            {{ plan.duration }}
          </div>
        </div>

        <!-- Особенности -->
        <ul class="space-y-3 mb-6">
          <li v-for="feature in plan.features" :key="feature" class="flex items-center text-sm text-gray-500">
            <svg
              class="w-4 h-4 text-green-500 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            {{ feature }}
          </li>
        </ul>

        <!-- Кнопка выбора -->
        <button 
          class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors"
          @click="selectPlan(plan)"
        >
          Выбрать план
        </button>
      </div>
    </div>

    <!-- Информация -->
    <div class="mt-8 text-center">
      <p class="text-sm text-gray-500">
        Все планы включают базовое размещение объявления. 
        Дополнительные услуги можно заказать отдельно.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

interface Plan {
  id: number
  name: string
  price: number
  duration: string
  features: string[]
}

const props = defineProps<{
  plans: Plan[]
  adId?: number
}>()

const selectPlan = (plan: any) => {
    router.post('/payment/process', {
        plan_id: plan.id,
        ad_id: props.adId
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
