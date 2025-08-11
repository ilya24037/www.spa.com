<template>
    <Modal :show="true" @close="$emit('close')" max-width="sm">
        <div class="p-6">
            <!-- Иконка успеха -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <!-- Заголовок -->
            <h3 class="text-center text-lg font-semibold text-gray-900 mb-2">
                Бронирование создано!
            </h3>
            
            <!-- Описание -->
            <p class="text-center text-gray-600 mb-6">
                Ваша заявка отправлена мастеру. Ожидайте подтверждения.
            </p>
            
            <!-- Детали бронирования -->
            <div v-if="booking" class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Номер заявки:</span>
                        <span class="font-medium text-gray-900">#{{ booking.id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Мастер:</span>
                        <span class="font-medium text-gray-900">{{ booking.master_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Услуга:</span>
                        <span class="font-medium text-gray-900">{{ booking.service_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Дата и время:</span>
                        <span class="font-medium text-gray-900">{{ formatDateTime(booking) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Стоимость:</span>
                        <span class="font-medium text-gray-900">{{ formatPrice(booking.price) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Информация о подтверждении -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" 
                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" 
                              clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3 text-sm text-blue-700">
                        <p class="font-medium mb-1">Что дальше?</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Мастер получит уведомление о вашей заявке</li>
                            <li>В течение 30 минут он подтвердит бронирование</li>
                            <li>Вы получите SMS и email с подтверждением</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Действия -->
            <div class="flex gap-3">
                <Link :href="`/bookings/${booking.id}`"
                      class="flex-1 bg-purple-600 text-white text-center py-2.5 px-4 rounded-lg font-medium hover:bg-purple-700 transition-colors">
                    Посмотреть детали
                </Link>
                <button @click="$emit('close')"
                        class="flex-1 bg-gray-200 text-gray-700 py-2.5 px-4 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                    Закрыть
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Modal from '@/Components/UI/Modal.vue'
import { format } from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    booking: {
        type: Object,
        required: true
    }
})

defineEmits(['close'])

// Methods
const formatDateTime = (booking) => {
    if (!booking.booking_date || !booking.booking_time) return ''
    
    const date = new Date(`${booking.booking_date} ${booking.booking_time}`)
    return format(date, 'd MMMM, EEEE в HH:mm', { locale: ru })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price)
}
</script>