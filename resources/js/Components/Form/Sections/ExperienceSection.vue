<template>
    <div class="form-field">
        <div class="custom-select-wrapper" :class="{ 'active': isOpen }">
            <div 
                class="custom-select-trigger"
                @click="toggleDropdown"
                :class="{ 'has-value': form.experience }"
            >
                <span v-if="form.experience" class="selected-text">{{ getExperienceLabel(form.experience) }}</span>
                <span v-else class="placeholder-text">Выберите опыт работы</span>
                <div class="select-arrow">
                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
            
            <div v-if="isOpen" class="custom-select-dropdown">
                <div class="dropdown-option" 
                     v-for="option in experienceOptions" 
                     :key="option.value"
                     @click="selectOption(option.value)"
                     :class="{ 'selected': form.experience === option.value }">
                    {{ option.label }}
                </div>
            </div>
        </div>
        
        <div v-if="errors.experience" class="error-message">
            {{ errors.experience }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

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

const isOpen = ref(false)

// Варианты опыта как на скрине
const experienceOptions = [
    { value: 'less_than_year', label: 'Меньше года' },
    { value: '1_3_years', label: '1-3 года' },
    { value: '4_7_years', label: '4-7 лет' },
    { value: '8_10_years', label: '8-10 лет' },
    { value: 'more_than_10', label: 'Больше 10 лет' }
]

const toggleDropdown = () => {
    isOpen.value = !isOpen.value
}

const selectOption = (value) => {
    props.form.experience = value
    isOpen.value = false
}

const getExperienceLabel = (value) => {
    const option = experienceOptions.find(opt => opt.value === value)
    return option ? option.label : ''
}

// Закрытие при клике вне компонента
const handleClickOutside = (event) => {
    if (!event.target.closest('.custom-select-wrapper')) {
        isOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.custom-select-wrapper {
    position: relative;
    width: 100%;
}

.custom-select-trigger {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid #e5e5e5;
    border-radius: 8px;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 48px;
    transition: all 0.2s ease;
}

.custom-select-trigger:hover {
    border-color: #d0d0d0;
}

.custom-select-wrapper.active .custom-select-trigger {
    border-color: #2196f3;
    background: #fff;
    border-radius: 8px 8px 0 0;
}

.selected-text {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 400;
}

.placeholder-text {
    font-size: 16px;
    color: #8c8c8c;
}

.select-arrow {
    color: #8c8c8c;
    transition: transform 0.2s ease;
}

.custom-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid #2196f3;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}

.dropdown-option {
    padding: 16px;
    cursor: pointer;
    font-size: 16px;
    color: #1a1a1a;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-option:last-child {
    border-bottom: none;
}

.dropdown-option:hover {
    background-color: #f8f9fa;
}

.dropdown-option.selected {
    background-color: #e3f2fd;
    color: #2196f3;
    font-weight: 500;
}

.error-message {
    margin-top: 8px;
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 