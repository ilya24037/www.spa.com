import { ref, computed, type Ref, type ComputedRef } from 'vue'
import { router } from '@inertiajs/vue3'
import { useBookingStore } from '@/stores/bookingStore'
import { useToast } from '@/src/shared/composables/useToast'

interface Master {
  id: number
  name: string
  avatar?: string
  services?: Service[]
}

interface Service {
  id: number
  name: string
  description?: string
  price_from?: number
  duration?: number
}

interface BookingSlot {
  time: string
  available: boolean
  duration: number
}

interface AvailableSlots {
  [date: string]: BookingSlot[]
}

interface BookingData {
  master_id: number
  service_id: number
  datetime: string
  client_comment?: string
}

export function useBooking(master: Master, initialService: Service | null = null) {
    // Stores & Composables
    const bookingStore = useBookingStore()
    const { showSuccess, showError } = useToast()
    
    // State
    const selectedService: Ref<Service | null> = ref(initialService)
    const selectedDateTime: Ref<{ datetime: string } | null> = ref(null)
    const clientComment: Ref<string> = ref('')
    const isCreating: Ref<boolean> = ref(false)
    const showConfirmModal: Ref<boolean> = ref(false)
    const availableSlots: Ref<AvailableSlots> = ref({})
    const loadingSlots: Ref<boolean> = ref(false)
    
    // Computed
    const canProceed: ComputedRef<boolean> = computed(() => {
        return !!selectedService.value && !!selectedDateTime.value
    })
    
    const totalPrice: ComputedRef<number> = computed(() => {
        if (!selectedService.value) return 0
        
        const basePrice = selectedService.value.price_from || 0
        
        return basePrice
    })
    
    const formattedDateTime = computed(() => {
        if (!selectedDateTime.value) return ''
        
        const date = new Date(selectedDateTime.value.datetime)
        const options: Intl.DateTimeFormatOptions = {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit'
        }
        
        return new Intl.DateTimeFormat('ru-RU', options).format(date)
    })
    
    // Methods
    const selectService = (service: Service) => {
        selectedService.value = service
        // Сбрасываем выбранное время при смене услуги
        selectedDateTime.value = null
    }
    
    const selectDateTime = (dateTime: { datetime: string }) => {
        selectedDateTime.value = dateTime
    }
    
    const loadAvailableSlots = async (date: string | null = null) => {
        if (!master.id || !selectedService.value?.id) return
        
        loadingSlots.value = true
        
        try {
            const response = await router.get(`/api/masters/${master.id}/available-slots`, {
                service_id: selectedService.value.id,
                date: date,
                preserveState: true,
                preserveScroll: true,
                only: ['slots']
            })
            
            if ((response as any).props?.slots) {
                if (date) {
                    availableSlots.value[date] = (response as any)._props.slots
                } else {
                    availableSlots.value = (response as any)._props.slots
                }
            }
        } catch (error: any) {
            showError('Не удалось загрузить доступное время')
        } finally {
            loadingSlots.value = false
        }
    }
    
    const createBooking = async () => {
        if (!canProceed.value) {
            showError('Выберите услугу и время')
            return
        }
        
        isCreating.value = true
        
        try {
            const bookingData: BookingData = {
                master_id: master.id,
                service_id: selectedService.value!.id,
                datetime: selectedDateTime.value!.datetime,
                client_comment: clientComment.value || undefined
            }
            
            await bookingStore.createBooking({
                master_id: bookingData.master_id,
                service_id: bookingData.service_id,
                start_time: bookingData.datetime,
                duration_minutes: selectedService.value!.duration || 60,
                total_price: totalPrice.value,
                client_name: bookingData.client_comment ? 'Клиент' : '', // TODO: получать из формы пользователя
                client_phone: '', // TODO: получать из профиля пользователя или формы
                client_email: undefined,
                notes: bookingData.client_comment
            } as any)
            
            showSuccess('Бронирование успешно создано! Ожидайте подтверждения от мастера.')
            
            // Сбрасываем форму
            resetForm()
            
            // Перенаправляем на страницу бронирований
            setTimeout(() => {
                router.visit('/bookings')
            }, 2000)
            
        } catch (error: any) {
            showError(error.response?.data?.message || 'Ошибка при создании бронирования')
        } finally {
            isCreating.value = false
        }
    }
    
    const confirmBooking = () => {
        showConfirmModal.value = true
    }
    
    const resetForm = () => {
        selectedService.value = initialService
        selectedDateTime.value = null
        clientComment.value = ''
        showConfirmModal.value = false
    }
    
    const calculateEndTime = (startTime: string, duration: number) => {
        const [hours = 0, minutes = 0] = startTime.split(':').map(Number)
        const totalMinutes = hours * 60 + minutes + duration
        
        const endHours = Math.floor(totalMinutes / 60)
        const endMinutes = totalMinutes % 60
        
        return `${endHours.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}`
    }
    
    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(price)
    }
    
    const formatDuration = (minutes: number) => {
        const hours = Math.floor(minutes / 60)
        const mins = minutes % 60
        
        if (hours > 0 && mins > 0) {
            return `${hours} ч ${mins} мин`
        } else if (hours > 0) {
            return `${hours} ч`
        } else {
            return `${mins} мин`
        }
    }
    
    return {
        // State
        selectedService,
        selectedDateTime,
        clientComment,
        isCreating,
        showConfirmModal,
        availableSlots,
        loadingSlots,
        
        // Computed
        canProceed,
        totalPrice,
        formattedDateTime,
        
        // Methods
        selectService,
        selectDateTime,
        loadAvailableSlots,
        createBooking,
        confirmBooking,
        resetForm,
        calculateEndTime,
        formatPrice,
        formatDuration
    }
}