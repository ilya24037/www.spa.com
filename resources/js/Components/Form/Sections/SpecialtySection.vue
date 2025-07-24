<template>
    <div class="form-field">
        <BaseSelect
            v-model="form.specialty"
            :options="specialtyOptions"
            label="Специальность или сфера"
            placeholder="Выберите специальность"
            :error="errors.specialty"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import BaseSelect from '../../UI/BaseSelect.vue'

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

// Варианты специальностей как на скрине
const specialtyOptions = [
    { value: 'massage', label: 'Массаж' },
    { value: 'therapeutic', label: 'Лечебный массаж' },
    { value: 'relaxing', label: 'Расслабляющий массаж' },
    { value: 'sports', label: 'Спортивный массаж' },
    { value: 'anti-cellulite', label: 'Антицеллюлитный массаж' },
    { value: 'lymphatic', label: 'Лимфодренажный массаж' },
    { value: 'honey', label: 'Медовый массаж' },
    { value: 'stone', label: 'Стоун-массаж' },
    { value: 'thai', label: 'Тайский массаж' },
    { value: 'erotic', label: 'Эротический массаж' }
]
</script>

<style scoped>
.form-field {
    margin-bottom: 24px;
}
</style> 