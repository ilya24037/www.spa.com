<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Overlay -->
                <div 
                    class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                    @click="$emit('close')"
                ></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Запись к мастеру</h3>
                        <button 
                            @click="$emit('close')"
                            class="p-2 hover:bg-gray-100 rounded-lg"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Content -->
                    <form @submit.prevent="submitBooking" class="p-6">
                        <!-- Мастер -->
                        <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                            <img 
                                :src="master.avatar || '/images/no-avatar.jpg'"
                                :alt="master.display_name"
                                class="w-16 h-16 rounded-full"
                            >
                            <div>
                                <h4 class="font-medium">{{ master.display_name }}</h4>
                                <p class="text-sm text-gray-600">{{ master.district }}</p>
                            </div>
                        </div>

                        <!-- Выбор услуги -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Выберите услугу *
                            </label>
                            <select 
                                v-model="form.service_id"
                                class="w-full border-gray-300 rounded-lg"
                                required
                            >
                                <option value="">Выберите услугу</option>
                                <option 
                                    v-for="service in master.services"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{ service.name }} - {{ service.price }}₽ ({{ service.duration }} мин)
                                </option>
                            </select>
                        </div>

                        <!-- Дата и время -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Дата *
                                </label>
                                <VueDatePicker 
                                    v-model="form.booking_date"
                                    :min-date="new Date()"
                                    :disabled-dates="disabledDates"
                                    locale="ru"
                                    format="dd.MM.yyyy"
                                    placeholder="Выберите дату"
                                    :enable-time-picker="false"
                                    @update:model-value="fetchAvailableSlots"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Время *
                                </label>
                                <select 
                                    v-model="form.start_time"
                                    class="w-full border-gray-300 rounded-lg"
                                    :disabled="!form.booking_date || loadingSlots"
                                    required
                                >
                                    <option value="">{{ loadingSlots ? 'Загрузка...' : 'Выберите время' }}</option>
                                    <option 
                                        v-for="slot in availableSlots"
                                        :key="slot"
                                        :value="slot"
                                    >
                                        {{ slot }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Тип услуги -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Где провести сеанс?
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <label 
                                    v-if="master.home_service"
                                    class="relative flex items-center p-4 border rounded-lg cursor-pointer"
                                    :class="{ 'border-indigo-500 bg-indigo-50': form.is_home_service }"
                                >
                                    <input 
                                        type="radio"
                                        v-model="form.is_home_service"
                                        :value="true"
                                        class="sr-only"
                                    >
                                    <div>
                                        <p class="font-medium">Выезд на дом</p>
                                        <p class="text-sm text-gray-600">+500₽ к стоимости</p>
                                    </div>
                                </label>
                                
                                <label 
                                    v-if="master.salon_service"
                                    class="relative flex items-center p-4 border rounded-lg cursor-pointer"
                                    :class="{ 'border-indigo-500 bg-indigo-50': !form.is_home_service }"
                                >
                                    <input 
                                        type="radio"
                                        v-model="form.is_home_service"
                                        :value="false"
                                        class="sr-only"
                                    >
                                    <div>
                                        <p class="font-medium">В салоне</p>
                                        <p class="text-sm text-gray-600">{{ master.salon_address }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Адрес (для выезда) -->
                        <div v-if="form.is_home_service" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Адрес *
                            </label>
                            <input 
                                v-model="form.address"
                                type="text"
                                class="w-full border-gray-300 rounded-lg"
                                placeholder="Улица, дом, квартира"
                                required
                            >
                        </div>

                        <!-- Контактные данные -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ваше имя *
                                </label>
                                <input 
                                    v-model="form.client_name"
                                    type="text"
                                    class="w-full border-gray-300 rounded-lg"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Телефон *
                                </label>
                                <vue-tel-input
                                    v-model="form.client_phone"
                                    :preferred-countries="['ru', 'ua', 'by']"
                                    :only-countries="['ru', 'ua', 'by', 'kz']"
                                    mode="international"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <input 
                                v-model="form.client_email"
                                type="email"
                                class="w-full border-gray-300 rounded-lg"
                                placeholder="Для отправки подтверждения"
                            >
                        </div>

                        <!-- Комментарий -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Пожелания
                            </label>
                            <textarea 
                                v-model="form.client_comment"
                                rows="3"
                                class="w-full border-gray-300 rounded-lg"
                                placeholder="Особые пожелания или вопросы"
                            ></textarea>
                        </div>

                        <!-- Способ оплаты -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Способ оплаты
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label 
                                    v-for="method in paymentMethods"
                                    :key="method.value"
                                    class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer"
                                    :class="{ 'border-indigo-500 bg-indigo-50': form.payment_method === method.value }"
                                >
                                    <input 
                                        type="radio"
                                        v-model="form.payment_method"
                                        :value="method.value"
                                        class="sr-only"
                                    >
                                    <span class="text-sm font-medium">{{ method.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Итого -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="flex justify-between text-sm mb-2">
                                <span>Услуга:</span>
                                <span>{{ selectedServicePrice }}₽</span>
                            </div>
                            <div v-if="form.is_home_service" class="flex justify-between text-sm mb-2">
                                <span>Выезд:</span>
                                <span>500₽</span>
                            </div>
                            <div class="flex justify-between font-medium text-lg border-t pt-2">
                                <span>Итого:</span>
                                <span>{{ totalPrice }}₽</span>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex gap-3">
                            <button 
                                type="submit"
                                :disabled="loading"
                                class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ loading ? 'Отправка...' : 'Записаться' }}
                            </button>
                            <button 
                                type="button"
                                @click="$emit('close')"
                                class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Отмена
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
    master: Object,
    service: Object
})

