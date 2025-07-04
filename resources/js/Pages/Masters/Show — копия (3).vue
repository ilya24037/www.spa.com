<template>
    <MetaTags :meta="meta" />
        <!-- Хлебные крошки как на Avito -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <nav class="flex text-sm text-gray-500">
                <Link href="/" class="hover:text-gray-700">Главная</Link>
                <span class="mx-2">/</span>
                <Link href="/masters" class="hover:text-gray-700">Мастера</Link>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ master.display_name || master.name }}</span>
            </nav>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
            
    <!-- Галерея фотографий (для всех устройств) -->
            <div class="mb-6 lg:hidden">
                <MasterPhotoGallery 
                    :photos="allPhotos"
                    :master-name="master.display_name || master.name"
                />
            </div>
            
          <!-- Desktop: 2 колонки -->
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                
                <!-- Левая колонка: Галерея для десктопа -->
                <div class="lg:col-span-7">
                    <!-- Галерея фотографий (только десктоп) -->
                    <div class="hidden lg:block mb-6">
                        <MasterPhotoGallery 
                            :photos="allPhotos"
                            :master-name="master.display_name || master.name"
                            :is-premium="master.is_premium"
                            :is-verified="master.is_verified"
                        />
                    </div>
                            
                            <!-- Превью фотографий -->
                            <div v-if="allImages.length > 1" class="absolute bottom-4 left-4 right-4">
                                <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                                    <button
                                        v-for="(image, index) in allImages"
                                        :key="index"
                                        @click="currentImageIndex = index"
                                        class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all"
                                        :class="currentImageIndex === index ? 'border-white shadow-lg' : 'border-transparent opacity-70 hover:opacity-100'"
                                    >
                                        <img :src="image" class="w-full h-full object-cover">
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Бейджи как на Ozon -->
                            <div class="absolute top-4 left-4 flex flex-col gap-2">
                                <span v-if="master.is_premium" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-yellow-400 to-yellow-500 text-white shadow-lg">
                                    ⭐ ТОП мастер
                                </span>
                                <span v-if="master.is_verified" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg">
                                    ✓ Проверенный
                                </span>
                            </div>
                            
                            <!-- Счетчик фото -->
                            <div v-if="allImages.length > 1" class="absolute top-4 right-4 bg-black/60 text-white px-3 py-1 rounded-full text-sm">
                                {{ currentImageIndex + 1 }} / {{ allImages.length }}
                            </div>
                        </div>
                    </div>

                    <!-- Мобильная версия: основная информация -->
                    <div class="lg:hidden bg-white rounded-lg shadow-sm p-4 mb-4">
                        <h1 class="text-xl font-bold text-gray-900">
                            {{ master.display_name || master.name }}
                        </h1>
                        <div class="flex items-center gap-2 mt-2">
                            <StarRating :rating="master.rating" />
                            <span class="text-sm text-gray-600">
                                {{ master.rating }} ({{ master.reviews_count }} {{ pluralize(master.reviews_count, ['отзыв', 'отзыва', 'отзывов']) }})
                            </span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 mt-3">
                            от {{ formatPrice(master.price_from) }} ₽
                        </div>
                    </div>

                    <!-- Табы с контентом -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <!-- Навигация табов как на Яндекс.Услугах -->
                        <div class="border-b">
                            <nav class="flex -mb-px overflow-x-auto scrollbar-hide">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    class="flex-shrink-0 py-4 px-6 text-sm font-medium border-b-2 transition-all relative"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    ]"
                                >
                                    {{ tab.name }}
                                    <span v-if="tab.badge" class="ml-2 bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full text-xs">
                                        {{ tab.badge }}
                                    </span>
                                </button>
                            </nav>
                        </div>

                        <!-- Контент табов -->
                        <div class="p-6">
                            <!-- Услуги -->
                            <div v-show="activeTab === 'services'">
                                <div class="space-y-4">
                                    <div
                                        v-for="service in master.services"
                                        :key="service.id"
                                        class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                        @click="openBookingModal(service)"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-grow">
                                                <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
                                                <p class="text-sm text-gray-600 mt-1">{{ service.description }}</p>
                                                <div class="flex items-center gap-4 mt-2 text-sm">
                                                    <span class="text-gray-500">
                                                        <ClockIcon class="w-4 h-4 inline mr-1" />
                                                        {{ service.duration }} мин
                                                    </span>
                                                    <span v-if="service.home_service" class="text-blue-600">
                                                        <HomeIcon class="w-4 h-4 inline mr-1" />
                                                        Выезд
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-right ml-4">
                                                <div class="text-lg font-bold text-gray-900">
                                                    {{ formatPrice(service.price) }} ₽
                                                </div>
                                                <button class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                                    Записаться →
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- О мастере -->
                            <div v-show="activeTab === 'about'">
                                <div class="prose max-w-none">
                                    <h3 class="text-lg font-semibold mb-4">Обо мне</h3>
                                    <p class="text-gray-700 whitespace-pre-line">{{ master.bio || master.description || 'Информация о мастере скоро будет добавлена.' }}</p>
                                    
                                    <!-- Образование и опыт -->
                                    <div v-if="master.education || master.experience_description" class="mt-6">
                                        <h3 class="text-lg font-semibold mb-4">Образование и опыт</h3>
                                        <div class="space-y-3">
                                            <div v-if="master.education" class="flex items-start gap-3">
                                                <AcademicCapIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <div class="font-medium">Образование</div>
                                                    <div class="text-sm text-gray-600">{{ master.education }}</div>
                                                </div>
                                            </div>
                                            <div v-if="master.experience_description" class="flex items-start gap-3">
                                                <BriefcaseIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <div class="font-medium">Опыт работы</div>
                                                    <div class="text-sm text-gray-600">{{ master.experience_description }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Сертификаты -->
                                    <div v-if="master.certificates?.length" class="mt-6">
                                        <h3 class="text-lg font-semibold mb-4">Сертификаты и дипломы</h3>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <div 
                                                v-for="(cert, index) in master.certificates"
                                                :key="index"
                                                class="border rounded-lg overflow-hidden cursor-pointer hover:shadow-md transition-shadow"
                                                @click="openCertificate(cert)"
                                            >
                                                <img 
                                                    :src="cert.thumbnail || cert.image" 
                                                    :alt="cert.name"
                                                    class="w-full h-32 object-cover"
                                                >
                                                <div class="p-3">
                                                    <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ cert.name }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">{{ cert.year }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Отзывы -->
                            <div v-show="activeTab === 'reviews'">
                                <!-- Сводка отзывов как на Ozon -->
                                <div v-if="master.reviews_count > 0" class="mb-6">
                                    <div class="flex items-center gap-8">
                                        <div class="text-center">
                                            <div class="text-4xl font-bold text-gray-900">{{ master.rating }}</div>
                                            <StarRating :rating="master.rating" class="mt-1" />
                                            <div class="text-sm text-gray-500 mt-1">
                                                {{ master.reviews_count }} {{ pluralize(master.reviews_count, ['отзыв', 'отзыва', 'отзывов']) }}
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <div v-for="i in 5" :key="i" class="flex items-center gap-2 mb-1">
                                                <span class="text-sm text-gray-600 w-3">{{ 6 - i }}</span>
                                                <div class="flex-grow bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        class="bg-yellow-400 h-2 rounded-full"
                                                        :style="`width: ${getReviewPercent(6 - i)}%`"
                                                    ></div>
                                                </div>
                                                <span class="text-sm text-gray-600 w-10 text-right">
                                                    {{ getReviewCount(6 - i) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <ReviewsList 
                                    :reviews="master.reviews || []" 
                                    :master-id="master.id"
                                    :can-write-review="canWriteReview"
                                />
                            </div>

                            <!-- Портфолио -->
                            <div v-show="activeTab === 'portfolio'">
                                <div v-if="master.portfolio_photos?.length" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div
                                        v-for="(photo, index) in master.portfolio_photos"
                                        :key="index"
                                        class="relative group cursor-pointer rounded-lg overflow-hidden"
                                        @click="openGallery(index)"
                                    >
                                        <img 
                                            :src="photo.thumbnail || photo.path || photo"
                                            :alt="`Работа ${index + 1}`"
                                            class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                        >
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors">
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <MagnifyingGlassPlusIcon class="w-8 h-8 text-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12">
                                    <PhotoIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                    <p class="text-gray-500">Портфолио пока не добавлено</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка: Информация и действия (5 колонок) -->
                <div class="hidden lg:block lg:col-span-5">
                    <div class="sticky top-24">
                        <!-- Основная информация -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
                            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                {{ master.display_name || master.name }}
                                <CheckBadgeIcon v-if="master.is_verified" class="w-6 h-6 text-blue-500" />
                            </h1>
                            
                            <!-- Рейтинг и отзывы -->
                            <div class="flex items-center gap-4 mt-3">
                                <div class="flex items-center">
                                    <StarRating :rating="master.rating" />
                                    <span class="ml-2 font-medium">{{ master.rating }}</span>
                                </div>
                                <Link 
                                    :href="`#reviews`" 
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    {{ master.reviews_count }} {{ pluralize(master.reviews_count, ['отзыв', 'отзыва', 'отзывов']) }}
                                </Link>
                            </div>
                            
                            <!-- Статистика как на Avito -->
                            <div class="grid grid-cols-3 gap-4 mt-4 py-4 border-y">
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ master.experience_years }}
                                    </div>
                                    <div class="text-xs text-gray-500">лет опыта</div>
                                </div>
                                <div class="text-center border-x">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ master.completed_bookings || '500+' }}
                                    </div>
                                    <div class="text-xs text-gray-500">сеансов</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ master.repeat_clients_percent || '85' }}%
                                    </div>
                                    <div class="text-xs text-gray-500">повторных</div>
                                </div>
                            </div>
                            
                            <!-- Цены -->
                            <div class="mt-4">
                                <div class="text-3xl font-bold text-gray-900">
                                    от {{ formatPrice(master.price_from) }} ₽
                                </div>
                                <div v-if="master.price_to" class="text-sm text-gray-600">
                                    до {{ formatPrice(master.price_to) }} ₽ за сеанс
                                </div>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="space-y-3 mt-6">
                                <button 
                                    @click="openBookingModal()"
                                    class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-indigo-700 transition-colors"
                                >
                                    Записаться на приём
                                </button>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        @click="showPhone = !showPhone"
                                        class="flex items-center justify-center gap-2 py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                    >
                                        <PhoneIcon class="w-4 h-4" />
                                        <span v-if="!showPhone">Показать</span>
                                        <span v-else class="text-sm">{{ formatPhone(master.phone) }}</span>
                                    </button>
                                    
                                    <button 
                                        v-if="master.whatsapp"
                                        @click="openWhatsApp()"
                                        class="flex items-center justify-center gap-2 py-2 px-4 border border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition-colors"
                                    >
                                        <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                        WhatsApp
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Дополнительные действия -->
                            <div class="flex items-center justify-between mt-4 pt-4 border-t">
                                <button 
                                    @click="toggleFavorite"
                                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-red-500"
                                    :class="{ 'text-red-500': isFavorite }"
                                >
                                    <HeartIcon class="w-5 h-5" />
                                    {{ isFavorite ? 'В избранном' : 'В избранное' }}
                                </button>
                                <button 
                                    @click="share"
                                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
                                >
                                    <ShareIcon class="w-5 h-5" />
                                    Поделиться
                                </button>
                            </div>
                        </div>
                        
                        <!-- Локация и условия -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Локация и условия</h3>
                            
                            <div class="space-y-3">
                                <!-- Метро -->
                                <div v-if="master.metro_station" class="flex items-start gap-3">
                                    <MapPinIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                    <div>
                                        <div class="font-medium">м. {{ master.metro_station }}</div>
                                        <div class="text-sm text-gray-600">{{ master.metro_distance || '5 мин пешком' }}</div>
                                    </div>
                                </div>
                                
                                <!-- Район -->
                                <div v-if="master.district" class="flex items-start gap-3">
                                    <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                    <div>
                                        <div class="font-medium">{{ master.district }}</div>
                                        <div class="text-sm text-gray-600">{{ master.city }}</div>
                                    </div>
                                </div>
                                
                                <!-- Формат работы -->
                                <div class="flex items-start gap-3">
                                    <HomeModernIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                    <div>
                                        <div class="font-medium">Формат работы</div>
                                        <div class="text-sm text-gray-600">
                                            <span v-if="master.salon_service" class="block">✓ Приём в салоне</span>
                                            <span v-if="master.home_service" class="block">✓ Выезд на дом 
                                                <span v-if="master.home_service_price" class="text-gray-500">
                                                    (+{{ formatPrice(master.home_service_price) }} ₽)
                                                </span>
                                            </span>
                                            <span v-if="master.online_consultation" class="block">✓ Онлайн консультации</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- График работы -->
                                <div class="flex items-start gap-3">
                                    <CalendarIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                    <div>
                                        <div class="font-medium">График работы</div>
                                        <div class="text-sm text-gray-600">
                                            {{ master.schedule_description || 'Пн-Вс: 10:00 - 21:00' }}
                                        </div>
                                        <button class="text-sm text-indigo-600 hover:text-indigo-700 mt-1">
                                            Смотреть расписание →
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Мини-карта -->
                            <div v-if="master.show_exact_address" class="mt-4 h-48 bg-gray-100 rounded-lg">
                                <!-- Здесь будет карта -->
                                <div class="h-full flex items-center justify-center text-gray-500">
                                    <MapIcon class="w-8 h-8" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Похожие мастера -->
            <div v-if="similarMasters?.length" class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Похожие специалисты</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <MasterCard 
                        v-for="similar in similarMasters"
                        :key="similar.id"
                        :master="similar"
                    />
                </div>
            </div>

            <!-- Фиксированная панель записи (мобильная версия) -->
            <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4 lg:hidden z-40">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold text-gray-900">
                            от {{ formatPrice(master.price_from) }} ₽
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ master.services?.length || 0 }} услуг
                        </div>
                    </div>
                    <button 
                        @click="openBookingModal()"
                        class="bg-indigo-600 text-white py-2.5 px-6 rounded-lg font-medium"
                    >
                        Записаться
                    </button>
                </div>
            </div>
        <!-- Модальные окна -->
        <BookingModal 
            v-if="showBookingModal"
            :master="master"
            :service="selectedService"
            @close="showBookingModal = false"
        />
        
        <ImageGalleryModal
            v-if="showGalleryModal"
            :images="galleryImages"
            :initial-index="galleryInitialIndex"
            @close="showGalleryModal = false"
        />
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    HeartIcon, 
    ShareIcon, 
    MapPinIcon, 
    BuildingOfficeIcon,
    ClockIcon,
    HomeIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    CheckBadgeIcon,
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    MagnifyingGlassPlusIcon,
    PhotoIcon,
    CalendarIcon,
    HomeModernIcon,
    MapIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import MetaTags from '@/Components/MetaTags.vue'
