<!-- resources/js/Components/Masters/BookingWidget/index.vue -->
<template>
  <div class="booking-widget p-6">
    <!-- Р‘Р»РѕРє СЃ С†РµРЅРѕР№ -->
    <div class="mb-6">
      <div class="flex items-baseline justify-between mb-2">
        <span class="text-3xl font-bold text-gray-900">
          РѕС‚ {{ formatPrice(master.price_from) }} в‚Ѕ
        </span>
        <span class="text-gray-500 text-sm">Р·Р° СЃРµР°РЅСЃ</span>
      </div>
      <div v-if="master.price_to" class="text-sm text-gray-600">
        РґРѕ {{ formatPrice(master.price_to) }} в‚Ѕ
      </div>
    </div>
    
    <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ -->
    <BookingActions
      :phone="master.phone"
      :whatsapp="master.whatsapp"
      :telegram="master.telegram"
      :is-available="master.is_available_now"
      @book="$emit('open-booking')"
      @call="handleCall"
      class="mb-6"
    />
    
    <!-- Р“СЂР°С„РёРє СЂР°Р±РѕС‚С‹ -->
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

