<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Расписание работы
        </h3>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    График работы
                </label>
                <div class="space-y-3">
                    <div 
                        v-for="day in weekDays" 
                        :key="day.id"
                        class="flex items-center space-x-4"
                    >
                        <div class="w-20">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    v-model="form.schedule[day.id].active"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700">{{ day.name }}</span>
                            </label>
                        </div>
                        
                        <div v-if="form.schedule[day.id].active" class="flex items-center space-x-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">С</label>
                                <input 
                                    type="time" 
                                    v-model="form.schedule[day.id].from"
                                    class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                            </div>
                            <span class="text-gray-500">—</span>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">До</label>
                                <input 
                                    type="time" 
                                    v-model="form.schedule[day.id].to"
                                    class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                            </div>
                            <div class="ml-4">
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.schedule[day.id].around_clock"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    >
                                    <span class="ml-2 text-sm text-gray-700">Круглосуточно</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="errors.schedule" class="mt-1 text-sm text-red-600">
                    {{ errors.schedule }}
                </p>
            </div>
        </div>
        
        <div class="form-row two-columns">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Минимальное время записи
                </label>
                <select 
                    v-model="form.min_booking_time" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите время</option>
                    <option value="30">За 30 минут</option>
                    <option value="60">За 1 час</option>
                    <option value="120">За 2 часа</option>
                    <option value="180">За 3 часа</option>
                    <option value="360">За 6 часов</option>
                    <option value="720">За 12 часов</option>
                    <option value="1440">За 1 день</option>
                </select>
                <p v-if="errors.min_booking_time" class="mt-1 text-sm text-red-600">
                    {{ errors.min_booking_time }}
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Максимальное время записи
                </label>
                <select 
                    v-model="form.max_booking_time" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите время</option>
                    <option value="1440">За 1 день</option>
                    <option value="2880">За 2 дня</option>
                    <option value="4320">За 3 дня</option>
                    <option value="10080">За 1 неделю</option>
                    <option value="20160">За 2 недели</option>
                    <option value="43200">За 1 месяц</option>
                </select>
                <p v-if="errors.max_booking_time" class="mt-1 text-sm text-red-600">
                    {{ errors.max_booking_time }}
                </p>
            </div>
        </div>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Дополнительная информация
                </label>
                <textarea 
                    v-model="form.schedule_notes"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Например: Возможны изменения в расписании, предварительная запись обязательна"
                ></textarea>
                <p v-if="errors.schedule_notes" class="mt-1 text-sm text-red-600">
                    {{ errors.schedule_notes }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'

// Props
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// Дни недели
const weekDays = [
    { id: 'monday', name: 'Пн' },
    { id: 'tuesday', name: 'Вт' },
    { id: 'wednesday', name: 'Ср' },
    { id: 'thursday', name: 'Чт' },
    { id: 'friday', name: 'Пт' },
    { id: 'saturday', name: 'Сб' },
    { id: 'sunday', name: 'Вс' }
]

// Инициализация расписания если не существует
if (!props.form.schedule) {
    props.form.schedule = {}
    weekDays.forEach(day => {
        props.form.schedule[day.id] = {
            active: false,
            from: '09:00',
            to: '18:00',
            around_clock: false
        }
    })
}

// Отслеживание изменений круглосуточного режима
weekDays.forEach(day => {
    watch(
        () => props.form.schedule[day.id].around_clock,
        (newValue) => {
            if (newValue) {
                props.form.schedule[day.id].from = '00:00'
                props.form.schedule[day.id].to = '23:59'
            }
        }
    )
})
</script> 