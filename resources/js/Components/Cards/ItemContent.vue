<template>
  <div class="item-content-section">
    <!-- Заголовок -->
    <h4 class="item-title">
      <a :href="itemUrl" class="item-title-link">
        {{ item.title || item.name }}
      </a>
    </h4>

    <!-- Цена -->
    <div class="item-price-section">
      <p class="item-price">
        <span v-if="item.price_from" class="price-value">
          от {{ formatPrice(item.price_from) }} ₽ за час
        </span>
        <span v-else class="price-negotiable">Цена договорная</span>
      </p>
    </div>

    <!-- Остальная информация только для НЕ черновиков -->
    <template v-if="item.status !== 'draft'">
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

      <!-- Адрес -->
      <div class="item-location">
        <p class="location-address">{{ item.address || item.city }}</p>
        <p class="location-district">{{ item.district || 'Центральный район' }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  itemUrl: {
    type: String,
    required: true
  }
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price)
}
</script>

<style scoped>
.item-content-section {
  @apply flex-1 px-4;
}

.item-title {
  @apply mb-2;
}

.item-title-link {
  @apply text-lg font-medium text-gray-900 hover:text-blue-600 transition-colors;
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
  @apply text-sm text-gray-600;
}

.location-address {
  @apply mb-1;
}

.location-district {
  @apply text-gray-500;
}
</style>