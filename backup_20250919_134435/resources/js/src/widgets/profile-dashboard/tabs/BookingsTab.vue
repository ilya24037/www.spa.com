<!-- resources/js/src/widgets/profile-dashboard/tabs/BookingsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div :class="EMPTY_STATE_CLASSES">
      <CalendarIcon :class="EMPTY_ICON_CLASSES" />
      <h3 :class="EMPTY_TITLE_CLASSES">
        Нет бронирований
      </h3>
      <p :class="EMPTY_DESCRIPTION_CLASSES">
        Здесь будут отображаться ваши бронирования
      </p>
    </div>
  </div>
</template>

<script setup>
import { CalendarIcon } from '@heroicons/vue/outline'

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-500 mb-4'
const EMPTY_TITLE_CLASSES = 'text-lg font-medium text-gray-500 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-500'

defineProps({
    bookings: {
        type: Array,
        default: () => []
    },
    counts: {
        type: Object,
        default: () => ({})
    }
})
</script>