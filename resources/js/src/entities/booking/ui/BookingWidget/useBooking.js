import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useBookingStore } from '@/stores/bookingStore'
import { useToast } from '@/Composables/useToast'

export function useBooking(master, initialService = null) {
    // Stores & Composables
    const bookingStore = useBookingStore()
    const { showSuccess, showError } = useToast()
    
    // State
    const selectedService = ref(initialService)
    const selectedDateTime = ref(null)
    const clientComment = ref('')
    const isCreating = ref(false)
    const showConfirmModal = ref(false)
    const availableSlots = ref({})
    const loadingSlots = ref(false)
    
    // Computed
    const canProceed = computed(() => {
        return selectedService.value && selectedDateTime.value
    })
    
    const totalPrice = computed(() => {
        if (!selectedService.value) return 0
        
        const basePrice = selectedService.value.price_from || 0
        const duration = selectedService.value.duration || 60
        
        // Добавляем наценку за выезд если есть
        if (selectedService.value.home_service && master.value?.home_service_fee) {
            return basePrice + master.value.home_service_fee
        }
        
        return basePrice
    })
    
    const formattedDateTime = computed(() => {
        if (!selectedDateTime.value) return ''
        
        const date = new Date(selectedDateTime.value.datetime)
        const options = {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit'
        }
        
        return new Intl.DateTimeFormat('ru-RU', options).format(date)
    })
    
    // Methods
    const selectService = (service) => {
        selectedService.value = service
        // Сбрасываем выбранное время при смене услуги
        selectedDateTime.value = null
    }
    
    const selectDateTime = (dateTime) => {
        selectedDateTime.value = dateTime
    }
    
    const loadAvailableSlots = async (date = null) => {
        if (!master.value?.id || !selectedService.value?.id) return
        
        loadingSlots.value = true
        
        try {
            const response = await router.get(`/api/masters/${master.value.id}/available-slots`, {
                service_id: selectedService.value.id,
                date: date,
                preserveState: true,
                preserveScroll: true,
                only: ['slots']
            })
            
            if (response.props.slots) {
                if (date) {
                    availableSlots.value[date] = response.props.slots
                } else {
                    availableSlots.value = response.props.slots
                }
            }
        } catch (error) {
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
            await bookingStore.createBooking({
                master_profile_id: master.value.id,
                service_id: selectedService.value.id,
                booking_date: selectedDateTime.value.date,
                booking_time: selectedDateTime.value.time,
                client_comment: clientComment.value,
                price: totalPrice.value
            })
            
            showSuccess('Бронирование успешно создано! Ожидайте подтверждения от мастера.')
            
            // Сбрасываем форму
            resetForm()
            
            // Перенаправляем на страницу бронирований
            setTimeout(() => {
                router.visit('/bookings')
            }, 2000)
            
        } catch (error) {
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
    
    const calculateEndTime = (startTime, duration) => {
        const [hours, minutes] = startTime.split(':').map(Number)
        const totalMinutes = hours * 60 + minutes + duration
        
        const endHours = Math.floor(totalMinutes / 60)
        const endMinutes = totalMinutes % 60
        
        return `${endHours.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}`
    }
    
    const formatPrice = (price) => {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(price)
    }
    
    const formatDuration = (minutes) => {
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