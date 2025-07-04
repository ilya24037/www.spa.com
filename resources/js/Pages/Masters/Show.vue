<template>
    <MetaTags :meta="meta" />
        <!-- Контейнер в стиле Feipiter -->
        <div class="min-h-screen bg-gray-50">
            <!-- Хлебные крошки -->
            <div class="bg-white border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <nav class="flex text-sm text-gray-500">
                        <Link href="/" class="hover:text-gray-700">Главная</Link>
                        <span class="mx-2">/</span>
                        <Link href="/masters" class="hover:text-gray-700">Мастера</Link>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">{{ master.display_name || master.name }}</span>
                    </nav>
                </div>
            </div>

            <!-- Основной контент -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Левая колонка: Галерея (5 колонок на десктопе) -->
                    <div class="lg:col-span-5">
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-6">
                            <!-- Основное фото -->
                            <div class="relative">
                                <div class="aspect-w-3 aspect-h-4">
                                    <img 
                                        :src="currentPhoto" 
                                        :alt="master.display_name || master.name"
                                        class="w-full h-full object-cover"
                                        @error="handleImageError"
                                    >
                                </div>
                                
                                <!-- Бейджи поверх фото -->
                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                    <span v-if="master.is_premium" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-lg">
                                        ⭐ ПРЕМИУМ
                                    </span>
                                    <span v-if="master.is_verified" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg">
                                        ✓ Проверен
                                    </span>
                                    <span v-if="master.is_new" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-pink-500 text-white shadow-lg">
                                        NEW
                                    </span>
                                </div>
                                
                                <!-- Навигация по фото -->
                                <div v-if="allPhotos.length > 1" class="absolute bottom-4 left-0 right-0 flex justify-center gap-2">
                                    <button
                                        v-for="(_, index) in allPhotos"
                                        :key="index"
                                        @click="currentPhotoIndex = index"
                                        class="w-2 h-2 rounded-full transition-all"
                                        :class="currentPhotoIndex === index ? 'bg-white w-8' : 'bg-white/60'"
                                    ></button>
                                </div>
                            </div>
                            
                            <!-- Превью фотографий -->
                            <div v-if="allPhotos.length > 1" class="p-3 bg-gray-50">
                                <div class="grid grid-cols-4 gap-2">
                                    <button
                                        v-for="(photo, index) in allPhotos.slice(0, 8)"
                                        :key="`thumb-${index}`"
                                        @click="currentPhotoIndex = index"
                                        class="relative aspect-square rounded-lg overflow-hidden border-2 transition-all"
                                        :class="currentPhotoIndex === index ? 'border-indigo-500' : 'border-transparent'"
                                    >
                                        <img 
                                            :src="photo" 
                                            :alt="`Фото ${index + 1}`"
                                            class="w-full h-full object-cover"
                                        >
                                        <div 
                                            v-if="index === 7 && allPhotos.length > 8"
                                            class="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-medium"
                                        >
                                            +{{ allPhotos.length - 8 }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Правая колонка: Информация (7 колонок на десктопе) -->
                    <div class="lg:col-span-7">
                        <!-- Основная информация -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
                            <!-- Заголовок и статус -->
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900">
                                        {{ master.display_name || master.name }}
                                    </h1>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="text-gray-600">{{ master.age || 30 }} лет</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-gray-600">ID: {{ master.id }}</span>
                                        <span v-if="master.is_online" class="flex items-center gap-1 text-green-600">
                                            <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                                            Онлайн
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        @click="toggleFavorite"
                                        class="p-2 rounded-lg border hover:bg-gray-50 transition-colors"
                                        :class="{ 'text-red-500 border-red-500': isFavorite }"
                                    >
                                        <HeartIcon class="w-5 h-5" />
                                    </button>
                                    <button 
                                        @click="share"
                                        class="p-2 rounded-lg border hover:bg-gray-50 transition-colors"
                                    >
                                        <ShareIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Контактная информация -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <button 
                                    @click="showPhone = !showPhone"
                                    class="flex items-center justify-center gap-2 py-3 px-4 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium"
                                >
                                    <PhoneIcon class="w-5 h-5" />
                                    {{ showPhone ? formatPhone(master.phone) : 'Показать телефон' }}
                                </button>
                                
                                <button 
                                    v-if="master.whatsapp"
                                    @click="openWhatsApp()"
                                    class="flex items-center justify-center gap-2 py-3 px-4 border-2 border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition-colors font-medium"
                                >
                                    <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                    WhatsApp
                                </button>
                            </div>
                            
                            <!-- Быстрая информация -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-4 border-y">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ master.rating || '5.0' }}
                                    </div>
                                    <div class="text-xs text-gray-500">Рейтинг</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ master.reviews_count || 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500">Отзывов</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ master.experience_years || 5 }}
                                    </div>
                                    <div class="text-xs text-gray-500">Лет опыта</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ master.repeat_clients_percent || 85 }}%
                                    </div>
                                    <div class="text-xs text-gray-500">Повторных</div>
                                </div>
                            </div>
                            
                            <!-- Локация -->
                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-900 mb-2">Локация</h3>
                                <div class="space-y-2">
                                    <div v-if="master.metro_station" class="flex items-center gap-2 text-gray-600">
                                        <MapPinIcon class="w-4 h-4" />
                                        <span>Метро {{ master.metro_station }}</span>
                                        <span class="text-gray-400">{{ master.metro_distance || '5 мин' }}</span>
                                    </div>
                                    <div v-if="master.district" class="flex items-center gap-2 text-gray-600">
                                        <BuildingOfficeIcon class="w-4 h-4" />
                                        <span>{{ master.district }}, {{ master.city || 'Москва' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Цены -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Цены</h2>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-3 border-b">
                                    <div>
                                        <div class="font-medium">Классический массаж</div>
                                        <div class="text-sm text-gray-500">60 минут</div>
                                    </div>
                                    <div class="text-xl font-bold text-orange-500">
                                        {{ formatPrice(master.price_from || 3000) }} ₽
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b">
                                    <div>
                                        <div class="font-medium">Расслабляющий массаж</div>
                                        <div class="text-sm text-gray-500">90 минут</div>
                                    </div>
                                    <div class="text-xl font-bold text-orange-500">
                                        {{ formatPrice(4500) }} ₽
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center py-3">
                                    <div>
                                        <div class="font-medium">Выезд на дом</div>
                                        <div class="text-sm text-gray-500">Дополнительно</div>
                                    </div>
                                    <div class="text-xl font-bold text-orange-500">
                                        +{{ formatPrice(master.home_service_price || 1000) }} ₽
                                    </div>
                                </div>
                            </div>
                            
                            <button 
                                @click="openBookingModal()"
                                class="w-full mt-6 bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition-colors"
                            >
                                Записаться на приём
                            </button>
                        </div>
                        
                        <!-- Услуги -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Услуги</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div 
                                    v-for="service in displayServices"
                                    :key="service.id || service.name"
                                    class="flex items-center gap-2"
                                >
                                    <CheckIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    <span class="text-gray-700">{{ service.name }}</span>
                                    <span v-if="service.is_new" class="text-xs bg-pink-100 text-pink-600 px-2 py-0.5 rounded-full">NEW</span>
                                </div>
                            </div>
                            
                            <div v-if="master.additional_info" class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h3 class="font-medium text-gray-900 mb-2">Дополнительная информация</h3>
                                <p class="text-sm text-gray-600">{{ master.additional_info }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Табы с дополнительной информацией -->
                <div class="mt-8 bg-white rounded-lg shadow-sm">
                    <div class="border-b">
                        <nav class="flex -mb-px">
                            <button
                                v-for="tab in bottomTabs"
                                :key="tab.id"
                                @click="activeBottomTab = tab.id"
                                class="py-4 px-6 text-sm font-medium border-b-2 transition-colors"
                                :class="[
                                    activeBottomTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                ]"
                            >
                                {{ tab.name }}
                            </button>
                        </nav>
                    </div>
                    
                    <div class="p-6">
                        <!-- О мастере -->
                        <div v-show="activeBottomTab === 'about'">
                            <div class="prose max-w-none">
                                <p>{{ master.bio || master.description || 'Профессиональный массажист с многолетним опытом работы.' }}</p>
                                
                                <div v-if="master.education" class="mt-4">
                                    <h3>Образование</h3>
                                    <p>{{ master.education }}</p>
                                </div>
                                
                                <div v-if="master.certificates?.length" class="mt-4">
                                    <h3>Сертификаты</h3>
                                    <div class="grid grid-cols-3 gap-4">
                                        <img 
                                            v-for="(cert, index) in master.certificates"
                                            :key="index"
                                            :src="cert.image"
                                            :alt="cert.name"
                                            class="rounded-lg cursor-pointer hover:opacity-90"
                                            @click="openCertificate(cert)"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Отзывы -->
                        <div v-show="activeBottomTab === 'reviews'">
                            <ReviewsList 
                                :reviews="master.reviews || []" 
                                :master-id="master.id"
                                :can-write-review="canWriteReview"
                            />
                        </div>
                        
                        <!-- График работы -->
                        <div v-show="activeBottomTab === 'schedule'">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="font-semibold mb-3">Рабочие дни</h3>
                                    <div class="space-y-2">
                                        <div v-for="day in workDays" :key="day.name" class="flex justify-between">
                                            <span class="text-gray-600">{{ day.name }}</span>
                                            <span class="font-medium">{{ day.hours }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="font-semibold mb-3">Ближайшие свободные окна</h3>
                                    <div class="space-y-2">
                                        <button 
                                            v-for="slot in availableSlots"
                                            :key="slot.datetime"
                                            @click="openBookingModal(null, slot)"
                                            class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                        >
                                            <div class="font-medium">{{ slot.date }}</div>
                                            <div class="text-sm text-gray-600">{{ slot.time }}</div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <BookingModal 
            v-if="showBookingModal"
            :master="master"
            :service="selectedService"
            :selected-slot="selectedSlot"
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
    PhoneIcon,
    ChatBubbleLeftRightIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import MetaTags from '@/Components/MetaTags.vue'
import ReviewsList from '@/Components/Reviews/ReviewsList.vue'
import BookingModal from '@/Components/Booking/BookingModal.vue'
import ImageGalleryModal from '@/Components/Common/ImageGalleryModal.vue'
import { useMasterStore } from '@/stores/masterStore'
import { formatPhone } from '@/utils/helpers'

const props = defineProps({
    master: Object,
    meta: Object,
    canWriteReview: Boolean
})

const masterStore = useMasterStore()
const page = usePage()

// Состояния
const showPhone = ref(false)
const showBookingModal = ref(false)
const selectedService = ref(null)
const selectedSlot = ref(null)
const currentPhotoIndex = ref(0)
const activeBottomTab = ref('about')
const showGalleryModal = ref(false)
const galleryImages = ref([])
const galleryInitialIndex = ref(0)

// Вычисляемые свойства
const allPhotos = computed(() => {
    const photos = []
    if (props.master.avatar) photos.push(props.master.avatar)
    if (props.master.photos?.length) photos.push(...props.master.photos)
    if (props.master.workspace_photos?.length) photos.push(...props.master.workspace_photos)
    if (props.master.portfolio_photos?.length) photos.push(...props.master.portfolio_photos)
    return photos.length ? photos : ['/images/no-photo.jpg']
})

const currentPhoto = computed(() => allPhotos.value[currentPhotoIndex.value])

const displayServices = computed(() => {
    return props.master.services || [
        { name: 'Классический массаж' },
        { name: 'Расслабляющий массаж' },
        { name: 'Лечебный массаж' },
        { name: 'Антицеллюлитный массаж' },
        { name: 'Спортивный массаж' },
        { name: 'Массаж лица', is_new: true }
    ]
})

const bottomTabs = [
    { id: 'about', name: 'О мастере' },
    { id: 'reviews', name: `Отзывы (${props.master.reviews_count || 0})` },
    { id: 'schedule', name: 'График работы' }
]

const workDays = [
    { name: 'Понедельник', hours: '10:00 - 21:00' },
    { name: 'Вторник', hours: '10:00 - 21:00' },
    { name: 'Среда', hours: '10:00 - 21:00' },
    { name: 'Четверг', hours: '10:00 - 21:00' },
    { name: 'Пятница', hours: '10:00 - 21:00' },
    { name: 'Суббота', hours: '12:00 - 19:00' },
    { name: 'Воскресенье', hours: 'Выходной' }
]

const availableSlots = [
    { date: 'Сегодня, 4 июля', time: '15:00 - 16:00', datetime: '2025-07-04T15:00' },
    { date: 'Сегодня, 4 июля', time: '18:00 - 19:00', datetime: '2025-07-04T18:00' },
    { date: 'Завтра, 5 июля', time: '10:00 - 11:00', datetime: '2025-07-05T10:00' },
    { date: 'Завтра, 5 июля', time: '14:00 - 15:00', datetime: '2025-07-05T14:00' }
]

const isFavorite = computed(() => masterStore.isInFavorites(props.master.id))

// Методы
const toggleFavorite = () => {
    masterStore.toggleFavorite(props.master.id)
}

const share = async () => {
    try {
        await navigator.share({
            title: props.master.display_name || props.master.name,
            url: window.location.href
        })
    } catch (err) {
        await navigator.clipboard.writeText(window.location.href)
    }
}

const openBookingModal = (service = null, slot = null) => {
    selectedService.value = service
    selectedSlot.value = slot
    showBookingModal.value = true
}

const openWhatsApp = () => {
    const phone = props.master.whatsapp.replace(/\D/g, '')
    const message = encodeURIComponent(`Здравствуйте! Хочу записаться на массаж.`)
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
}

const openCertificate = (cert) => {
    galleryImages.value = [cert.image]
    galleryInitialIndex.value = 0
    showGalleryModal.value = true
}

const handleImageError = (event) => {
    event.target.src = '/images/no-photo.jpg'
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price)
}
</script>

<style scoped>
.aspect-w-3 {
    position: relative;
    padding-bottom: 133.33%; /* 3:4 Aspect Ratio */
}

.aspect-w-3 > img {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}

.aspect-square {
    aspect-ratio: 1 / 1;
}
</style>