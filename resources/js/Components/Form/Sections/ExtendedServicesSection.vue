<template>
    <div class="extended-services-section">
        <h3 class="section-title">Дополнительные услуги</h3>
        <p class="section-description">
            Выберите дополнительные услуги, которые вы готовы предоставить. 
            Клиенты увидят их с указанием доплаты.
        </p>

        <!-- Вкладки по типам услуг -->
        <div class="service-tabs">
            <button 
                v-for="(typeName, typeKey) in serviceTypes"
                :key="typeKey"
                @click="activeTab = typeKey"
                :class="[
                    'tab-button',
                    { 'active': activeTab === typeKey }
                ]"
                type="button"
            >
                {{ typeName }}
                <span v-if="getSelectedCountByType(typeKey) > 0" class="count-badge">
                    {{ getSelectedCountByType(typeKey) }}
                </span>
            </button>
        </div>

        <!-- Содержимое активной вкладки -->
        <div class="tab-content">
            <div v-if="!categoriesByType[activeTab] || categoriesByType[activeTab].length === 0" 
                 class="empty-state">
                <p>Загрузка услуг...</p>
            </div>

            <div v-else class="services-grid">
                <div 
                    v-for="category in categoriesByType[activeTab]"
                    :key="category.id"
                    class="service-item"
                    :class="{ 
                        'selected': isSelected(category.id),
                        'adult-only': category.is_adult_only,
                        'has-cost': category.base_additional_cost > 0
                    }"
                >
                    <div class="service-checkbox" @click="toggleService(category.id)">
                        <input 
                            type="checkbox" 
                            :checked="isSelected(category.id)"
                            :id="`service-${category.id}`"
                            @change="toggleService(category.id)"
                        >
                        <label :for="`service-${category.id}`" class="service-label">
                            <div class="service-icon" v-if="category.icon">
                                <i :class="`icon-${category.icon}`"></i>
                            </div>
                            <div class="service-info">
                                <h4 class="service-name">{{ category.name }}</h4>
                                <p class="service-description">{{ category.description }}</p>
                                
                                <!-- Возрастные ограничения -->
                                <div v-if="category.is_adult_only" class="age-restriction">
                                    <span class="restriction-badge">{{ category.min_age }}+</span>
                                </div>
                                
                                <!-- Доплата -->
                                <div v-if="category.base_additional_cost > 0" class="additional-cost">
                                    <span class="cost-badge">+{{ formatCost(category.base_additional_cost) }}</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Персональная настройка цены -->
                    <div v-if="isSelected(category.id)" class="custom-settings">
                        <div class="custom-cost">
                            <label :for="`custom-cost-${category.id}`" class="custom-label">
                                Персональная доплата:
                            </label>
                            <input
                                :id="`custom-cost-${category.id}`"
                                type="number"
                                min="0"
                                step="100"
                                :placeholder="category.base_additional_cost"
                                v-model.number="getSelectedService(category.id).custom_cost"
                                class="custom-cost-input"
                            >
                            <span class="currency">₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Предупреждения -->
        <div v-if="hasAdultServices" class="warning-section">
            <div class="warning-box">
                <div class="warning-icon">⚠️</div>
                <div class="warning-text">
                    <strong>Внимание!</strong> Выбранные услуги предназначены только для совершеннолетних клиентов. 
                    Убедитесь, что ваше объявление размещается в соответствующей категории.
                </div>
            </div>
        </div>

        <!-- Итоговая информация -->
        <div v-if="selectedServices.length > 0" class="summary">
            <h4>Выбрано услуг: {{ selectedServices.length }}</h4>
            <div class="summary-list">
                <span 
                    v-for="service in selectedServicesSummary" 
                    :key="service.id"
                    class="summary-item"
                >
                    {{ service.name }}
                    <span v-if="service.cost > 0" class="summary-cost">+{{ formatCost(service.cost) }}</span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

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

// Типы услуг
const serviceTypes = {
    massage: 'Массаж',
    sex: 'Интимные услуги',
    bdsm: 'BDSM',
    additional: 'Дополнительно'
}

// Состояние компонента
const activeTab = ref('massage')
const categoriesByType = ref({})
const selectedServices = ref([])

// Вычисляемые свойства
const hasAdultServices = computed(() => {
    return selectedServices.value.some(service => {
        const category = findCategoryById(service.category_id)
        return category && category.is_adult_only
    })
})

const selectedServicesSummary = computed(() => {
    return selectedServices.value.map(service => {
        const category = findCategoryById(service.category_id)
        return {
            id: service.category_id,
            name: category ? category.name : 'Неизвестная услуга',
            cost: service.custom_cost || (category ? category.base_additional_cost : 0)
        }
    })
})

