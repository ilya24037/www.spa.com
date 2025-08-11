<template>
    <div class="service-provider-section">
        <h2 class="form-group-title">Кто оказывает услуги</h2>
        <div class="radio-group">
            <label v-for="option in providerOptions" :key="option.value" class="radio-label">
                <input 
                    type="radio" 
                    :value="option.value" 
                    v-model="localProvider" 
                    @change="emitProvider"
                />
                {{ option.label }}
            </label>
        </div>
        <div v-if="errors.service_provider" class="error-message">
            {{ errors.service_provider }}
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
    serviceProvider: { type: String, default: 'woman' },
    errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceProvider'])

const localProvider = ref(props.serviceProvider)

watch(() => props.serviceProvider, (val) => {
    localProvider.value = val
})

// Опции для RadioGroup
const providerOptions = computed(() => [
    { value: 'woman', label: 'Женщина' },
    { value: 'man', label: 'Мужчина' },
    { value: 'couple', label: 'Пара' }
])

const emitProvider = () => {
    emit('update:serviceProvider', localProvider.value)
}
</script>

<style scoped>
.service-provider-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 16px;
}

.radio-label input[type="radio"] {
  width: 18px;
  height: 18px;
}

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-top: 8px;
}
</style> 