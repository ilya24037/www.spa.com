<template>
    <div class="service-provider-section">
        <h2 class="form-group-title">Кто оказывает услуги</h2>
        <div class="checkbox-group">
            <label v-for="option in providerOptions" :key="option.value" class="checkbox-label">
                <input 
                    type="checkbox" 
                    :value="option.value" 
                    v-model="localProviders" 
                    @change="emitProviders"
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
    serviceProvider: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceProvider'])

const localProviders = ref([...props.serviceProvider])

watch(() => props.serviceProvider, (val) => {
    localProviders.value = [...val]
})

// Опции для CheckboxGroup
const providerOptions = computed(() => [
    { value: 'women', label: 'Женщина' },
    { value: 'men', label: 'Мужчина' },
    { value: 'couple', label: 'Пара' }
])

const emitProviders = () => {
    emit('update:serviceProvider', [...localProviders.value])
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

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 16px;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-top: 8px;
}
</style> 