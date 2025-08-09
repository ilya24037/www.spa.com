<template>
    <div class="service-provider-section">
        <h2 class="form-group-title">Кто оказывает услуги</h2>
        <CheckboxGroup 
            v-model="localProviders"
            :options="providerOptions"
            @update:modelValue="emitProviders"
        />
        <div v-if="errors.service_provider" class="error-message">
            {{ errors.service_provider }}
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

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
    { value: 'men', label: 'Мужчина' }
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

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-top: 8px;
}
</style> 