<template>
    <Head title="Мои отзывы" />
    
    <div class="py-6 lg:py-8">
        <div class="flex gap-6">
            <!-- Боковая панель -->
            <SidebarWrapper 
                v-model="showSidebar"
                content-class="p-0"
                :show-desktop-header="false"
                :always-visible-desktop="true"
            >
                <!-- Профиль пользователя -->
                <div class="p-6 border-b">
                    <div class="flex items-center space-x-3">
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                            :style="{ backgroundColor: avatarColor }"
                        >
                            {{ userInitial }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ userName }}</div>
                            <div class="text-sm text-gray-500">★ 4.2 • 5 отзывов</div>
                        </div>
                    </div>
                </div>
                
                <!-- Меню -->
                <nav class="flex-1">
                    <div class="py-2">
                        <div class="px-4">
                            <Link 
                                href="/profile"
                                class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors text-gray-700 hover:bg-gray-50"
                            >
                                <span>Мои объявления</span>
                            </Link>
                        </div>
                        
                        <div class="px-4 mt-2 space-y-1">
                            <Link 
                                href="/bookings"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Заказы
                            </Link>
                            
                            <Link 
                                href="/profile/reviews"
                                class="flex items-center px-3 py-2 text-sm bg-blue-50 text-blue-700 font-medium rounded-md transition-colors"
                            >
                                Мои отзывы
                            </Link>
                            
                            <Link 
                                href="/favorites"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Избранное
                            </Link>
                            
                            <Link 
                                href="/messages"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Сообщения
                            </Link>
                            
                            <Link 
                                href="/notifications"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Уведомления
                            </Link>
                            
                            <Link 
                                href="/wallet"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Кошелёк
                            </Link>
                            
                            <Link 
                                href="/profile/addresses"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Адреса
                            </Link>
                            
                            <Link 
                                href="/profile/management"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Управление профилем
                            </Link>
                            
                            <Link 
                                href="/profile/security"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Защита профиля
                            </Link>
                            
                            <Link 
                                href="/settings"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Настройки
                            </Link>
                            
                            <Link 
                                href="/services"
                                class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                Платные услуги
                            </Link>
                        </div>
                    </div>
                </nav>
            </SidebarWrapper>
            
            <!-- Основной контент -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="p-6 border-b">
                        <h1 class="text-2xl font-semibold text-gray-900">Мои отзывы</h1>
                        <p class="text-sm text-gray-500 mt-1">Отзывы от клиентов о ваших услугах</p>
                    </div>
                    
                    <!-- Фильтры -->
                    <div class="p-4 border-b">
                        <div class="flex gap-4 items-center">
                            <button 
                                v-for="filter in filters" 
                                :key="filter.value"
                                @click="activeFilter = filter.value"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                    activeFilter === filter.value 
                                        ? 'bg-blue-100 text-blue-700' 
                                        : 'text-gray-600 hover:bg-gray-100'
                                ]"
                            >
                                {{ filter.label }}
                                <span v-if="filter.count > 0" class="ml-2 text-xs">
                                    ({{ filter.count }})
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Список отзывов -->
                    <div v-if="filteredReviews.length > 0" class="divide-y">
                        <div v-for="review in filteredReviews" :key="review.id" class="p-6 hover:bg-gray-50">
                            <div class="flex items-start gap-4">
                                <div 
                                    class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium flex-shrink-0"
                                    :style="{ backgroundColor: getAvatarColor(review.client_name) }"
                                >
                                    {{ review.client_name.charAt(0).toUpperCase() }}
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ review.client_name }}</h3>
                                            <p class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</p>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span 
                                                v-for="star in 5" 
                                                :key="star"
                                                class="text-lg"
                                                :class="star <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                            >
                                                ★
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-700 mb-3">{{ review.comment }}</p>
                                    
                                    <div class="flex items-center gap-4 text-sm">
                                        <Link 
                                            :href="`/ads/${review.ad_id}`"
                                            class="text-blue-600 hover:text-blue-700"
                                        >
                                            {{ review.ad_title }}
                                        </Link>
                                    </div>
                                    
                                    <!-- Ответ на отзыв -->
                                    <div v-if="review.response" class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-sm font-medium text-gray-700">Ваш ответ:</span>
                                            <span class="text-xs text-gray-500">{{ formatDate(review.response_at) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700">{{ review.response }}</p>
                                    </div>
                                    
                                    <!-- Форма ответа -->
                                    <div v-else-if="showReplyForm === review.id" class="mt-4">
                                        <textarea
                                            v-model="replyText"
                                            placeholder="Напишите ответ на отзыв..."
                                            class="w-full p-3 border rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            rows="3"
                                        ></textarea>
                                        <div class="flex gap-2 mt-2">
                                            <button
                                                @click="submitReply(review.id)"
                                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
                                            >
                                                Отправить
                                            </button>
                                            <button
                                                @click="showReplyForm = null; replyText = ''"
                                                class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200"
                                            >
                                                Отмена
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Кнопка ответить -->
                                    <button
                                        v-else
                                        @click="showReplyForm = review.id"
                                        class="mt-3 text-sm text-blue-600 hover:text-blue-700"
                                    >
                                        Ответить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Пустое состояние -->
                    <div v-else class="p-12 text-center">
                        <div class="text-6xl mb-4">💬</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            {{ activeFilter === 'all' ? 'У вас пока нет отзывов' : 'Нет отзывов в этой категории' }}
                        </h3>
                        <p class="text-gray-500">
                            Отзывы появятся здесь после того, как клиенты оставят их о ваших услугах
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
// 🎯 FSD Импорты
import SidebarWrapper from '@/src/shared/ui/organisms/SidebarWrapper/SidebarWrapper.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const userName = computed(() => user.value?.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = '#3B82F6'

const showSidebar = ref(false)
const activeFilter = ref('all')
const showReplyForm = ref(null)
const replyText = ref('')

// Фильтры
const filters = [
    { label: 'Все отзывы', value: 'all', count: 15 },
    { label: 'Положительные', value: 'positive', count: 12 },
    { label: 'Нейтральные', value: 'neutral', count: 2 },
    { label: 'Отрицательные', value: 'negative', count: 1 },
    { label: 'Без ответа', value: 'no_reply', count: 5 }
]

// Тестовые данные отзывов
const reviews = ref([
    {
        id: 1,
        client_name: 'Анна Петрова',
        rating: 5,
        comment: 'Отличный массаж! Мастер профессионал своего дела. Атмосфера расслабляющая, все очень чисто. Обязательно приду еще!',
        ad_id: 74,
        ad_title: 'Массаж релаксирующий',
        created_at: new Date('2024-01-10'),
        response: 'Спасибо за ваш отзыв! Рада, что вам понравилось. Буду рада видеть вас снова!',
        response_at: new Date('2024-01-11')
    },
    {
        id: 2,
        client_name: 'Михаил Иванов',
        rating: 4,
        comment: 'Хороший массаж, но немного дороговато. В целом доволен.',
        ad_id: 74,
        ad_title: 'Массаж релаксирующий',
        created_at: new Date('2024-01-08'),
        response: null
    },
    {
        id: 3,
        client_name: 'Елена Сидорова',
        rating: 5,
        comment: 'Прекрасный мастер! Проблемы со спиной ушли после курса массажа.',
        ad_id: 75,
        ad_title: 'Лечебный массаж спины',
        created_at: new Date('2024-01-05'),
        response: null
    }
])

// Фильтрация отзывов
const filteredReviews = computed(() => {
    if (activeFilter.value === 'all') return reviews.value
    if (activeFilter.value === 'positive') return reviews.value.filter(r => r.rating >= 4)
    if (activeFilter.value === 'neutral') return reviews.value.filter(r => r.rating === 3)
    if (activeFilter.value === 'negative') return reviews.value.filter(r => r.rating <= 2)
    if (activeFilter.value === 'no_reply') return reviews.value.filter(r => !r.response)
    return reviews.value
})

// Функции
const getAvatarColor = (name) => {
    const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
    const index = name.charCodeAt(0) % colors.length
    return colors[index]
}

const formatDate = (date) => {
    if (!date) return ''
    const d = new Date(date)
    return d.toLocaleDateString('ru-RU', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
    })
}

const submitReply = async (reviewId) => {
    if (!replyText.value.trim()) return
    
    // Здесь будет отправка на сервер
    
    // Обновляем локально для демонстрации
    const review = reviews.value.find(r => r.id === reviewId)
    if (review) {
        review.response = replyText.value
        review.response_at = new Date()
    }
    
    showReplyForm.value = null
    replyText.value = ''
}
</script> 