import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useBookingStore = defineStore('booking', () => {
  const bookings = ref([])
  const loading = ref(false)
  
  return {
    bookings,
    loading
  }
})