// Методы
const loadCategories = async () => {
    try {
        // Здесь должен быть API вызов для загрузки категорий
        // Пока используем моковые данные
        categoriesByType.value = {
            massage: [
                { id: 1, name: 'Расслабляющий', description: 'Расслабляющий массаж всего тела', is_adult_only: false, min_age: 16, base_additional_cost: 0, icon: 'spa' },
                { id: 2, name: 'Эротический', description: 'Эротический массаж с интимными элементами', is_adult_only: true, min_age: 18, base_additional_cost: 0, icon: 'heart' },
                { id: 3, name: 'Профессиональный', description: 'Профессиональный лечебный массаж', is_adult_only: false, min_age: 16, base_additional_cost: 0, icon: 'medical' }
            ],
            sex: [
                { id: 4, name: 'Секс классический', description: 'Традиционный вагинальный секс', is_adult_only: true, min_age: 18, base_additional_cost: 0, icon: 'heart' },
                { id: 5, name: 'Секс анальный', description: 'Анальный секс', is_adult_only: true, min_age: 18, base_additional_cost: 5000, icon: 'heart' },
                { id: 6, name: 'Минет без презерватива', description: 'Оральный секс без защиты', is_adult_only: true, min_age: 18, base_additional_cost: 3000, icon: 'warning' }
            ],
            bdsm: [
                { id: 7, name: 'Фетиш', description: 'Фетишистские практики', is_adult_only: true, min_age: 21, base_additional_cost: 0, icon: 'lightning' },
                { id: 8, name: 'Страпон', description: 'Использование страпона', is_adult_only: true, min_age: 21, base_additional_cost: 5000, icon: 'lightning' }
            ],
            additional: [
                { id: 9, name: 'Стриптиз', description: 'Эротический танец', is_adult_only: true, min_age: 18, base_additional_cost: 0, icon: 'music' }
            ]
        }
    } catch (error) {
        console.error('Ошибка загрузки категорий:', error)
    }
}

const toggleService = (categoryId) => {
    const index = selectedServices.value.findIndex(s => s.category_id === categoryId)
    
    if (index >= 0) {
        // Убираем услугу
        selectedServices.value.splice(index, 1)
    } else {
        // Добавляем услугу
        selectedServices.value.push({
            category_id: categoryId,
            custom_cost: null
        })
    }
    
    updateFormData()
}

const isSelected = (categoryId) => {
    return selectedServices.value.some(s => s.category_id === categoryId)
}

const getSelectedService = (categoryId) => {
    return selectedServices.value.find(s => s.category_id === categoryId) || {}
}

const getSelectedCountByType = (type) => {
    if (!categoriesByType.value[type]) return 0
    
    return categoriesByType.value[type].filter(category => 
        isSelected(category.id)
    ).length
}

const findCategoryById = (categoryId) => {
    for (const type in categoriesByType.value) {
        const category = categoriesByType.value[type].find(c => c.id === categoryId)
        if (category) return category
    }
    return null
}

const formatCost = (cost) => {
    return new Intl.NumberFormat('ru-RU').format(cost) + ' ₽'
}

const updateFormData = () => {
    // Обновляем данные формы
    props.form.extended_services = selectedServices.value
}

// Жизненный цикл
onMounted(() => {
    loadCategories()
    
    // Инициализируем из формы если есть данные
    if (props.form.extended_services) {
        selectedServices.value = [...props.form.extended_services]
    }
})
</script>

<style scoped>
.extended-services-section {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 24px;
    background: #fff;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.section-description {
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
}

.service-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 24px;
    gap: 4px;
}

.tab-button {
    padding: 12px 16px;
    border: none;
    background: none;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    position: relative;
}

.tab-button:hover {
    color: #1f2937;
}

.tab-button.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.count-badge {
    background: #3b82f6;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.services-grid {
    display: grid;
    gap: 16px;
}

.service-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s;
}

.service-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.service-item.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.service-item.adult-only {
    border-left: 4px solid #ef4444;
}

.service-checkbox {
    padding: 16px;
}

.service-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
}

.service-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.service-info {
    flex: 1;
}

.service-name {
    font-size: 16px;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
}

.service-description {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 8px;
}

.age-restriction {
    margin-bottom: 4px;
}

.restriction-badge {
    background: #ef4444;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.additional-cost {
    display: inline-block;
}

.cost-badge {
    background: #f59e0b;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.custom-settings {
    border-top: 1px solid #e5e7eb;
    padding: 16px;
    background: #f9fafb;
}

.custom-cost {
    display: flex;
    align-items: center;
    gap: 8px;
}

.custom-label {
    font-size: 14px;
    color: #374151;
    min-width: 140px;
}

.custom-cost-input {
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    width: 100px;
    font-size: 14px;
}

.currency {
    color: #6b7280;
    font-size: 14px;
}

.warning-section {
    margin-top: 24px;
}

.warning-box {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
}

.warning-icon {
    font-size: 20px;
}

.warning-text {
    color: #7f1d1d;
    font-size: 14px;
    line-height: 1.5;
}

.summary {
    margin-top: 24px;
    padding: 16px;
    background: #f3f4f6;
    border-radius: 8px;
}

.summary h4 {
    color: #1f2937;
    margin-bottom: 12px;
}

.summary-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.summary-item {
    background: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
    border: 1px solid #d1d5db;
}

.summary-cost {
    color: #f59e0b;
    font-weight: 500;
    margin-left: 4px;
}

.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #6b7280;
}
</style>