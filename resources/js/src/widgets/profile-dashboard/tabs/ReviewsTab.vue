<!-- resources/js/src/widgets/profile-dashboard/tabs/ReviewsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Используем новый компонент ReviewList с CRUD функционалом -->
    <ReviewList
      :user-id="userId"
      :can-add-review="canAddReview"
      :can-moderate="canModerate"
      :show-status-filter="canModerate"
    />
  </div>
</template>

<script setup lang="ts">
import ReviewList from '@/features/review-management/ui/ReviewList/ReviewList.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'

// Получаем данные текущего пользователя
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Props для компонента
interface Props {
  userId?: number
  canAddReview?: boolean
  canModerate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  userId: computed(() => user.value?.id),
  canAddReview: true,
  canModerate: computed(() => user.value?.role === 'admin' || user.value?.role === 'moderator')
})
</script>