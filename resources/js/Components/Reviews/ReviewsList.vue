﻿<template>
    <div>
        <!-- Фильтры отзывов как на Ozon -->
        <div v-if="reviews.length > 0" class="mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    @click="activeFilter = filter.value"
                    class="px-4 py-2 rounded-lg border text-sm font-medium transition-all"
                    :class="[
                        activeFilter === filter.value
                            ? 'bg-indigo-50 border-indigo-300 text-indigo-700'
                            : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                    ]"
                >
                    {{ filter.label }}
                    <span v-if="filter.count" class="ml-1 text-gray-500">
                        ({{ filter.count }})
                    </span>
                </button>
            </div>
        </div>

        <!-- Кнопка написать отзыв -->
        <div v-if="canWriteReview && !hasUserReview" class="mb-6">
            <button
                @click="showReviewForm = true"
                class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors"
            >
                Написать отзыв
            </button>
        </div>

        <!-- Форма отзыва -->
        <Transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-2"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-2"
        >
            <div v-if="showReviewForm" class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Ваш отзыв</h3>
                
                <form @submit.prevent="submitReview">
                    <!-- Рейтинг -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Оценка
                        </label>
                        <div class="flex gap-2">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="newReview.rating = star"
                                @mouseover="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                class="text-3xl transition-colors"
                                :class="[
                                    (hoverRating || newReview.rating) >= star
                                        ? 'text-yellow-400'
                                        : 'text-gray-300'
                                ]"
                            >
                                ★
                            </button>
                        </div>
                    </div>

                    <!-- Категории оценок -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div v-for="category in ratingCategories" :key="category.key">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ category.label }}
                            </label>
                            <div class="flex gap-1">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    @click="newReview.categories[category.key] = star"
                                    class="text-xl transition-colors"
                                    :class="[
                                        (newReview.categories[category.key] || 0) >= star
                                            ? 'text-yellow-400'
                                            : 'text-gray-300'
                                    ]"
                                >
                                    ★
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Текст отзыва -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Комментарий
                        </label>
                        <textarea
                            v-model="newReview.comment"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Расскажите о вашем опыте..."
                        ></textarea>
                    </div>

                    <!-- Загрузка фото -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Фотографии (необязательно)
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <label class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400 transition-colors">
                                <CameraIcon class="w-6 h-6 text-gray-400" />
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleFileUpload"
                                >
                            </label>
                            <div
                                v-for="(photo, index) in newReview.photos"
                                :key="index"
                                class="relative w-24 h-24"
                            >
                                <img
                                    :src="photo.preview"
                                    class="w-full h-full object-cover rounded-lg"
                                >
                                <button
                                    type="button"
                                    @click="removePhoto(index)"
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="!newReview.rating || processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ processing ? 'Отправка...' : 'Отправить отзыв' }}
                        </button>
                        <button
                            type="button"
                            @click="cancelReview"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors"
                        >
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- Список отзывов -->
        <div v-if="filteredReviews.length > 0" class="space-y-6">
            <div
                v-for="review in paginatedReviews"
                :key="review.id"
                class="bg-white rounded-lg border p-6"
            >
                <!-- Шапка отзыва -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <img
                            :src="review.user?.avatar || '/images/default-avatar.jpg'"
                            :alt="review.user?.name || 'Пользователь'"
                            class="w-10 h-10 rounded-full object-cover"
                        >
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ review.user?.name || 'Пользователь' }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <StarRating :rating="review.rating" :size="'sm'" />
                                <span>•</span>
                                <time :datetime="review.created_at">
                                    {{ formatDate(review.created_at) }}
                                </time>
                                <span v-if="review.is_verified" class="text-green-600">
                                    • ✓ Подтвержденная покупка
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Действия -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="toggleHelpful(review.id)"
                            class="text-sm text-gray-500 hover:text-gray-700"
                            :class="{ 'text-indigo-600': review.user_helpful }"
                        >
                            👍 Полезно ({{ review.helpful_count || 0 }})
                        </button>
                    </div>
                </div>

                <!-- Категории оценок -->
                <div v-if="review.categories" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
                    <div v-for="(value, key) in review.categories" :key="key" class="flex items-center gap-1">
                        <span class="text-gray-600">{{ getCategoryLabel(key) }}:</span>
                        <StarRating :rating="value" :size="'xs'" />
                    </div>
                </div>

                <!-- Текст отзыва -->
                <p class="text-gray-700 mb-4">{{ review.comment }}</p>

                <!-- Фотографии -->
                <div v-if="review.photos?.length" class="flex flex-wrap gap-2 mb-4">
                    <img
                        v-for="(photo, index) in review.photos"
                        :key="index"
                        :src="photo.thumbnail || photo.url"
                        :alt="`Фото ${index + 1}`"
                        class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:opacity-90"
                        @click="openPhotoGallery(review.photos, index)"
                    >
                </div>

                <!-- Ответ мастера -->
                <div v-if="review.master_response" class="mt-4 pl-4 border-l-4 border-gray-200">
                    <div class="text-sm font-medium text-gray-900 mb-1">
                        Ответ мастера:
                    </div>
                    <p class="text-sm text-gray-700">{{ review.master_response }}</p>
                    <time class="text-xs text-gray-500">
                        {{ formatDate(review.response_created_at) }}
                    </time>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="text-center py-12">
            <ChatBubbleBottomCenterTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <p class="text-gray-500">
                {{ activeFilter === 'all' ? 'Пока нет отзывов' : 'Нет отзывов по выбранному фильтру' }}
            </p>
        </div>

        <!-- Пагинация -->
        <div v-if="totalPages > 1" class="mt-6 flex justify-center">
            <nav class="flex gap-1">
                <button
                    v-for="page in totalPages"
                    :key="page"
                    @click="currentPage = page"
                    class="w-10 h-10 rounded-lg font-medium transition-colors"
                    :class="[
                        currentPage === page
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-100'
                    ]"
                >
                    {{ page }}
                </button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { CameraIcon, XMarkIcon, ChatBubbleBottomCenterTextIcon } from '@heroicons/vue/24/outline'