import StarRating from '@/Components/Common/StarRating.vue'
import ReviewsList from '@/Components/Reviews/ReviewsList.vue'
import BookingModal from '@/Components/Booking/BookingModal.vue'
import MasterCard from '@/Components/Cards/MasterCard.vue'
import ImageGalleryModal from '@/Components/Common/ImageGalleryModal.vue'
import { useMasterStore } from '@/stores/masterStore'
import { pluralize, formatPhone } from '@/utils/helpers'

const props = defineProps({
    master: Object,
    meta: Object,
    similarMasters: Array,
    canWriteReview: Boolean
})

const masterStore = useMasterStore()
const page = usePage()

// Состояния
const activeTab = ref('services')
const showBookingModal = ref(false)
const selectedService = ref(null)
const showPhone = ref(false)
const currentImageIndex = ref(0)
const showGalleryModal = ref(false)
const galleryImages = ref([])
const galleryInitialIndex = ref(0)

// Вычисляемые свойства
const allImages = computed(() => {
    const images = []
    if (props.master.avatar) images.push(props.master.avatar)
    if (props.master.workspace_photos?.length) {
        images.push(...props.master.workspace_photos)
    }
    if (props.master.portfolio_photos?.length) {
        images.push(...props.master.portfolio_photos.slice(0, 3))
    }
    return images.length ? images : ['/images/no-photo.jpg']
})

