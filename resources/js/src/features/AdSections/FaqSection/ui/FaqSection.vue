<template>
  <div class="faq-section">
    <div class="faq-body">
      <div v-for="question in faqQuestions" :key="question.id" class="faq-item">
        <div class="faq-question">{{ question.question }}</div>
        
        <!-- Radio группа -->
        <div v-if="question.type === 'radio'" class="faq-options radio-group">
          <BaseRadio
            v-for="option in question.options"
            :key="`${question.id}_${option.value}`"
            v-model="localFaq[question.id]"
            :value="option.value"
            :label="option.label"
            :name="question.id"
            @update:modelValue="handleRadioChange(question.id, $event)"
          />
        </div>
        
        <!-- Checkbox группа -->
        <div v-else-if="question.type === 'checkbox'" class="faq-options checkbox-group">
          <div class="checkbox-options">
            <BaseCheckbox
              v-for="option in question.options"
              :key="`${question.id}_${option.value}`"
              :model-value="isOptionSelected(question.id, option.value)"
              :label="option.label"
              :class="{
                'exclusive-no-option': isExclusiveNoOption(question.id, option.value) && isOptionSelected(question.id, option.value)
              }"
              @update:modelValue="handleCheckboxChange(question.id, option.value, $event)"
            />
          </div>
          
          <!-- Кнопки "Выбрать все / Отменить все" -->
          <div v-if="question.allowMultiple" class="checkbox-controls">
            <a href="#" @click.prevent="selectAll(question.id, question.options)" class="select-all">
              Выбрать все
            </a>
            <span class="separator">/</span>
            <a href="#" @click.prevent="deselectAll(question.id)" class="deselect-all">
              Отменить все
            </a>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Сообщения об ошибках -->
    <div v-if="errors.faq" class="error-message">{{ errors.faq }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import { faqQuestions, type FaqQuestion, type FaqOption } from '../model/faqData'

interface Props {
  faq?: Record<string, any>
  errors?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  faq: () => ({}),
  errors: () => ({})
})

const emit = defineEmits<{
  'update:faq': [value: Record<string, any>]
}>()

// Локальное состояние FAQ
const localFaq = ref<Record<string, any>>({})

// Вопросы с взаимоисключающей опцией "Нет" (questionId -> value опции "Нет")
const exclusiveNoQuestions: Record<string, number> = {
  'faq_2': 4, // "Есть ласки и тактильный контакт?" - опция "Нет"
  'faq_3': 4  // "Возможны встречи в формате GFE?" - опция "Нет"
}

// Проверка, является ли опция взаимоисключающим "Нет"
const isExclusiveNoOption = (questionId: string, optionValue: any): boolean => {
  return exclusiveNoQuestions[questionId] === optionValue
}

// Инициализация данных
onMounted(() => {
  // Инициализируем локальное состояние из props или пустыми значениями
  faqQuestions.forEach(question => {
    if (props.faq && props.faq[question.id] !== undefined) {
      localFaq.value[question.id] = props.faq[question.id]
    } else {
      // Инициализация по умолчанию
      if (question.type === 'checkbox') {
        localFaq.value[question.id] = []
      } else {
        localFaq.value[question.id] = null
      }
    }
  })
})

// Следим за изменениями props.faq
watch(() => props.faq, (newFaq) => {
  if (newFaq) {
    Object.keys(newFaq).forEach(key => {
      localFaq.value[key] = newFaq[key]
    })
  }
}, { deep: true })

// Обработка изменения radio
const handleRadioChange = (questionId: string, value: any) => {
  localFaq.value[questionId] = value
  emit('update:faq', { ...localFaq.value })
}

// Обработка изменения checkbox
const handleCheckboxChange = (questionId: string, optionValue: any, checked: boolean) => {
  if (!localFaq.value[questionId]) {
    localFaq.value[questionId] = []
  }
  
  if (!Array.isArray(localFaq.value[questionId])) {
    localFaq.value[questionId] = []
  }
  
  let currentValues = [...localFaq.value[questionId]]
  
  if (checked) {
    // Если выбирается взаимоисключающий "Нет"
    if (isExclusiveNoOption(questionId, optionValue)) {
      // Очищаем все другие опции, оставляем только "Нет"
      currentValues = [optionValue]
    } else {
      // Если выбирается любая другая опция
      // Убираем "Нет" из выбранных, если он там есть
      const noValue = exclusiveNoQuestions[questionId]
      if (noValue !== undefined) {
        const noIndex = currentValues.indexOf(noValue)
        if (noIndex > -1) {
          currentValues.splice(noIndex, 1)
        }
      }
      // Добавляем новую опцию
      if (!currentValues.includes(optionValue)) {
        currentValues.push(optionValue)
      }
    }
  } else {
    // Снимаем галочку
    const index = currentValues.indexOf(optionValue)
    if (index > -1) {
      currentValues.splice(index, 1)
    }
  }
  
  localFaq.value[questionId] = currentValues
  emit('update:faq', { ...localFaq.value })
}

// Проверка, выбрана ли опция в checkbox группе
const isOptionSelected = (questionId: string, optionValue: any): boolean => {
  const values = localFaq.value[questionId]
  if (!values || !Array.isArray(values)) return false
  return values.includes(optionValue)
}

// Выбрать все опции в checkbox группе
const selectAll = (questionId: string, options: FaqOption[]) => {
  // Если в вопросе есть взаимоисключающий "Нет", не включаем его в "выбрать все"
  const noValue = exclusiveNoQuestions[questionId]
  if (noValue !== undefined) {
    // Выбираем все опции, кроме "Нет"
    localFaq.value[questionId] = options
      .filter(opt => opt.value !== noValue)
      .map(opt => opt.value)
  } else {
    // Обычное поведение для вопросов без взаимоисключающего "Нет"
    localFaq.value[questionId] = options.map(opt => opt.value)
  }
  emit('update:faq', { ...localFaq.value })
}

// Отменить все опции в checkbox группе
const deselectAll = (questionId: string) => {
  localFaq.value[questionId] = []
  emit('update:faq', { ...localFaq.value })
}
</script>

<style scoped>
.faq-section {
  /* Убраны отступы, так как теперь компонент внутри обертки */
  padding: 0;
}

.faq-body {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.faq-item {
  padding: 16px 0;
  border-bottom: 1px solid #f5f5f5;
}

.faq-item:last-child {
  border-bottom: none;
}

.faq-question {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  margin-bottom: 12px;
  line-height: 1.4;
}

.faq-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.radio-group :deep(.base-radio) {
  flex: 0 0 auto;
  min-width: 250px;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.checkbox-controls {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.select-all,
.deselect-all {
  color: #007bff;
  text-decoration: none;
  transition: color 0.2s;
}

.select-all:hover,
.deselect-all:hover {
  color: #0056b3;
  text-decoration: underline;
}

.separator {
  color: #999;
  font-size: 13px;
}

.error-message {
  color: #dc3545;
  font-size: 14px;
  margin-top: 8px;
}

/* Адаптивность */
@media (min-width: 768px) {
  .radio-group {
    flex-direction: row;
  }
  
  .radio-group :deep(.base-radio) {
    flex: 0 0 calc(50% - 6px);
  }
}

@media (min-width: 992px) {
  .radio-group :deep(.base-radio) {
    flex: 0 0 calc(33.333% - 8px);
  }
}

@media (max-width: 767px) {
  .faq-section {
    padding: 0;
  }
  
  .faq-question {
    font-size: 14px;
  }
  
  .radio-group :deep(.base-radio) {
    width: 100%;
    min-width: unset;
  }
}
</style>