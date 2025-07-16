<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Параметры массажа
        </h3>
        
        <!-- Типы массажа -->
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Типы массажа *
                </label>
                <div class="space-y-2">
                    <label 
                        v-for="type in massageTypes" 
                        :key="type.id"
                        class="flex items-center"
                    >
                        <input 
                            type="checkbox" 
                            :value="type.id"
                            v-model="form.massage_types"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">{{ type.name }}</span>
                    </label>
                </div>
                <p v-if="errors.massage_types" class="mt-1 text-sm text-red-600">
                    {{ errors.massage_types }}
                </p>
            </div>
        </div>
        
        <!-- Продолжительность сеанса -->
        <div class="form-row two-columns">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Продолжительность сеанса *
                </label>
                <select 
                    v-model="form.session_duration" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите продолжительность</option>
                    <option value="30">30 минут</option>
                    <option value="45">45 минут</option>
                    <option value="60">1 час</option>
                    <option value="90">1.5 часа</option>
                    <option value="120">2 часа</option>
                    <option value="180">3 часа</option>
                    <option value="custom">Другое</option>
                </select>
                <p v-if="errors.session_duration" class="mt-1 text-sm text-red-600">
                    {{ errors.session_duration }}
                </p>
            </div>
            
            <!-- Кастомная продолжительность -->
            <div v-if="form.session_duration === 'custom'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Укажите продолжительность (мин)
                </label>
                <input 
                    type="number" 
                    v-model="form.custom_duration"
                    min="15"
                    max="480"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Например, 75"
                >
                <p v-if="errors.custom_duration" class="mt-1 text-sm text-red-600">
                    {{ errors.custom_duration }}
                </p>
            </div>
        </div>
        
        <!-- Дополнительные услуги -->
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Дополнительные услуги
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label 
                        v-for="service in additionalServices" 
                        :key="service.id"
                        class="flex items-center"
                    >
                        <input 
                            type="checkbox" 
                            :value="service.id"
                            v-model="form.additional_services"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">{{ service.name }}</span>
                    </label>
                </div>
                <p v-if="errors.additional_services" class="mt-1 text-sm text-red-600">
                    {{ errors.additional_services }}
                </p>
            </div>
        </div>
        
        <!-- Специализация -->
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Специализация
                </label>
                <div class="space-y-2">
                    <label 
                        v-for="spec in specializations" 
                        :key="spec.id"
                        class="flex items-center"
                    >
                        <input 
                            type="checkbox" 
                            :value="spec.id"
                            v-model="form.specializations"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">{{ spec.name }}</span>
                    </label>
                </div>
                <p v-if="errors.specializations" class="mt-1 text-sm text-red-600">
                    {{ errors.specializations }}
                </p>
            </div>
        </div>
        
        <!-- Опыт работы -->
        <div class="form-row two-columns">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Опыт работы
                </label>
                <select 
                    v-model="form.experience" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите опыт</option>
                    <option value="less_1">Менее 1 года</option>
                    <option value="1_3">1-3 года</option>
                    <option value="3_5">3-5 лет</option>
                    <option value="5_10">5-10 лет</option>
                    <option value="more_10">Более 10 лет</option>
                </select>
                <p v-if="errors.experience" class="mt-1 text-sm text-red-600">
                    {{ errors.experience }}
                </p>
            </div>
            
            <!-- Образование -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Образование
                </label>
                <select 
                    v-model="form.education" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите образование</option>
                    <option value="medical">Медицинское</option>
                    <option value="massage_courses">Курсы массажа</option>
                    <option value="self_taught">Самоучка</option>
                    <option value="other">Другое</option>
                </select>
                <p v-if="errors.education" class="mt-1 text-sm text-red-600">
                    {{ errors.education }}
                </p>
            </div>
        </div>
        
        <!-- Условия работы -->
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Условия работы
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.home_visits"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Выезд на дом</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.salon_work"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Работа в салоне</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.hotel_visits"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Выезд в отель</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// Типы массажа
const massageTypes = [
    { id: 'classic', name: 'Классический массаж' },
    { id: 'therapeutic', name: 'Лечебный массаж' },
    { id: 'relaxing', name: 'Расслабляющий массаж' },
    { id: 'sports', name: 'Спортивный массаж' },
    { id: 'anti_cellulite', name: 'Антицеллюлитный массаж' },
    { id: 'lymphatic', name: 'Лимфодренажный массаж' },
    { id: 'honey', name: 'Медовый массаж' },
    { id: 'stone', name: 'Стоун-массаж' },
    { id: 'thai', name: 'Тайский массаж' },
    { id: 'vacuum', name: 'Вакуумный массаж' }
]

// Дополнительные услуги
const additionalServices = [
    { id: 'aromatherapy', name: 'Ароматерапия' },
    { id: 'music_therapy', name: 'Музыкотерапия' },
    { id: 'candles', name: 'Массаж при свечах' },
    { id: 'oils', name: 'Эфирные масла' },
    { id: 'wrapping', name: 'Обертывания' },
    { id: 'scrub', name: 'Скрабирование' },
    { id: 'consultation', name: 'Консультация' },
    { id: 'recommendations', name: 'Рекомендации по уходу' }
]

// Специализации
const specializations = [
    { id: 'back_problems', name: 'Проблемы со спиной' },
    { id: 'neck_shoulders', name: 'Шея и плечи' },
    { id: 'stress_relief', name: 'Снятие стресса' },
    { id: 'muscle_tension', name: 'Мышечное напряжение' },
    { id: 'circulation', name: 'Улучшение кровообращения' },
    { id: 'posture', name: 'Коррекция осанки' },
    { id: 'rehabilitation', name: 'Реабилитация' },
    { id: 'pregnancy', name: 'Массаж для беременных' }
]
</script> 