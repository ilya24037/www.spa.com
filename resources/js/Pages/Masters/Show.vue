<template>
        <!-- SEO метатеги -->
        <MetaTags 
            :title="`${master.name} - Мастер массажа в ${master.city || 'Москве'}`"
            :description="metaDescription"
            :image="master.avatar"
            :keywords="metaKeywords"
        />

        <div class="min-h-screen bg-gray-50">
            <!-- Шапка профиля с хлебными крошками -->
            <MasterHeader 
                :master="master"
                :is-loading="isLoading"
                @toggle-favorite="toggleFavorite"
                @share="shareMaster"
            />

            <!-- Основной контент -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Загрузка -->
                <div v-if="isLoading" class="animate-pulse space-y-8">
                    <div class="bg-white rounded-lg h-96"></div>
                    <div class="bg-white rounded-lg h-64"></div>
                </div>

                <!-- Ошибка -->
                <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <h3 class="text-red-800 font-semibold mb-2">Произошла ошибка</h3>
                    <p class="text-red-600">{{ error }}</p>
                    <Link href="/masters" class="mt-4 inline-block text-red-700 underline">
                        Вернуться к каталогу
                    </Link>
                </div>

                <!-- Контент -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Левая колонка (2/3 на десктопе) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Галерея -->
                        <MasterGallery
                            :images="masterImages"
                            :master-name="master.name"
                            :is-premium="master.is_premium"
                            class="bg-white rounded-lg shadow-sm overflow-hidden"
                        />
                        
                        <!-- Детальная информация -->
                        <MasterDetails 
                            :master="master"
                            :stats="masterStats"
                            class="bg-white rounded-lg shadow-sm"
                        />
                        
                        <!-- Услуги и цены -->
                        <ServicesSection 
                            :services="master.services || []"
                            :master-id="master.id"
                            @select-service="handleServiceSelect"
                        />
                        
                        <!-- Отзывы -->
                        <ReviewsSection 
                            :master-id="master.id"
                            :reviews="reviews"
                            :can-review="canReview"
                            :loading="reviewsLoading"
                            @review-added="handleReviewAdded"
                        />
                    </div>
                    
                    <!-- Правая колонка (1/3 на десктопе) -->
                    <div class="space-y-6">
                        <!-- Виджет бронирования (sticky) -->
                        <div class="lg:sticky lg:top-20">
                            <BookingWidget 
                                :master="master"
                                :selected-service="selectedService"
                                :available-slots="availableSlots"
                                @booking-created="handleBookingCreated"
                            />
                            
                            <!-- Дополнительная информация на мобильных -->
                            <div class="mt-6 lg:hidden">
                                <MasterContactInfo :master="master" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Похожие мастера -->
                <SimilarMastersSection 
                    v-if="similarMasters.length"
                    :masters="similarMasters"
                    :current-master-id="master.id"
                    class="mt-16"
                />
            </div>
        </div>

        <!-- Модальное окно успешного бронирования -->
        <BookingSuccessModal 
            v-if="showBookingSuccess"
            :booking="lastBooking"
            @close="showBookingSuccess = false"
        />
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import { useFavoritesStore } from '@/stores/favorites' // ✅ ИСПРАВЛЕНО!

// Common Components
import MetaTags from '@/Components/MetaTags.vue'

// Master Widgets
import MasterHeader from '@/Components/Masters/MasterHeader/index.vue'
import MasterGallery from '@/Components/Masters/MasterGallery/index.vue'
import MasterDetails from '@/Components/Masters/MasterDetails/index.vue'
import BookingWidget from '@/Components/Masters/BookingWidget/index.vue'
import MasterContactInfo from '@/Components/Masters/MasterContactInfo.vue'

// Sections
import ServicesSection from '@/Components/Masters/ServicesSection.vue'
import ReviewsSection from '@/Components/Masters/ReviewsSection.vue'
import SimilarMastersSection from '@/Components/Masters/SimilarMastersSection.vue'

// Modals
import BookingSuccessModal from '@/Components/Booking/BookingSuccessModal.vue'

