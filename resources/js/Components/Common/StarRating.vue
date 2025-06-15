<template>
    <div class="flex items-center gap-1">
        <div class="flex">
            <span v-for="star in 5" :key="star"
                  @click="interactive ? updateRating(star) : null"
                  :class="[
                      'text-2xl transition-colors',
                      interactive ? 'cursor-pointer hover:scale-110' : 'cursor-default',
                      star <= currentRating ? 'text-yellow-400' : 'text-gray-300'
                  ]"
            >
                ★
            </span>
        </div>
        <span v-if="showText" class="ml-2 text-sm text-gray-600">
            {{ currentRating }}.0
        </span>
        <span v-if="showCount && count" class="ml-1 text-sm text-gray-500">
            ({{ count }})
        </span>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    rating: {
        type: Number,
        default: 0
    },
    interactive: {
        type: Boolean,
        default: false
    },
    showText: {
        type: Boolean,
        default: true
    },
    showCount: {
        type: Boolean,
        default: false
    },
    count: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['update:rating'])

const currentRating = ref(props.rating)

watch(() => props.rating, (newVal) => {
    currentRating.value = newVal
})

const updateRating = (star) => {
    if (!props.interactive) return
    currentRating.value = star
    emit('update:rating', star)
}
</script>