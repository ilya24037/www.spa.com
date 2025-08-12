<template>
  <div class="geo-section">
    <h2 class="form-group-title">География</h2>
    <BaseInput
      v-model="localGeo"
      type="text"
      placeholder="Введите город, район или метро..."
      @update:modelValue="emitGeo"
      :error="errors?.geo?.[0]"
    />
    
    <!-- Отладочная информация (только в dev режиме) -->
    <div v-if="isDev && debugInfo" class="debug-info">
      <p><strong>Тип geo:</strong> {{ typeof geo }}</p>
      <p><strong>Значение geo:</strong> {{ JSON.stringify(geo) }}</p>
      <p><strong>localGeo:</strong> {{ localGeo }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'

// Типы
interface Props {
  geo?: string | Record<string, any>  // ✅ Принимаем и строку, и объект
  errors?: Record<string, string[]>
}

interface Emits {
  'update:geo': [value: string]  // ✅ Эмитим строку
}

// Props
const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({})
})

// Emits
const emit = defineEmits<Emits>()

// Состояние
const localGeo = ref('')

// Вычисляемые свойства
const isDev = computed(() => import.meta.env.DEV)
const debugInfo = computed(() => isDev.value)

// Нормализуем geo в строку
const normalizeGeo = (value: string | Record<string, any>): string => {
  if (typeof value === 'string') {
    return value
  }
  
  if (value && typeof value === 'object') {
    // Если это объект с адресом, извлекаем его
    if ('address' in value && typeof value.address === 'string') {
      return value.address
    }
    
    // Если это объект с координатами, показываем их
    if ('lat' in value && 'lng' in value) {
      return `${value.lat}, ${value.lng}`
    }
    
    // Если это пустой объект или другой объект, возвращаем пустую строку
    return ''
  }
  
  return ''
}

// Инициализация
localGeo.value = normalizeGeo(props.geo)

// Следим за изменениями props.geo
watch(() => props.geo, (newValue) => {
  localGeo.value = normalizeGeo(newValue)
}, { immediate: true })

// Методы
const emitGeo = () => {
  emit('update:geo', localGeo.value)
}
</script>

<style scoped>
.geo-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}

.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}

.geo-input { 
  width: 100%; 
  padding: 12px; 
  border: 1px solid #ddd; 
  border-radius: 6px; 
  font-size: 15px; 
  font-family: inherit; 
}

.debug-info {
  margin-top: 16px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 12px;
  font-family: monospace;
  border: 1px solid #e9ecef;
}

.debug-info p {
  margin: 4px 0;
  color: #6c757d;
}
</style>
