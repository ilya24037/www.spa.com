<template>
  <FormField
    label="Подарок или бонус"
    hint="Дополнительная услуга или подарок для клиентов"
    :error="error"
  >
    <div class="space-y-4">
      <!-- Поле ввода подарка -->
      <BaseTextarea
        v-model="localValue"
        :rows="3"
        placeholder="Например: бесплатный чай, дополнительные 15 минут массажа, ароматерапия..."
        :maxlength="200"
      />
      
      <!-- Готовые варианты -->
      <Card variant="bordered" class="bg-blue-50 border-blue-200 p-4">
        <div class="space-y-3">
          <p class="text-sm font-medium text-blue-800">Популярные подарки:</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <button
              v-for="gift in giftSuggestions"
              :key="gift"
              type="button"
              @click="addGiftSuggestion(gift)"
              class="p-2 text-sm text-left bg-white border border-blue-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors"
            >
              {{ gift }}
            </button>
          </div>
          
          <p class="text-xs text-blue-600">
            💡 Нажмите на подарок, чтобы добавить его к описанию
          </p>
        </div>
      </Card>
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue || '')

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

// Watch для отправки изменений родителю
watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
})

// Варианты подарков
const giftSuggestions = [
  'Бесплатный чай или кофе',
  'Дополнительные 15 минут',
  'Ароматерапия',
  'Расслабляющая музыка',
  'Консультация по здоровью',
  'Скидка на следующий визит',
  'Горячие полотенца',
  'Фруктовая вода'
]

// Методы
const addGiftSuggestion = (suggestion) => {
  let newValue = localValue.value.trim()
  
  if (newValue) {
    // Проверяем, не добавлен ли уже этот подарок
    if (!newValue.includes(suggestion)) {
      newValue += ', ' + suggestion
    }
  } else {
    newValue = suggestion
  }
  
  // Обновляем localValue (watcher автоматически отправит изменения)
  localValue.value = newValue
}
</script>