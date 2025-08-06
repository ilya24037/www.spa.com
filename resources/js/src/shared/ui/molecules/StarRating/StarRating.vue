<template>
    <div class="flex items-center">
        <div class="flex">
            <span
                v-for="star in 5"
                :key="star"
                class="star"
                :class="[
                    star <= fullStars ? 'text-yellow-400' : 'text-gray-300',
                    sizeClasses[size]
                ]"
            >
                <svg
                    v-if="star <= fullStars"
                    class="w-full h-full fill-current"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <svg
                    v-else-if="star === Math.ceil(rating) && hasHalfStar"
                    class="w-full h-full"
                    viewBox="0 0 20 20"
                >
                    <defs>
                        <linearGradient :id="`half-${componentId}`">
                            <stop offset="50%" stop-color="currentColor" class="text-yellow-400"/>
                            <stop offset="50%" stop-color="currentColor" class="text-gray-300"/>
                        </linearGradient>
                    </defs>
                    <path 
                        :fill="`url(#half-${componentId})`"
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                    />
                </svg>
                <svg
                    v-else
                    class="w-full h-full fill-current"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </span>
        </div>
        <span v-if="showValue" class="ml-1 text-gray-600" :class="sizeClasses[size]">
            {{ rating.toFixed(1) }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { generateId } from '@/utils/helpers'

const props = defineProps({
    rating: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 5
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    showValue: {
        type: Boolean,
        default: false
    }
})

const componentId = generateId()

const sizeClasses = {
    xs: 'w-3 h-3 text-xs',
    sm: 'w-4 h-4 text-sm',
    md: 'w-5 h-5 text-base',
    lg: 'w-6 h-6 text-lg',
    xl: 'w-8 h-8 text-xl'
}

const fullStars = computed(() => Math.floor(props.rating))
const hasHalfStar = computed(() => props.rating % 1 !== 0)
</script>

<style scoped>
.star {
    display: inline-block;
}
</style>
