// resources/js/Components/Filters/FilterPanel.vue
<template>
    <aside class="w-80 bg-white border-r border-gray-200 overflow-y-auto h-full">
        <div class="p-6 space-y-6">
            <!-- Заголовок и кнопка сброса -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                <button 
                    @click="resetAllFilters"
                    class="text-sm text-blue-600 hover:text-blue-700"
                >
                    Сбросить все
                </button>
            </div>

            <!-- Переключатель "Работает сейчас" -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Доступность</h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="filters.availableNow" 
                        @change="updateFilters"
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Работает сейчас</span>
                </label>
            </div>

            <!-- Кто оказывает услуги -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Кто оказывает услуги</h3>
                <div class="space-y-2">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="filters.providerType.individual"
                            @change="updateFilters"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Частный исполнитель</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="filters.providerType.company"
                            @change="updateFilters"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Компания</span>
                    </label>
                </div>
            </div>

            <!-- Цена за час -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Цена за час</h3>
                <div class="space-y-4">
                    <!-- Поля ввода -->
                    <div class="flex items-center space-x-2">
                        <input 
                            type="number"
                            v-model.number="filters.price.min"
                            @input="updatePriceRange"
                            placeholder="0"
                            min="0"
                            :max="filters.price.max"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <span class="text-gray-500">—</span>
                        <input 
                            type="number"
                            v-model.number="filters.price.max"
                            @input="updatePriceRange"
                            placeholder="30000"
                            :min="filters.price.min"
                            max="30000"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <!-- Двойной ползунок -->
                    <div class="relative pt-1">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>{{ formatPrice(filters.price.min) }}</span>
                            <span>{{ formatPrice(filters.price.max) }}</span>
                        </div>
                        <div class="relative h-2 mt-2">
                            <!-- Фон ползунка -->
                            <div class="absolute w-full h-2 bg-gray-200 rounded-full"></div>
                            <!-- Активная область -->
                            <div 
                                class="absolute h-2 bg-blue-600 rounded-full"
                                :style="{
                                    left: `${(filters.price.min / 30000) * 100}%`,
                                    right: `${100 - (filters.price.max / 30000) * 100}%`
                                }"
                            ></div>
                            <!-- Ползунок минимума -->
                            <input 
                                type="range"
                                v-model.number="filters.price.min"
                                @input="updatePriceRange"
                                min="0"
                                :max="filters.price.max"
                                step="100"
                                class="absolute w-full -top-1 h-4 bg-transparent appearance-none cursor-pointer"
                                style="pointer-events: none;"
                            >
                            <!-- Ползунок максимума -->
                            <input 
                                type="range"
                                v-model.number="filters.price.max"
                                @input="updatePriceRange"
                                :min="filters.price.min"
                                max="30000"
                                step="100"
                                class="absolute w-full -top-1 h-4 bg-transparent appearance-none cursor-pointer"
                                style="pointer-events: none;"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Возраст -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Возраст мастера</h3>
                <div class="flex items-center space-x-2">
                    <input 
                        type="number"
                        v-model.number="filters.age.min"
                        @input="updateFilters"
                        placeholder="18"
                        min="18"
                        max="99"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <span class="text-gray-500">—</span>
                    <input 
                        type="number"
                        v-model.number="filters.age.max"
                        @input="updateFilters"
                        placeholder="99"
                        min="18"
                        max="99"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
            </div>

            <!-- Рост -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Рост мастера (см)</h3>
                <div class="flex items-center space-x-2">
                    <input 
                        type="number"
                        v-model.number="filters.height.min"
                        @input="updateFilters"
                        placeholder="150"
                        min="150"
                        max="210"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                    <span class="text-gray-500">—</span>
                    <input 
                        type="number"
                        v-model.number="filters.height.max"
                        @input="updateFilters"
                        placeholder="210"
                        min="150"
                        max="210"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
            </div>

            <!-- Дополнительные параметры -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Дополнительно</h3>
                <div class="space-y-2">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="filters.extras.promoOnly"
                            @change="updateFilters"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Конкурсы</span>
                        <span 
                            class="ml-1 inline-flex items-center justify-center w-4 h-4 text-xs text-gray-500 bg-gray-200 rounded-full"
                            title="Участвует в конкурсах"
                        >
                            ?
                        </span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="filters.extras.discounted"
                            @change="updateFilters"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Дискотека</span>
                        <span 
                            class="ml-1 inline-flex items-center justify-center w-4 h-4 text-xs text-gray-500 bg-gray-200 rounded-full"
                            title="Скидки и акции"
                        >
                            ?
                        </span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="filters.extras.showBandits"
                            @change="updateFilters"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Шоу пузырей</span>
                        <span 
                            class="ml-1 inline-flex items-center justify-center w-4 h-4 text-xs text-gray-500 bg-gray-200 rounded-full"
                            title="Мыльные пузыри - это шоу мыльных пузырей"
                        >
                            ?
                        </span>
                    </label>
                </div>
            </div>

            <!-- Цвет волос -->
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">Цвет волос</h3>
                <div class="grid grid-cols-4 gap-2">
                    <button 
                        v-for="color in hairColors"
                        :key="color.value"
                        @click="toggleHairColor(color.value)"
                        :class="[
                            'w-12 h-12 rounded-lg border-2 transition-all',
                            filters.hairColors.includes(color.value) 
                                ? 'border-blue-500 ring-2 ring-blue-200' 
                                : 'border-gray-300 hover:border-gray-400'
                        ]"
                        :style="{ backgroundColor: color.hex }"
                        :title="color.name"
                    >
                        <span v-if="filters.hairColors.includes(color.value)" class="text-white">
                            <svg class="w-6 h-6 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="space-y-2 pt-4 border-t border-gray-200">
                <button 
                    @click="applyFilters"
                    class="w-full py-2.5 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                >
                    Показать результаты
                </button>
                <button 
                    @click="resetAllFilters"
                    class="w-full py-2.5 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                >
                    Сбросить фильтры
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Определение пропсов
const props = defineProps({
    initialFilters: {
        type: Object,
        default: () => ({})
    }
})

