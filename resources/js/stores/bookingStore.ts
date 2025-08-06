import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios, { type AxiosResponse } from 'axios'

// =================== TYPES ===================
export interface BookingData {
  masterId: number
  serviceId: number
  date: string
  time: string
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail?: string
  address?: string
  addressDetails?: string
  comment?: string
  paymentMethod: 'cash' | 'card'
}

export interface BookingResult {
  id: number
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
  master_profile_id: number
  service_id: number
  booking_date: string
  booking_time: string
  service_location: 'home' | 'salon'
  client_name: string
  client_phone: string
  client_email?: string
  address?: string
  client_comment?: string
  payment_method: 'cash' | 'card'
  payment_status?: 'pending' | 'paid' | 'failed'
  total_price?: number
  created_at: string
  updated_at: string
  cancelled_at?: string
  confirmed_at?: string
  paid_at?: string
}

export interface TimeSlot {
  time: string
  available: boolean
  price?: number
}

export interface BookingSlots {
  [date: string]: TimeSlot[]
}

interface BookingState {
  masterId: number | null
  serviceId: number | null
  date: string | null
  time: string | null
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail: string
  address: string
  comment: string
  paymentMethod: 'cash' | 'card'
}

export interface BookingStats {
  total: number
  pending: number
  confirmed: number
  completed: number
  cancelled: number
}

interface ApiError {
  response?: {
    status?: number
    data?: {
      message?: string
      errors?: Record<string, string[]>
    }
  }
}

