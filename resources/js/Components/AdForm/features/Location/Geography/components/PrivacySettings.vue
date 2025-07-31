<template>
  <FormField
    label="Настройки приватности"
    hint="Как показывать ваше местоположение в объявлении"
  >
    <!-- Используем готовые BaseRadio вместо кастомных -->
    <div class="space-y-3">
      <BaseRadio
        v-model="localValue"
        value="exact"
        label="Точный адрес"
        description="Показывать полный адрес"
        icon="📍"
      />
      
      <BaseRadio
        v-model="localValue"
        value="district"
        label="Только район"
        description="Показывать только район и метро"
        icon="🏘️"
      />
      
      <BaseRadio
        v-model="localValue"
        value="metro"
        label="Только метро"
        description="Показывать только станцию метро"
        icon="🚇"
      />
    </div>
    
    <!-- Информация о безопасности -->
    <Card variant="bordered" class="mt-4 bg-amber-50 border-amber-200">
      <div class="flex space-x-2">
        <span class="text-amber-600">🔒</span>
        <div class="text-sm text-amber-800">
          <p class="font-medium mb-1">Рекомендация по безопасности:</p>
          <p>Мы рекомендуем не указывать точный домашний адрес в публичных объявлениях. Используйте настройку "Только район" или "Только метро".</p>
        </div>
      </div>
    </Card>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: String, default: 'district' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || 'district'
})

watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
})
</script>