<template>
    <AppLayout>
        <Head :title="`Бронирование №${booking.booking_number}`" />

        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- Хлебные крошки -->
            <nav class="mb-6 text-sm">
                <Link :href="route('home')" class="text-gray-500 hover:text-gray-700">
                    Главная
                </Link>
                <span class="mx-2 text-gray-400">/</span>
                <Link :href="route('bookings.index')" class="text-gray-500 hover:text-gray-700">
                    Мои бронирования
                </Link>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-700">№{{ booking.booking_number }}</span>
            </nav>

            <!-- Статус бронирования -->
            <div class="mb-6">
                <div :class="getStatusBanner(booking.status)">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <component :is="getStatusIcon(booking.status)" class="w-6 h-6" />
                            <div>
                                <h2 class="text-lg font-semibold">{{ getStatusTitle(booking.status) }}</h2>
                                <p class="text-sm opacity-90">{{ getStatusDescription(booking.status) }}</p>
                            </div>
                        </div>
                        
                        <!-- Действия -->
                        <div v-if="canTakeAction" class="flex gap-2">
                            <!-- Для мастера -->
                            <template v-if="canManage && booking.status === 'pending'">
                                <button 
                                    @click="confirmBooking"
                                    class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition"
                                >
                                    Принять заказ
                                </button>
                                <button 
                                    @click="showCancelModal = true"
                                    class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition"
                                >
                                    Отклонить
                                </button>
                            </template>

                            <!-- Для клиента -->
                            <template v-else-if="!canManage && canCancel">
                                <button 
                                    @click="showCancelModal = true"
                                    class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition"
                                >
                                    Отменить бронирование
                                </button>
                            </template>

                            <!-- Завершить для мастера -->
                            <template v-if="canManage && booking.status === 'confirmed'">
                                <button 
                                    @click="completeBooking"
                                    class="px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                >
                                    Отметить выполненным
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Основная информация -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Информация о бронировании -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Детали бронирования</h3>
                        
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Номер заказа:</dt>
                                <dd class="font-medium">{{ booking.booking_number }}</dd>
                            </div>
                            
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Дата создания:</dt>
                                <dd>{{ formatDateTime(booking.created_at) }}</dd>
                            </div>
                            
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Дата и время услуги:</dt>
                                <dd class="font-medium">
                                    {{ formatDate(booking.booking_date) }}, {{ booking.booking_time }}
                                </dd>
                            </div>
                            
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Длительность:</dt>
                                <dd>{{ booking.service.duration }} минут</dd>
                            </div>
                            
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Услуга:</dt>
                                <dd class="font-medium">{{ booking.service.name }}</dd>
                            </div>
                            
                            <div class="flex justify-between py-2 border-b">
                                <dt class="text-gray-600">Место оказания:</dt>
                                <dd>
                                    <span v-if="booking.service_location === 'home'">
                                        На выезде
                                    </span>
                                    <span v-else>
                                        В салоне
                                    </span>
                                </dd>
                            </div>
                            
                            <div v-if="booking.address" class="py-2 border-b">
                                <dt class="text-gray-600 mb-1">Адрес:</dt>
                                <dd class="font-medium">{{ booking.address }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Информация о клиенте/мастере -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            {{ canManage ? 'Информация о клиенте' : 'Информация о мастере' }}
                        </h3>
                        
                        <div class="flex items-start gap-4">
                            <img 
                                :src="getContactAvatar"
                                :alt="getContactName"
                                class="w-16 h-16 rounded-full object-cover"
                            >
                            <div class="flex-1">
                                <h4 class="font-semibold">{{ getContactName }}</h4>
                                
                                <div v-if="!canManage && booking.master_profile" class="flex items-center gap-1 text-sm text-gray-600 mt-1">
                                    <StarIcon class="w-4 h-4 text-yellow-400 fill-current" />
                                    <span>{{ booking.master_profile.rating }}</span>
                                    <span class="text-gray-400">({{ booking.master_profile.reviews_count }} отзывов)</span>
                                </div>
                                
                                <!-- Контакты показываем только после подтверждения -->
                                <div v-if="booking.status !== 'pending'" class="mt-3 space-y-2">
                                    <a :href="`tel:${getContactPhone}`" class="flex items-center gap-2 text-blue-600 hover:underline">
                                        <PhoneIcon class="w-4 h-4" />
                                        {{ getContactPhone }}
                                    </a>
                                    
                                    <Link 
                                        v-if="!canManage"
                                        :href="route('masters.show', booking.master_profile_id)"
                                        class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800"
                                    >
                                        Перейти в профиль мастера
                                        <ChevronRightIcon class="w-4 h-4" />
                                    </Link>
                                </div>
                                
                                <div v-else class="mt-3 p-3 bg-yellow-50 rounded-lg">
                                    <p class="text-sm text-yellow-800">
                                        Контактные данные будут доступны после подтверждения бронирования
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Комментарий клиента -->
                        <div v-if="booking.client_comment" class="mt-4 pt-4 border-t">
                            <p class="text-sm text-gray-600 mb-1">Комментарий к заказу:</p>
                            <p class="text-gray-800">{{ booking.client_comment }}</p>
                        </div>
                    </div>

                    <!-- История изменений -->
                    <div v-if="booking.status !== 'pending'" class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">История заказа</h3>
                        
                        <div class="space-y-3">
                            <!-- Создание -->
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600">Заказ создан</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.created_at) }}</p>
                                </div>
                            </div>
                            
                            <!-- Подтверждение -->
                            <div v-if="booking.confirmed_at" class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600">Заказ подтверждён мастером</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.confirmed_at) }}</p>
                                </div>
                            </div>
                            
                            <!-- Отмена -->
                            <div v-if="booking.cancelled_at" class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-red-500 rounded-full mt-2"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600">Заказ отменён</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.cancelled_at) }}</p>
                                    <p v-if="booking.cancellation_reason" class="text-sm mt-1">
                                        Причина: {{ booking.cancellation_reason }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Завершение -->
                            <div v-if="booking.status === 'completed'" class="flex items-start gap-3">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600">Услуга оказана</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Сайдбар с оплатой -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                        <h3 class="text-lg font-semibold mb-4">Оплата</h3>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ booking.service.name }}</span>
                                <span>{{ formatPrice(booking.service_price) }}</span>
                            </div>
                            
                            <div v-if="booking.travel_fee > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Выезд мастера</span>
                                <span>{{ formatPrice(booking.travel_fee) }}</span>
                            </div>
                            
                            <div v-if="booking.discount_amount > 0" class="flex justify-between text-sm text-green-600">
                                <span>Скидка</span>
                                <span>-{{ formatPrice(booking.discount_amount) }}</span>
                            </div>
                            
                            <div class="pt-3 border-t flex justify-between font-semibold">
                                <span>Итого:</span>
                                <span class="text-xl">{{ formatPrice(booking.total_price) }}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Способ оплаты:</span>
                                <span class="font-medium">{{ getPaymentMethodLabel(booking.payment_method) }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Статус оплаты:</span>
                                <span :class="[
                                    'font-medium',
                                    booking.payment_status === 'paid' ? 'text-green-600' : 'text-gray-800'
                                ]">
                                    {{ getPaymentStatusLabel(booking.payment_status) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Кнопка оплаты (для будущего функционала) -->
                        <div v-if="booking.payment_status === 'pending' && booking.payment_method === 'online' && booking.status === 'confirmed'" 
                             class="mt-4">
                            <button class="w-full py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition" disabled>
                                Оплатить онлайн (скоро)
                            </button>
                        </div>
                        
                        <!-- QR код для быстрого доступа -->
                        <div class="mt-6 pt-6 border-t text-center">
                            <p class="text-sm text-gray-600 mb-2">QR-код бронирования</p>
                            <div class="inline-block p-4 bg-gray-100 rounded-lg">
                                <div class="w-32 h-32 bg-gray-300 rounded flex items-center justify-center text-xs text-gray-500">
                                    QR Code
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Покажите код мастеру
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно отмены -->
        <Modal :show="showCancelModal" @close="showCancelModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ canManage ? 'Отклонить заказ' : 'Отменить бронирование' }}
                </h3>
                
                <p class="text-gray-600 mb-4">
                    Вы уверены, что хотите {{ canManage ? 'отклонить этот заказ' : 'отменить бронирование' }}?
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Причина отмены *</label>
                    <textarea 
                        v-model="cancelReason"
                        rows="3"
                        class="w-full border-gray-300 rounded-lg"
                        :placeholder="canManage ? 'Укажите причину отклонения' : 'Укажите причину отмены'"
                        required
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        @click="showCancelModal = false"
                        class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                    >
                        Отмена
                    </button>
                    <button 
                        @click="cancelBooking"
                        :disabled="!cancelReason.trim()"
                        :class="[
                            'px-4 py-2 rounded-lg transition',
                            cancelReason.trim() 
                                ? 'bg-red-600 text-white hover:bg-red-700' 
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        ]"
                    >
                        {{ canManage ? 'Отклонить' : 'Отменить бронирование' }}
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
import Modal from '@/Components/Modal.vue'
import { 
    StarIcon, 
    PhoneIcon, 
    ChevronRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/solid'
import { format } from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    booking: Object,
    canManage: Boolean
})

// Состояние
const showCancelModal = ref(false)
const cancelReason = ref('')

// Вычисляемые свойства
const canTakeAction = computed(() => {
    return ['pending', 'confirmed'].includes(props.booking.status)
})

const canCancel = computed(() => {
    if (!['pending', 'confirmed'].includes(props.booking.status)) return false
    
    // Проверяем время до начала (минимум 2 часа)
    const bookingDateTime = new Date(`${props.booking.booking_date} ${props.booking.booking_time}`)
    const hoursUntilBooking = (bookingDateTime - new Date()) / (1000 * 60 * 60)
    
    return hoursUntilBooking > 2
})

const getContactName = computed(() => {
    if (props.canManage) {
        return props.booking.client_name
    }
    return props.booking.master_profile?.user?.name || 'Мастер'
})

const getContactPhone = computed(() => {
    if (props.canManage) {
        return props.booking.client_phone
    }
    return props.booking.master_profile?.phone || ''
})

const getContactAvatar = computed(() => {
    if (props.canManage) {
        return props.booking.client?.avatar_url || '/images/default-avatar.png'
    }
    return props.booking.master_profile?.user?.avatar_url || '/images/default-avatar.png'
})

// Методы для статусов
const getStatusBanner = (status) => {
    const banners = {
        pending: 'p-4 rounded-lg bg-yellow-100 text-yellow-800',
        confirmed: 'p-4 rounded-lg bg-green-100 text-green-800',
        completed: 'p-4 rounded-lg bg-blue-100 text-blue-800',
        cancelled: 'p-4 rounded-lg bg-red-100 text-red-800',
        in_progress: 'p-4 rounded-lg bg-purple-100 text-purple-800'
    }
    return banners[status] || 'p-4 rounded-lg bg-gray-100 text-gray-800'
}

const getStatusIcon = (status) => {
    const icons = {
        pending: ClockIcon,
        confirmed: CheckCircleIcon,
        completed: CheckCircleIcon,
        cancelled: XCircleIcon,
        in_progress: ClockIcon
    }
    return icons[status] || ExclamationTriangleIcon
}

const getStatusTitle = (status) => {
    const titles = {
        pending: 'Ожидает подтверждения',
        confirmed: 'Бронирование подтверждено',
        completed: 'Услуга оказана',
        cancelled: 'Бронирование отменено',
        in_progress: 'Услуга выполняется'
    }
    return titles[status] || 'Неизвестный статус'
}

const getStatusDescription = (status) => {
    if (props.canManage) {
        const descriptions = {
            pending: 'Примите или отклоните заказ',
            confirmed: 'Клиент ожидает вас в назначенное время',
            completed: 'Заказ успешно выполнен',
            cancelled: 'Заказ был отменён',
            in_progress: 'Услуга выполняется в данный момент'
        }
        return descriptions[status] || ''
    } else {
        const descriptions = {
            pending: 'Мастер рассматривает вашу заявку',
            confirmed: 'Мастер подтвердил бронирование',
            completed: 'Спасибо за использование нашего сервиса!',
            cancelled: 'Бронирование было отменено',
            in_progress: 'Мастер оказывает услугу'
        }
        return descriptions[status] || ''
    }
}

const getPaymentMethodLabel = (method) => {
    const labels = {
        cash: 'Наличными',
        card: 'Картой мастеру',
        online: 'Онлайн на сайте',
        transfer: 'Переводом'
    }
    return labels[method] || method
}

const getPaymentStatusLabel = (status) => {
    const labels = {
        pending: 'Ожидает оплаты',
        paid: 'Оплачено',
        refunded: 'Возвращено'
    }
    return labels[status] || status
}

// Форматирование
const formatDate = (date) => {
    return format(new Date(date), 'd MMMM yyyy', { locale: ru })
}

const formatDateTime = (datetime) => {
    return format(new Date(datetime), 'd MMMM yyyy, HH:mm', { locale: ru })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(price)
}

// Действия
const confirmBooking = () => {
    router.post(route('bookings.confirm', props.booking.id), {}, {
        preserveScroll: true
    })
}

const completeBooking = () => {
    if (confirm('Отметить услугу как выполненную?')) {
        router.post(route('bookings.complete', props.booking.id), {}, {
            preserveScroll: true
        })
    }
}

const cancelBooking = () => {
    if (!cancelReason.value.trim()) return
    
    router.post(route('bookings.cancel', props.booking.id), {
        reason: cancelReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false
            cancelReason.value = ''
        }
    })
}
</script>