<template>
    <div class="info-block text-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
        <!-- Иконка -->
        <div class="mb-2 flex justify-center">
            <div :class="[
                'w-12 h-12 rounded-lg flex items-center justify-center',
                colorClasses.bg
            ]">
                <!-- Опыт -->
                <svg v-if="icon === 'experience'" :class="['w-6 h-6', colorClasses.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                
                <!-- Услуги -->
                <svg v-else-if="icon === 'services'" :class="['w-6 h-6', colorClasses.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                
                <!-- Клиенты -->
                <svg v-else-if="icon === 'clients'" :class="['w-6 h-6', colorClasses.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                
                <!-- Сертификаты -->
                <svg v-else-if="icon === 'certificates'" :class="['w-6 h-6', colorClasses.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                
                <!-- По умолчанию -->
                <svg v-else :class="['w-6 h-6', colorClasses.icon]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        
        <!-- Значение -->
        <div :class="['text-2xl font-bold mb-1', colorClasses.text]">
            {{ formatValue(value) }}
        </div>
        
        <!-- Подпись -->
        <div class="text-sm text-gray-600">
            {{ label }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    icon: {
        type: String,
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    label: {
        type: String,
        required: true
    },
    color: {
        type: String,
        default: 'purple',
        validator: (value) => ['purple', 'blue', 'green', 'orange', 'red', 'gray'].includes(value)
    }
})

// Computed
const colorClasses = computed(() => {
    const colors = {
        purple: {
            bg: 'bg-purple-100',
            icon: 'text-purple-600',
            text: 'text-purple-700'
        },
        blue: {
            bg: 'bg-blue-100',
            icon: 'text-blue-600',
            text: 'text-blue-700'
        },
        green: {
            bg: 'bg-green-100',
            icon: 'text-green-600',
            text: 'text-green-700'
        },
        orange: {
            bg: 'bg-orange-100',
            icon: 'text-orange-600',
            text: 'text-orange-700'
        },
        red: {
            bg: 'bg-red-100',
            icon: 'text-red-600',
            text: 'text-red-700'
        },
        gray: {
            bg: 'bg-gray-200',
            icon: 'text-gray-600',
            text: 'text-gray-700'
        }
    }
    
    return colors[props.color] || colors.purple
})

// Methods
const formatValue = (value) => {
    if (typeof value === 'number') {
        if (value >= 1000) {
            return Math.floor(value / 1000) + 'k+'
        }
        return value.toString()
    }
    return value
}
</script>

<style scoped>
.info-block {
    transition: all 0.2s ease;
}

.info-block:hover {
    transform: translateY(-2px);
}
</style>