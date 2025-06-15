<template>
    <AppLayout>
        <Head :title="isMaster ? 'Мои заказы' : 'Мои бронирования'" />

        <div class="container mx-auto px-4 py-8">
            <!-- Заголовок и фильтры -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-4">
                    {{ isMaster ? 'Заказы на услуги' : 'Мои бронирования' }}
                </h1>
                
                <!-- Фильтры по статусу -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button 
                        v-for="status in statuses" 
                        :key="status.value"
                        @click="filterStatus = status.value"
                        :class="[
                            'px-4 py-2 rounded-lg transition',
                            filterStatus === status.value 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-gray-100 hover:bg-gray-200'
                        ]"
                    >
                        {{ status.label }}
                        <span v-if="status.count > 0" class="ml-1 text-sm">
                            ({{ status.count }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- Список бронирований -->
            <div v-if="filteredBookings.length > 0" class="space-y-4">
                <div 
                    v-for="booking in filteredBookings" 
                    :key="booking.id"
                    class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition"
                >
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <!-- Основная информация -->
                        <div class="flex-1">
                            <!-- Номер и статус -->
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-sm text-gray-500">
                                    № {{ booking.booking_number }}
                                </span>
                                <span :class="getStatusClass(booking.status)">
                                    {{ getStatusLabel(booking.status) }}
                                </span>
                            </div>

                            <!-- Информация о мастере/клиенте -->
                            <div class="mb-3">
                                <h3 class="font-semibold text-lg">
                                    <template v-if="isMaster">
                                        Клиент: {{ booking.client_name }}
                                    </template>
                                    <template v-else>
                                        {{ booking.master_profile.user.name }}
                                    </template>
                                </h3>
                                <p class="text-gray-600">
                                    {{ booking.service.name }}
                                </p>
                            </div>

                            <!-- Дата и время -->
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-1">
                                    <CalendarIcon class="w-4 h-4 text-gray-400" />
                                    <span>{{ formatDate(booking.booking_date) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <ClockIcon class="w-4 h-4 text-gray-400" />
                                    <span>{{ booking.booking_time }} - {{ booking.booking_end_time }}</span>
                                </div>
                            </div>

                            <!-- Адрес (если на выезде) -->
                            <div v-if="booking.service_location === 'home'" class="mt-2 text-sm text-gray-600">
                                <MapPinIcon class="w-4 h-4 inline mr-1" />
                                {{ booking.address }}
                            </div>
                        </div>

                        <!-- Цена и действия -->
                        <div class="flex flex-col items-end gap-3">
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ formatPrice(booking.total_price) }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ getPaymentLabel(booking.payment_method) }}
                                </p>
                            </div>

                            <!-- Кнопки действий -->
                            <div class="flex gap-2">
                                <Link 
                                    :href="route('bookings.show', booking.id)"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                                >
                                    Подробнее
                                </Link>

                                <!-- Действия для мастера -->
                                <template v-if="isMaster && booking.status === 'pending'">
                                    <button 
                                        @click="confirmBooking(booking)"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                                    >
                                        Принять
                                    </button>
                                    <button 
                                        @click="showCancelModal(booking)"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                    >
                                        Отклонить
                                    </button>
                                </template>

                                <!-- Действия для клиента -->
                                <template v-else-if="!isMaster && canCancel(booking)">
                                    <button 
                                        @click="showCancelModal(booking)"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                    >
                                        Отменить
                                    </button>
                                </template>

                                <!-- Завершить услугу (для мастера) -->
                                <template v-if="isMaster && booking.status === 'confirmed'">
                                    <button 
                                        @click="completeBooking(booking)"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                    >
                                        Завершить
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <CalendarDaysIcon class="w-16 h-16 mx-auto" />
                </div>
                <p class="text-xl text-gray-600 mb-4">
                    {{ filterStatus === 'all' ? 'У вас пока нет бронирований' : 'Нет бронирований с таким статусом' }}
                </p>
                <Link 
                    v-if="!isMaster"
                    :href="route('home')"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Найти мастера
                </Link>
            </div>

            <!-- Пагинация -->
            <div v-if="bookings.meta && bookings.meta.last_page > 1" class="mt-8 flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="link in bookings.meta.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-2 rounded',
                            link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                        :disabled="!link.url"
                    />
                </nav>
            </div>
        </div>

        <!-- Модальное окно отмены -->
        <Modal :show="showCancelModalState" @close="showCancelModalState = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ isMaster ? 'Отклонить заказ' : 'Отменить бронирование' }}
                </h3>
                
                <p class="text-gray-600 mb-4">
                    Вы уверены, что хотите {{ isMaster ? 'отклонить этот заказ' : 'отменить бронирование' }}?
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Причина отмены</label>
                    <textarea 
                        v-model="cancelReason"
                        rows="3"
                        class="w-full border-gray-300 rounded-lg"
                        :placeholder="isMaster ? 'Укажите причину отклонения' : 'Укажите причину отмены'"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        @click="showCancelModalState = false"
                        class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                    >
                        Отмена
                    </button>
                    <button 
                        @click="cancelBooking"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                    >
                        {{ isMaster ? 'Отклонить' : 'Отменить бронирование' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import { CalendarIcon, ClockIcon, MapPinIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    bookings: Object,
    isMaster: Boolean
})

// Состояние
const filterStatus = ref('all')
const showCancelModalState = ref(false)
const selectedBooking = ref(null)
const cancelReason = ref('')

// Статусы для фильтрации
const statuses = computed(() => {
    const allBookings = props.bookings.data || []
    
    const statusCounts = {
        all: allBookings.length,
        pending: allBookings.filter(b => b.status === 'pending').length,
        confirmed: allBookings.filter(b => b.status === 'confirmed').length,
        completed: allBookings.filter(b => b.status === 'completed').length,
        cancelled: allBookings.filter(b => b.status === 'cancelled').length,
    }

    return [
        { value: 'all', label: 'Все', count: statusCounts.all },
        { value: 'pending', label: 'Ожидают', count: statusCounts.pending },
        { value: 'confirmed', label: 'Подтверждены', count: statusCounts.confirmed },
        { value: 'completed', label: 'Завершены', count: statusCounts.completed },
        { value: 'cancelled', label: 'Отменены', count: statusCounts.cancelled },
    ]
})

// Фильтрованные бронирования
const filteredBookings = computed(() => {
    const allBookings = props.bookings.data || []
    if (filterStatus.value === 'all') return allBookings
    return allBookings.filter(b => b.status === filterStatus.value)
})

// Форматирование даты
const formatDate = (date) => {
    return format(new Date(date), 'd MMMM yyyy', { locale: ru })
}

// Форматирование цены
const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(price)
}

// Получение класса для статуса
const getStatusClass = (status) => {
    const classes = {
        pending: 'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800',
        confirmed: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800',
        completed: 'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800',
        cancelled: 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800',
        in_progress: 'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800',
    }
    return classes[status] || ''
}

// Получение текста статуса
const getStatusLabel = (status) => {
    const labels = {
        pending: 'Ожидает подтверждения',
        confirmed: 'Подтверждено',
        completed: 'Завершено',
        cancelled: 'Отменено',
        in_progress: 'Выполняется',
    }
    return labels[status] || status
}

// Получение текста способа оплаты
const getPaymentLabel = (method) => {
    const labels = {
        cash: 'Наличными',
        card: 'Картой',
        online: 'Онлайн',
        transfer: 'Переводом'
    }
    return labels[method] || method
}

// Проверка возможности отмены
const canCancel = (booking) => {
    if (booking.status !== 'pending' && booking.status !== 'confirmed') return false
    
    // Проверяем время до начала (минимум 2 часа)
    const bookingDateTime = new Date(`${booking.booking_date} ${booking.booking_time}`)
    const hoursUntilBooking = (bookingDateTime - new Date()) / (1000 * 60 * 60)
    
    return hoursUntilBooking > 2
}

// Показать модальное окно отмены
const showCancelModal = (booking) => {
    selectedBooking.value = booking
    cancelReason.value = ''
    showCancelModalState.value = true
}

// Подтвердить бронирование
const confirmBooking = (booking) => {
    router.post(route('bookings.confirm', booking.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Обновится автоматически через Inertia
        }
    })
}

// Отменить бронирование
const cancelBooking = () => {
    if (!selectedBooking.value) return
    
    router.post(route('bookings.cancel', selectedBooking.value.id), {
        reason: cancelReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModalState.value = false
            selectedBooking.value = null
            cancelReason.value = ''
        }
    })
}

// Завершить услугу
const completeBooking = (booking) => {
    if (confirm('Отметить услугу как выполненную?')) {
        router.post(route('bookings.complete', booking.id), {}, {
            preserveScroll: true
        })
    }
}
</script>