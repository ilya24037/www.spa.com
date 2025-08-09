<template>
  <div class="location-section">
    <h2 class="form-group-title">Где вы оказываете услуги</h2>
    <CheckboxGroup 
      v-model="localLocation"
      :options="locationOptions"
      @update:modelValue="emitLocation"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  serviceLocation: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceLocation'])

const localLocation = ref([...props.serviceLocation])

watch(() => props.serviceLocation, (val) => {
  localLocation.value = [...val]
})

// Опции для CheckboxGroup
const locationOptions = computed(() => [
  { value: 'У заказчика дома', label: 'У заказчика дома' },
  { value: 'У себя дома', label: 'У себя дома' },
  { value: 'В офисе', label: 'В офисе' }
])

const emitLocation = () => {
  emit('update:serviceLocation', [...localLocation.value])
}
</script>

<style scoped>
.location-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}
</style> 