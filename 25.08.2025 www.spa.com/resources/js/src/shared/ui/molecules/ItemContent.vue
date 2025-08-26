<template>
  <div class="item-content-section">
    <!-- Заголовок -->
    <h4 class="item-title">
      <span class="item-title-text">
        {{ item.title || item.name }}
      </span>
    </h4>

    <!-- Цена -->
    <div class="item-price-section">
      <p class="item-price">
        <span v-if="getMinPrice()" class="price-value">
          от {{ formatPrice(getMinPrice()) }} ₽ за час
        </span>
        <span v-else class="price-negotiable">Цена договорная</span>
      </p>
    </div>

    <!-- Адрес (для всех статусов) -->
    <div v-if="item.address" class="item-location">
      <p class="location-address">{{ item.address }}</p>
    </div>

    <!-- Для черновиков показываем упрощенную информацию -->
    <template v-if="item.status === 'draft'">
      <!-- Услуга -->
      <div class="item-service-info">
        <p class="service-type">{{ item.company_name || 'Массажный салон' }}</p>
      </div>
    </template>
    
    <!-- Для остальных статусов (включая waiting_payment) показываем полную информацию -->
    <template v-else>
      <!-- Описание -->
      <div v-if="item.description" class="item-description">
        <p class="description-text">{{ item.description }}</p>
      </div>

      <!-- Доступность -->
      <div class="item-stock">
        <p class="stock-text">Доступен для записи</p>
      </div>

      <!-- Доставка/Выезд -->
      <div v-if="item.home_service" class="item-delivery">
        <div class="delivery-icon">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <p class="delivery-text">Выезд к клиенту</p>
      </div>

      <!-- Название компании/мастера -->
      <div class="item-company">
        <p class="company-name">{{ item.company_name || 'Массажный салон' }}</p>
      </div>

    </template>
  </div>
</template>

<script setup lang="ts">
interface Props {
  item: {
    title?: string
    name?: string
    price_from?: number
    prices?: {
      apartments_express?: number
      apartments_1h?: number
      apartments_2h?: number
      apartments_night?: number
      outcall_1h?: number
      outcall_2h?: number
      outcall_night?: number
      taxi_included?: boolean
    }
    status?: string
    description?: string
    home_service?: boolean
    company_name?: string
    address?: string
  }
  itemUrl: string
}

const props = defineProps<Props>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

// Берем цену за час как в черновике
const getMinPrice = () => {
  // Для черновиков используем часовую цену из prices
  if (props.item.prices) {
    const hourPrice = props.item.prices.apartments_1h || props.item.prices.outcall_1h
    if (hourPrice) {
      return hourPrice
    }
  }
  
  // Резервный вариант - используем price_from
  if (props.item.price_from) {
    return props.item.price_from
  }
  
  return null
}
</script>

<style scoped>
.item-content-section {
  @apply flex-1 px-4;
}

.item-title {
  @apply mb-2;
}

.item-title-text {
  @apply text-lg font-medium text-gray-900;
  /* Для черновиков - многострочный заголовок */
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-wrap: break-word;
}

.item-description {
  @apply mb-3;
}

.description-text {
  @apply text-sm text-gray-600 line-clamp-2;
}

.item-price-section {
  @apply mb-2;
}

.item-price {
  @apply text-lg font-normal text-gray-900;
  /* Цена как на скрине - обычный шрифт */
}

.price-negotiable {
  @apply text-gray-600;
}

.price-currency {
  @apply ml-1;
}

.item-stock {
  @apply mb-2;
}

.stock-text {
  @apply text-sm text-green-600;
}

.item-delivery {
  @apply flex items-center gap-2 mb-2;
}

/* Стили для черновиков */
.item-service-info {
  @apply text-sm text-gray-600 mt-2;
}

.service-type {
  @apply font-medium text-gray-900 mb-1;
}

.service-location {
  @apply text-gray-600;
}

.service-district {
  @apply text-gray-500 text-xs;
}

.delivery-icon {
  @apply text-blue-600;
}

.delivery-text {
  @apply text-sm text-gray-600;
}

.item-company {
  @apply mb-1;
}

.company-name {
  @apply text-sm font-medium text-gray-900;
}

.item-location {
  @apply text-sm text-gray-600 mb-2;
}

.location-address {
  @apply mb-1;
}
</style>