// =================== STORE ===================
export const useBookingStore = defineStore('booking', () => {
  
  // =================== РЎРћРЎРўРћРЇРќРР• ===================
  
  const bookings: import("vue").Ref<BookingResult[]> = ref([])
  const currentBooking: import("vue").Ref<BookingState> = ref({
    masterId: null,
    serviceId: null,
    date: null,
    time: null,
    locationType: 'home', // 'home' РёР»Рё 'salon'
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    address: '',
    comment: '',
    paymentMethod: 'cash'
  })
  
  const availableSlots: import("vue").Ref<BookingSlots> = ref({})
  const isLoading: import("vue").Ref<boolean> = ref(false)
  const error: import("vue").Ref<string | null> = ref(null)
  const lastBooking: import("vue").Ref<BookingResult | null> = ref(null)
  
  // =================== Р’Р«Р§РРЎР›РЇР•РњР«Р• ===================
  
  const isFormValid = computed((): boolean => {
    return Boolean(
      currentBooking.value.masterId &&
      currentBooking.value.serviceId &&
      currentBooking.value.date &&
      currentBooking.value.time &&
      currentBooking.value.clientName &&
      currentBooking.value.clientPhone
    )
  })
  
  const totalBookings = computed((): number => bookings.value.length)
  
  const pendingBookings = computed((): BookingResult[] => 
    bookings.value.filter(b => b.status === 'pending')
  )
  
  const confirmedBookings = computed((): BookingResult[] => 
    bookings.value.filter(b => b.status === 'confirmed')
  )
  
  // =================== Р”Р•Р™РЎРўР’РРЇ ===================
  
  // рџ“‹ РџРѕР»СѓС‡РёС‚СЊ СЃРїРёСЃРѕРє Р±СЂРѕРЅРёСЂРѕРІР°РЅРёР№
  async function fetchBookings(): Promise<BookingResult[]> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<{ data?: BookingResult[] } | BookingResult[]> = await axios.get('/api/bookings', {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      bookings.value = (response.data as any).data || response.data as BookingResult[]
      
      return bookings.value
    } catch (err: unknown) {
      const error = err as ApiError
      
      if (error.response?.status === 401) {
        setError('РќРµРѕР±С…РѕРґРёРјРѕ РІРѕР№С‚Рё РІ СЃРёСЃС‚РµРјСѓ')
      } else {
        setError('РќРµ СѓРґР°Р»РѕСЃСЊ Р·Р°РіСЂСѓР·РёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ')
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // рџ“… РџРѕР»СѓС‡РёС‚СЊ РґРѕСЃС‚СѓРїРЅС‹Рµ СЃР»РѕС‚С‹
  async function fetchAvailableSlots(
    masterId: number, 
    serviceId: number, 
    date: string | null = null
  ): Promise<BookingSlots> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const params: Record<string, string | number> = {
        master_profile_id: masterId,
        service_id: serviceId
      }
      
      if (date) {
        params.date = date
      }
      
      const response: AxiosResponse<{ slots: TimeSlot[] | BookingSlots }> = await axios.get('/api/bookings/available-slots', {
        params,
        headers: {
          'Accept': 'application/json'
        }
      })
      
      if (date) {
        // РЎР»РѕС‚С‹ РґР»СЏ РєРѕРЅРєСЂРµС‚РЅРѕР№ РґР°С‚С‹
        availableSlots.value[date] = response.data.slots as TimeSlot[]
      } else {
        // РЎР»РѕС‚С‹ РЅР° РЅРµСЃРєРѕР»СЊРєРѕ РґРЅРµР№
        availableSlots.value = response.data.slots as BookingSlots
      }
      
      return availableSlots.value
    } catch (err: unknown) {
      const error = err as ApiError
      setError('РќРµ СѓРґР°Р»РѕСЃСЊ РїРѕР»СѓС‡РёС‚СЊ РґРѕСЃС‚СѓРїРЅС‹Рµ СЃР»РѕС‚С‹')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // рџ“ќ РЎРѕР·РґР°С‚СЊ РЅРѕРІРѕРµ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ (РћР‘РќРћР’Р›Р•РќРќР«Р™ РњР•РўРћР”)
  async function createBooking(bookingData: BookingData): Promise<BookingResult> {
    
    isLoading.value = true
    error.value = null
    
    try {
      // РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РґР°РЅРЅС‹Рµ РІ РїСЂР°РІРёР»СЊРЅРѕРј С„РѕСЂРјР°С‚Рµ
      const dataToSend = {
        master_profile_id: bookingData.masterId,
        service_id: bookingData.serviceId,
        booking_date: bookingData.date,
        booking_time: bookingData.time,
        service_location: bookingData.locationType,
        client_name: bookingData.clientName,
        client_phone: bookingData.clientPhone,
        client_email: bookingData.clientEmail,
        address: bookingData.address,
        address_details: bookingData.addressDetails,
        client_comment: bookingData.comment,
        payment_method: bookingData.paymentMethod
      }
      
      // РћС‚РїСЂР°РІР»СЏРµРј РЅР° РЅРѕРІС‹Р№ API endpoint
      const response: AxiosResponse<{ booking: BookingResult }> = await axios.post('/api/bookings', dataToSend, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      
      // РЎРѕС…СЂР°РЅСЏРµРј СЂРµР·СѓР»СЊС‚Р°С‚
      lastBooking.value = response.data.booking
      
      // Р”РѕР±Р°РІР»СЏРµРј РІ Р»РѕРєР°Р»СЊРЅС‹Р№ СЃРїРёСЃРѕРє
      bookings.value.unshift(response.data.booking)
      
      // РћС‡РёС‰Р°РµРј С„РѕСЂРјСѓ
      resetCurrentBooking()
      
      return response.data.booking
    } catch (err: unknown) {
      const error = err as ApiError
      
      // РћР±СЂР°Р±РѕС‚РєР° РѕС€РёР±РѕРє РІР°Р»РёРґР°С†РёРё
      if (error.response?.status === 422) {
        const validationErrors = error.response.data?.errors || {}
        const errorMessage = Object.values(validationErrors).flat().join(', ') || error.response.data?.message
        setError(errorMessage || 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё')
        throw validationErrors
      }
      
      // РћР±СЂР°Р±РѕС‚РєР° РґСЂСѓРіРёС… РѕС€РёР±РѕРє
      if (error.response?.status === 401) {
        setError('РќРµРѕР±С…РѕРґРёРјРѕ РІРѕР№С‚Рё РІ СЃРёСЃС‚РµРјСѓ')
      } else {
        setError(error.response?.data?.message || 'РќРµ СѓРґР°Р»РѕСЃСЊ СЃРѕР·РґР°С‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ')
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // вќЊ РћС‚РјРµРЅРёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ
  async function cancelBooking(bookingId: number, reason: string | null = null): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/cancel`, 
        { reason },
        {
          headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
          }
        }
      )
      
      // РћР±РЅРѕРІР»СЏРµРј СЃС‚Р°С‚СѓСЃ РІ Р»РѕРєР°Р»СЊРЅРѕРј СЃРїРёСЃРєРµ
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'cancelled'
        booking.cancelled_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕС‚РјРµРЅРёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // вњ… РџРѕРґС‚РІРµСЂРґРёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ (РґР»СЏ РјР°СЃС‚РµСЂРѕРІ)
  async function confirmBooking(bookingId: number): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/confirm`, {}, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      // РћР±РЅРѕРІР»СЏРµРј СЃС‚Р°С‚СѓСЃ РІ Р»РѕРєР°Р»СЊРЅРѕРј СЃРїРёСЃРєРµ
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'confirmed'
        booking.confirmed_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'РќРµ СѓРґР°Р»РѕСЃСЊ РїРѕРґС‚РІРµСЂРґРёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // рџЏЃ Р—Р°РІРµСЂС€РёС‚СЊ СѓСЃР»СѓРіСѓ (РґР»СЏ РјР°СЃС‚РµСЂРѕРІ)
  async function completeBooking(bookingId: number): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/complete`, {}, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      // РћР±РЅРѕРІР»СЏРµРј СЃС‚Р°С‚СѓСЃ РІ Р»РѕРєР°Р»СЊРЅРѕРј СЃРїРёСЃРєРµ
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'completed'
        booking.payment_status = 'paid'
        booking.paid_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'РќРµ СѓРґР°Р»РѕСЃСЊ Р·Р°РІРµСЂС€РёС‚СЊ СѓСЃР»СѓРіСѓ')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // =================== Р’РЎРџРћРњРћР“РђРўР•Р›Р¬РќР«Р• РњР•РўРћР”Р« ===================
  
  // рџ”„ РЎР±СЂРѕСЃРёС‚СЊ С‚РµРєСѓС‰РµРµ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ
  function resetCurrentBooking(): void {
    currentBooking.value = {
      masterId: null,
      serviceId: null,
      date: null,
      time: null,
      locationType: 'home',
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      address: '',
      comment: '',
      paymentMethod: 'cash'
    }
  }
  
  // рџ“ќ РћР±РЅРѕРІРёС‚СЊ РґР°РЅРЅС‹Рµ С„РѕСЂРјС‹
  function updateBookingData(data: Partial<BookingState>): void {
    Object.assign(currentBooking.value, data)
  }
  
  // рџ”‘ РџРѕР»СѓС‡РёС‚СЊ С‚РѕРєРµРЅ Р°РІС‚РѕСЂРёР·Р°С†РёРё
  function getAuthToken(): string {
    // Р”Р»СЏ Laravel Sanctum С‚РѕРєРµРЅ РјРѕР¶РµС‚ Р±С‹С‚СЊ РІ cookie РёР»Рё localStorage
    // Р’ РґР°РЅРЅРѕРј СЃР»СѓС‡Р°Рµ РёСЃРїРѕР»СЊР·СѓРµРј CSRF С‚РѕРєРµРЅ
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token || ''
  }
  
  // рџ§№ РћС‡РёСЃС‚РёС‚СЊ РѕС€РёР±РєРё
  function clearError(): void {
    error.value = null
  }
  
  // вљ пёЏ РЈСЃС‚Р°РЅРѕРІРёС‚СЊ РѕС€РёР±РєСѓ
  function setError(message: string): void {
    error.value = message
  }
  
  // рџ“Љ РџРѕР»СѓС‡РёС‚СЊ СЃС‚Р°С‚РёСЃС‚РёРєСѓ
  function getBookingStats(): BookingStats {
    return {
      total: totalBookings.value,
      pending: pendingBookings.value.length,
      confirmed: confirmedBookings.value.length,
      completed: bookings.value.filter(b => b.status === 'completed').length,
      cancelled: bookings.value.filter(b => b.status === 'cancelled').length
    }
  }
  
  // =================== Р’РћР—Р’Р РђР©РђР•Рњ РРќРўР•Р Р¤Р•Р™РЎ ===================
  
  return {
    // РЎРѕСЃС‚РѕСЏРЅРёРµ
    bookings,
    currentBooking,
    availableSlots,
    isLoading,
    error,
    lastBooking,
    
    // Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ
    isFormValid,
    totalBookings,
    pendingBookings,
    confirmedBookings,
    
    // Р”РµР№СЃС‚РІРёСЏ
    fetchBookings,
    fetchAvailableSlots,
    createBooking,
    cancelBooking,
    confirmBooking,
    completeBooking,
    
    // Р’СЃРїРѕРјРѕРіР°С‚РµР»СЊРЅС‹Рµ
    resetCurrentBooking,
    updateBookingData,
    clearError,
    getBookingStats
  }
})
