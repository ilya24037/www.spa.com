<template>
  <FormSection
    title="Формат работы"
    hint="Выберите как вы предпочитаете работать"
    required
    :errors="errors"
    :error-keys="['work_format']"
  >
    <div class="work-format-options">
      <FormField
        label="Формат работы"
        required
        :error="errors.work_format"
      >
        <div class="radio-group">
          <div class="radio-item" @click="selectFormat('individual')">
            <div class="custom-radio" :class="{ checked: localWorkFormat === 'individual' }"></div>
            <div class="radio-content">
              <div class="radio-title">Индивидуально</div>
              <div class="radio-description">Работаю только одна</div>
            </div>
          </div>
          
          <div class="radio-item" @click="selectFormat('salon')">
            <div class="custom-radio" :class="{ checked: localWorkFormat === 'salon' }"></div>
            <div class="radio-content">
              <div class="radio-title">Салон</div>
              <div class="radio-description">Работаю в команде</div>
            </div>
          </div>
        </div>
      </FormField>

      <!-- Подруга (только для индивидуального формата) -->
      <FormField
        v-if="localWorkFormat === 'individual'"
        label="Есть подруга?"
        hint="Можете ли предложить услуги с подругой"
      >
        <div class="checkbox-item" @click="toggleGirlfriend">
          <input
            type="checkbox"
            :checked="localHasGirlfriend"
            @click.stop
            @change="toggleGirlfriend"
          />
          <span>Есть подруга</span>
        </div>
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  workFormat: {
    type: String,
    default: ''
  },
  hasGirlfriend: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:workFormat', 'update:hasGirlfriend'])

// Локальное состояние
const localWorkFormat = ref(props.workFormat)
const localHasGirlfriend = ref(props.hasGirlfriend)

// Отслеживаем изменения пропсов
watch(() => props.workFormat, (newValue) => {
  localWorkFormat.value = newValue
})

watch(() => props.hasGirlfriend, (newValue) => {
  localHasGirlfriend.value = newValue
})

// Методы
const selectFormat = (format) => {
  localWorkFormat.value = format
  emit('update:workFormat', format)
  
  // Если выбрали салон, сбрасываем подругу
  if (format === 'salon') {
    localHasGirlfriend.value = false
    emit('update:hasGirlfriend', false)
  }
}

const toggleGirlfriend = () => {
  localHasGirlfriend.value = !localHasGirlfriend.value
  emit('update:hasGirlfriend', localHasGirlfriend.value)
}
</script>

<style scoped>
.work-format-options {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.radio-item {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 12px;
  border: 1px solid #e5e5e5;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.radio-item:hover {
  border-color: #1890ff;
  background: #f0f8ff;
}

.custom-radio {
  width: 20px;
  height: 20px;
  border: 2px solid #d9d9d9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: #fff;
  flex-shrink: 0;
}

.custom-radio.checked {
  border-color: #1890ff;
}

.custom-radio.checked::after {
  content: '';
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #1890ff;
}

.radio-content {
  flex: 1;
}

.radio-title {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  line-height: 1.4;
}

.radio-description {
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.4;
  margin-top: 2px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px 0;
}

.checkbox-item input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
}

.checkbox-item span {
  font-size: 16px;
  color: #1a1a1a;
}
</style>