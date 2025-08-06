<!-- resources/js/Components/Masters/BookingWidget/PriceCalculator.vue -->
<template>
  <div class="price-block">
    <div class="text-3xl font-bold text-gray-900">
      {{ formatPrice(displayPrice) }} в‚Ѕ
    </div>
    <div v-if="discount" class="text-sm text-green-600">
      РЎРєРёРґРєР° {{ discount }}%
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  priceFrom: Number,
  priceTo: Number,
  selectedService: Object,
  discount: Number
})

const displayPrice = computed(() => {
  if (props.selectedService) {
    return props.selectedService.price
  }
  return props.priceFrom
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price || 0)
}
</script>
