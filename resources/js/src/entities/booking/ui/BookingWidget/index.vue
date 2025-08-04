<!-- resources/js/Components/Masters/BookingWidget/index.vue -->
<template>
  <div class="booking-widget p-6">
    <!-- Блок с ценой -->
    <div class="mb-6">
      <div class="flex items-baseline justify-between mb-2">
        <span class="text-3xl font-bold text-gray-900">
          от {{ formatPrice(master.price_from) }} ₽
        </span>
        <span class="text-gray-500 text-sm">за сеанс</span>
      </div>
      <div v-if="master.price_to" class="text-sm text-gray-600">
        до {{ formatPrice(master.price_to) }} ₽
      </div>
    </div>
    
    <!-- Кнопки действий -->
    <BookingActions
      :phone="master.phone"
      :whatsapp="master.whatsapp"
      :telegram="master.telegram"
      :is-available="master.is_available_now"
      @book="$emit('open-booking')"
      @call="handleCall"
      class="mb-6"
    />
    
    <!-- График работы -->
    <WorkSchedule 
      :schedule="master.schedule"
      :schedule-description="master.schedule_description"
      @show-calendar="$emit('open-booking')"
    />
  </div>
</template>

<script setup>
import BookingActions from './BookingActions.vue'
import WorkSchedule from './WorkSchedule.vue'

const props = defineProps({
  master: {
    type: Object,
    required: true
  },
  selectedService: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['open-booking', 'share'])

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price || 0)
}

const handleCall = () => {
}
</script>