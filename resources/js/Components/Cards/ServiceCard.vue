<template>
    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start">
            <div class="flex-grow">
                <h4 class="font-medium text-lg">{{ service.name }}</h4>
                <p class="text-gray-600 text-sm mt-1">{{ service.description }}</p>
                
                <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <ClockIcon class="w-4 h-4" />
                        <span>{{ service.duration }} мин</span>
                    </div>
                    <div v-if="service.category" class="flex items-center gap-1">
                        <TagIcon class="w-4 h-4" />
                        <span>{{ service.category.name }}</span>
                    </div>
                </div>
            </div>
            
            <div class="text-right ml-4">
                <p class="text-2xl font-bold text-gray-900">{{ service.price }}₽</p>
                <button 
                    @click="$emit('book', service)"
                    class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700"
                >
                    Записаться
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ClockIcon, TagIcon } from '@heroicons/vue/24/outline'

defineProps({
    service: Object
})

defineEmits(['book'])
</script>