const currentImage = computed(() => allImages.value[currentImageIndex.value])

const tabs = computed(() => [
    { 
        id: 'services', 
        name: 'Услуги',
        badge: props.master.services?.length 
    },
    { 
        id: 'about', 
        name: 'О мастере' 
    },
    { 
        id: 'reviews', 
        name: 'Отзывы',
        badge: props.master.reviews_count 
    },
    { 
        id: 'portfolio', 
        name: 'Портфолио',
        badge: props.master.portfolio_photos?.length 
    }
])

const isFavorite = computed(() => masterStore.isInFavorites(props.master.id))

// Методы
const toggleFavorite = () => {
    masterStore.toggleFavorite(props.master.id)
}

const share = async () => {
    const shareData = {
        title: `${props.master.display_name || props.master.name} - массажист`,
        text: `Массажист ${props.master.display_name || props.master.name} в ${props.master.city}. Рейтинг: ${props.master.rating}⭐`,
        url: window.location.href
    }
    
    try {
        if (navigator.share) {
            await navigator.share(shareData)
        } else {
            await navigator.clipboard.writeText(window.location.href)
            // TODO: показать toast уведомление
        }
    } catch (err) {
        console.error('Ошибка при попытке поделиться:', err)
    }
}

const openBookingModal = (service = null) => {
    selectedService.value = service
    showBookingModal.value = true
}

const openWhatsApp = () => {
    const phone = props.master.whatsapp.replace(/\D/g, '')
    const message = encodeURIComponent(`Здравствуйте! Я с сайта ${page.props.app.name}. Хотел бы записаться на массаж.`)
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
}

const openGallery = (index) => {
    galleryImages.value = props.master.portfolio_photos || []
    galleryInitialIndex.value = index
    showGalleryModal.value = true
}

const openCertificate = (cert) => {
    galleryImages.value = [cert.image]
    galleryInitialIndex.value = 0
    showGalleryModal.value = true
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price)
}

// Функции для распределения отзывов по рейтингу
const getReviewCount = (rating) => {
    if (!props.master.reviews_distribution) return 0
    return props.master.reviews_distribution[rating] || 0
}

const getReviewPercent = (rating) => {
    if (!props.master.reviews_count) return 0
    return Math.round((getReviewCount(rating) / props.master.reviews_count) * 100)
}
</script>

<style scoped>
/* Скрываем скроллбар но оставляем функциональность */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Анимация для hover эффектов */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}
</style>