// Эмиты
const emit = defineEmits(['filters-updated'])

// Состояние фильтров
const filters = reactive({
    availableNow: false,
    providerType: {
        individual: true,
        company: false
    },
    price: {
        min: 0,
        max: 30000
    },
    age: {
        min: 18,
        max: 99
    },
    height: {
        min: 150,
        max: 210
    },
    extras: {
        promoOnly: false,
        discounted: false,
        showBandits: false
    },
    hairColors: []
})

// Цвета волос
const hairColors = [
    { value: 'black', name: 'Чёрный', hex: '#1a1a1a' },
    { value: 'brown', name: 'Коричневый', hex: '#8B4513' },
    { value: 'blonde', name: 'Блондин', hex: '#F4E4C1' },
    { value: 'red', name: 'Рыжий', hex: '#B55239' }
]

// Методы
const formatPrice = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value)
}

const updatePriceRange = () => {
    // Проверка, чтобы минимум не превышал максимум
    if (filters.price.min > filters.price.max) {
        filters.price.min = filters.price.max
    }
    updateFilters()
}

const toggleHairColor = (color) => {
    const index = filters.hairColors.indexOf(color)
    if (index > -1) {
        filters.hairColors.splice(index, 1)
    } else {
        filters.hairColors.push(color)
    }
    updateFilters()
}

const updateFilters = () => {
    // Отправляем событие об изменении фильтров
    emit('filters-updated', { ...filters })
}

const applyFilters = () => {
    // Применяем фильтры и обновляем URL
    router.get(route('home'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetAllFilters = () => {
    // Сброс всех фильтров к значениям по умолчанию
    filters.availableNow = false
    filters.providerType.individual = true
    filters.providerType.company = false
    filters.price.min = 0
    filters.price.max = 30000
    filters.age.min = 18
    filters.age.max = 99
    filters.height.min = 150
    filters.height.max = 210
    filters.extras.promoOnly = false
    filters.extras.discounted = false
    filters.extras.showBandits = false
    filters.hairColors = []
    
    applyFilters()
}

// Инициализация фильтров из пропсов
if (props.initialFilters) {
    Object.assign(filters, props.initialFilters)
}
</script>