<template>
    <div class="space-y-6">
        <!-- Заголовок с общим рейтингом -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold">Отзывы клиентов</h3>
            <div class="flex items-center gap-4">
                <StarRating 
                    :rating="averageRating" 
                    :interactive="false"
                    :show-count="true"
                    :count="reviews.length"
                />
                <button 
                    v-if="canWriteReview"
                    @click="showReviewForm = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Написать отзыв
                </button>
            </div>
        </div>

        <!-- Список отзывов -->
        <div v-if="reviews.length > 0" class="space-y-4">
            <div v-for="review in reviews" 
                 :key="review.id" 
                 class="bg-white p-6 rounded-lg shadow-sm border border-gray-100"
            >
                <!-- Шапка отзыва -->
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="font-semibold">{{ review.client.name }}</h4>
                        <p class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</p>
                    </div>
                    <StarRating 
                        :rating="review.rating_overall" 
                        :interactive="false"
                        :show-text="false"
                    />
                </div>

                <!-- Услуга -->
                <p class="text-sm text-gray-600 mb-2">
                    Услуга: <span class="font-medium">{{ review.service?.name || 'Не указана' }}</span>
                </p>

                <!-- Текст отзыва -->
                <p class="text-gray-700 leading-relaxed">{{ review.comment }}</p>

                <!-- Полезность отзыва -->
                <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100">
                    <button class="text-sm text-gray-500 hover:text-gray-700 transition">
                        👍 Полезно ({{ review.helpful_count || 0 }})
                    </button>
                    <button class="text-sm text-gray-500 hover:text-gray-700 transition">
                        👎 Не полезно
                    </button>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="text-center py-12">
            <p class="text-gray-500 mb-4">Пока нет отзывов</p>
            <button 
                v-if="canWriteReview"
                @click="showReviewForm = true"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
                Стать первым
            </button>
        </div>

        <!-- Загрузить ещё -->
        <div v-if="hasMore" class="text-center pt-4">
            <button 
                @click="loadMore"
                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
                Показать ещё отзывы
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import StarRating from '@/Components/StarRating.vue'
import { format } from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    masterProfileId: {
        type: Number,
        required: true
    },
    reviews: {
        type: Array,
        default: () => []
    },
    canWriteReview: {
        type: Boolean,
        default: false
    },
    hasMore: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['load-more', 'write-review'])

const showReviewForm = ref(false)

const averageRating = computed(() => {
    if (props.reviews.length === 0) return 0
    const sum = props.reviews.reduce((acc, review) => acc + review.rating_overall, 0)
    return Math.round(sum / props.reviews.length)
})

const formatDate = (date) => {
    return format(new Date(date), 'd MMMM yyyy', { locale: ru })
}

const loadMore = () => {
    emit('load-more')
}
</script>