// Props
const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    reviews: {
        type: Array,
        default: () => []
    },
    similarMasters: {
        type: Array,
        default: () => []
    },
    availableSlots: {
        type: Object,
        default: () => ({})
    },
    canReview: {
        type: Boolean,
        default: false
    }
})

// Composables
const { showSuccess, showError } = useToast()
const favoritesStore = useFavoritesStore() // ✅ ИСПРАВЛЕНО!
const page = usePage()

// State
const isLoading = ref(false)
const error = ref(null)
const selectedService = ref(null)
const reviewsLoading = ref(false)
const showBookingSuccess = ref(false)
const lastBooking = ref(null)

// Computed
const masterImages = computed(() => {
    if (props.master.gallery?.length) {
        return props.master.gallery
    }
    
    if (props.master.avatar) {
        return [{ id: 1, url: props.master.avatar, is_main: true }]
    }
    
    // Заглушки
    return [
        { id: 1, url: '/images/masters/placeholder-1.jpg', is_main: true },
        { id: 2, url: '/images/masters/placeholder-2.jpg', is_main: false }
    ]
})

const masterStats = computed(() => ({
    experience: props.master.experience_years || 0,
    services: props.master.services_count || props.master.services?.length || 0,
    clients: props.master.clients_count || 0,
    certificates: props.master.certificates_count || 0,
    rating: props.master.rating || 0,
    reviews: props.master.reviews_count || 0
}))

const metaDescription = computed(() => {
    return props.master.description || 
           `${props.master.name} - профессиональный мастер массажа. ` +
           `Опыт работы ${props.master.experience_years || 5} лет. ` +
           `Рейтинг ${props.master.rating || 5.0}. Запись онлайн.`
})

const metaKeywords = computed(() => {
    const services = props.master.services?.map(s => s.name).join(', ') || 'массаж'
    return `${props.master.name}, мастер массажа, ${services}, ${props.master.city || 'Москва'}`
})

// Methods
const toggleFavorite = async () => {
    try {
        await favoritesStore.toggle(props.master.id)  // ✅ Передаём объект master
        showSuccess(
            favoritesStore.isFavorite(props.master.id) 
                ? 'Добавлено в избранное' 
                : 'Удалено из избранного'
        )
    } catch (err) {
        showError('Ошибка при изменении избранного')
    }
}

const shareMaster = () => {
    if (navigator.share) {
        navigator.share({
            title: props.master.name,
            text: metaDescription.value,
            url: window.location.href
        }).catch(() => {})
    } else {
        // Копируем ссылку в буфер
        navigator.clipboard.writeText(window.location.href)
        showSuccess('Ссылка скопирована')
    }
}

const handleServiceSelect = (service) => {
    selectedService.value = service
    // Прокручиваем к виджету бронирования на мобильных
    if (window.innerWidth < 1024) {
        const bookingWidget = document.querySelector('#booking-widget')
        bookingWidget?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

const handleBookingCreated = (booking) => {
    lastBooking.value = booking
    showBookingSuccess.value = true
    selectedService.value = null
    
    // Обновляем доступные слоты
    router.reload({ only: ['availableSlots'] })
}

const handleReviewAdded = (review) => {
    // Добавляем отзыв в начало списка
    props.reviews.unshift(review)
    
    // Обновляем статистику мастера
    props.master.reviews_count++
    props.master.rating = review.new_rating || props.master.rating
    
    showSuccess('Спасибо за ваш отзыв!')
}

// Отслеживаем просмотр
onMounted(() => {
    // Увеличиваем счетчик просмотров
    router.post(`/masters/${props.master.id}/view`, {}, {
        preserveScroll: true,
        preserveState: true
    })
})

// Обработка flash сообщений
watch(() => page.props.flash, (flash) => {
    if (flash?.success) showSuccess(flash.success)
    if (flash?.error) showError(flash.error)
}, { immediate: true })
</script>

<style scoped>
/* Анимация загрузки */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

/* Sticky виджет только на десктопе */
@media (max-width: 1023px) {
    .lg\:sticky {
        position: relative !important;
        top: auto !important;
    }
}
</style>