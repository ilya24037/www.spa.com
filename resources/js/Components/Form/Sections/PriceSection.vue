<template>
    <div class="form-field">
        <div class="price-controls">
            <div class="price-input-wrapper">
                <BaseInput
                    v-model="form.price"
                    type="number"
                    placeholder="Введите стоимость"
                    clearable
                    prefix="от"
                    suffix="₽"
                    :error="errors.price"
                    @input="handlePriceInput"
                />
            </div>
            
            <div class="unit-select">
                <BaseSelect
                    v-model="form.price_unit"
                    :options="unitOptions"
                    placeholder="за час"
                />
            </div>
        </div>
        
        <div class="mt-4">
            <BaseCheckbox
                v-model="form.is_starting_price"
                label="это начальная цена"
            />
        </div>
    </div>
</template>

<script setup>
import BaseInput from '../../UI/BaseInput.vue'
import BaseSelect from '../../UI/BaseSelect.vue'  
import BaseCheckbox from '../../UI/BaseCheckbox.vue'

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

// Инициализация значений по умолчанию
if (!props.form.price_unit) {
    props.form.price_unit = 'hour'
}
if (props.form.is_starting_price === undefined || props.form.is_starting_price === null) {
    props.form.is_starting_price = false
}

const unitOptions = [
    { value: 'hour', label: 'за час' },
    { value: 'session', label: 'за сеанс' },
    { value: 'service', label: 'за услугу' },
    { value: 'day', label: 'за день' },
    { value: 'visit', label: 'за выезд' }
]

const handlePriceInput = (value) => {
    // Дополнительная обработка если нужна
    props.form.price = value
}
</script>

<style scoped>
.price-controls {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.price-input-wrapper {
    flex: 1;
}

.unit-select {
    min-width: 120px;
}

.mt-4 {
    margin-top: 16px;
}
</style> 