import StarRating from '@/Components/Common/StarRating.vue'
import { formatDate } from '@/utils/helpers'

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    },
    masterId: {
        type: Number,
        required: true
    },
    canWriteReview: {
        type: Boolean,
        default: false
    },
    hasUserReview: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['photo-gallery-open'])

// Состояния
const activeFilter = ref('all')
const currentPage = ref(1)
const reviewsPerPage = 5
const showReviewForm = ref(false)
const hoverRating = ref(0)
const processing = ref(false)

// Форма нового отзыва
const newReview = ref({
    rating: 0,
    comment: '',
    categories: {
        professionalism: 0,
        cleanliness: 0,
        technique: 0,
        atmosphere: 0,
        value: 0
    },
    photos: []
})

// Категории оценок
const ratingCategories = [
    { key: 'professionalism', label: 'Профессионализм' },
    { key: 'cleanliness', label: 'Чистота' },
    { key: 'technique', label: 'Техника' },
    { key: 'atmosphere', label: 'Атмосфера' },
    { key: 'value', label: 'Цена/качество' }
]

// Фильтры
const filters = computed(() => {
    const allCount = props.reviews.length
    const withPhotosCount = props.reviews.filter(r => r.photos?.length).length
    const fiveStarsCount = props.reviews.filter(r => r.rating === 5).length
    const fourStarsCount = props.reviews.filter(r => r.rating === 4).length
    const lowRatingCount = props.reviews.filter(r => r.rating <= 3).length

    return [
        { value: 'all', label: 'Все отзывы', count: allCount },
        { value: 'with_photos', label: 'С фото', count: withPhotosCount },
        { value: '5_stars', label: '5 звёзд', count: fiveStarsCount },
        { value: '4_stars', label: '4 звезды', count: fourStarsCount },
        { value: 'low_rating', label: '3 и меньше', count: lowRatingCount }
    ]
})

// Фильтрованные отзывы
const filteredReviews = computed(() => {
    switch (activeFilter.value) {
        case 'with_photos':
            return props.reviews.filter(r => r.photos?.length)
        case '5_stars':
            return props.reviews.filter(r => r.rating === 5)
        case '4_stars':
            return props.reviews.filter(r => r.rating === 4)
        case 'low_rating':
            return props.reviews.filter(r => r.rating <= 3)
        default:
            return props.reviews
    }
})

// Пагинация
const totalPages = computed(() => 
    Math.ceil(filteredReviews.value.length / reviewsPerPage)
)

const paginatedReviews = computed(() => {
    const start = (currentPage.value - 1) * reviewsPerPage
    const end = start + reviewsPerPage
    return filteredReviews.value.slice(start, end)
})

// Методы
const submitReview = async () => {
    processing.value = true
    
    const form = useForm({
        master_id: props.masterId,
        rating: newReview.value.rating,
        comment: newReview.value.comment,
        categories: newReview.value.categories,
        photos: newReview.value.photos.map(p => p.file)
    })

    form.post(route('reviews.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showReviewForm.value = false
            resetReviewForm()
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const cancelReview = () => {
    showReviewForm.value = false
    resetReviewForm()
}

const resetReviewForm = () => {
    newReview.value = {
        rating: 0,
        comment: '',
        categories: {
            professionalism: 0,
            cleanliness: 0,
            technique: 0,
            atmosphere: 0,
            value: 0
        },
        photos: []
    }
    hoverRating.value = 0
}

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files)
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader()
            reader.onload = (e) => {
                newReview.value.photos.push({
                    file: file,
                    preview: e.target.result
                })
            }
            reader.readAsDataURL(file)
        }
    })
}

const removePhoto = (index) => {
    newReview.value.photos.splice(index, 1)
}

const toggleHelpful = (reviewId) => {
    // Отправка на сервер
    router.post(route('reviews.helpful', reviewId), {}, {
        preserveScroll: true,
        preserveState: true
    })
}

const getCategoryLabel = (key) => {
    const category = ratingCategories.find(c => c.key === key)
    return category ? category.label : key
}

const openPhotoGallery = (photos, index) => {
    emit('photo-gallery-open', {
        images: photos.map(p => p.url || p.original),
        initialIndex: index
    })
}
</script>