const emit = defineEmits(['close', 'success'])

const form = ref({
    master_profile_id: props.master.id,
    service_id: props.service?.id || '',
    booking_date: null,
    start_time: '',
    is_home_service: props.master.home_service,
    address: '',
    client_name: '',
    client_phone: '',
    client_email: '',
    client_comment: '',
    payment_method: 'cash'
})

const loading = ref(false)
const loadingSlots = ref(false)
const availableSlots = ref([])
const disabledDates = ref([])

const paymentMethods = [
    { value: 'cash', label: 'Наличные' },
    { value: 'card', label: 'Картой' },
    { value: 'online', label: 'Онлайн' }
]

const selectedServicePrice = computed(() => {
    if (!form.value.service_id) return 0
    const service = props.master.services.find(s => s.id === parseInt(form.value.service_id))
    return service?.price || 0
})

const totalPrice = computed(() => {
    let total = selectedServicePrice.value
    if (form.value.is_home_service) {
        total += 500
    }
    return total
})

const fetchAvailableSlots = async () => {
    if (!form.value.booking_date || !form.value.service_id) return
    
    loadingSlots.value = true
    try {
        const response = await axios.get('/api/bookings/available-slots', {
            params: {
                master_profile_id: props.master.id,
                service_id: form.value.service_id,
                date: form.value.booking_date
            }
        })
        availableSlots.value = response.data.slots
    } catch (error) {
        console.error('Error fetching slots:', error)
        toast.error('Ошибка загрузки доступного времени')
    } finally {
        loadingSlots.value = false
    }
}

const submitBooking = async () => {
    loading.value = true
    
    try {
        await router.post('/bookings', form.value)
        alert('Заявка отправлена! Ожидайте подтверждения мастера.')
        emit('success', form.value)
        emit('close')
    } catch (error) {
        console.error('Booking error:', error)
        alert('Ошибка при создании записи')
    } finally {
        loading.value = false
    }
}

// Загружаем слоты при изменении даты
watch(() => form.value.booking_date, () => {
    form.value.start_time = ''
    if (form.value.booking_date) {
        fetchAvailableSlots()
    }
})
</script>