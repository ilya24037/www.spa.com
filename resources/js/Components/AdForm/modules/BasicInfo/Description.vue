<template>
  <FormSection
    title="О себе"
    hint="Расскажите о себе и своих услугах подробно"
    required
    :errors="errors"
    :error-keys="['description']"
  >
    <FormField
      label="Описание"
      hint="Минимум 50 символов. Расскажите о ваших услугах, опыте, подходе к работе"
      required
      :error="errors.description"
    >
      <textarea
        v-model="localDescription"
        @input="updateDescription"
        rows="6"
        placeholder="Расскажите о себе, своих услугах, опыте работы..."
        maxlength="2000"
      ></textarea>
      
      <!-- Счетчик символов -->
      <div class="character-counter">
        <span class="current">{{ characterCount }}</span>
        <span class="separator">/</span>
        <span class="max">2000</span>
        <span v-if="characterCount < 50" class="min-warning">
          (минимум 50 символов)
        </span>
      </div>
    </FormField>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  description: {
    type: String,
    default: ''
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:description'])

// Локальное состояние
const localDescription = ref(props.description)

// Счетчик символов
const characterCount = computed(() => localDescription.value.length)

// Отслеживаем изменения пропсов
watch(() => props.description, (newValue) => {
  localDescription.value = newValue
})

// Методы
const updateDescription = () => {
  emit('update:description', localDescription.value)
}
</script>

<style scoped>
.character-counter {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 14px;
  color: #8c8c8c;
}

.current {
  color: #1a1a1a;
  font-weight: 500;
}

.min-warning {
  color: #ff4d4f;
  font-size: 12px;
}

/* Стили для textarea унаследуются из FormField */
</style>