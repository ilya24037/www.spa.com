<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h4 class="text-lg font-medium text-gray-900">Курсы и дополнительное образование</h4>
      <ActionButton
        variant="secondary"
        size="small"
        @click="$emit('add-course')"
      >
        + Добавить курс
      </ActionButton>
    </div>

    <div v-if="courses.length === 0" class="text-center py-8 text-gray-500">
      Курсы не добавлены. Нажмите "Добавить курс" чтобы указать дополнительное образование.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(course, index) in courses"
        :key="course.id"
        class="relative p-4 border border-gray-200 rounded-lg bg-gray-50"
      >
        <!-- Кнопка удаления -->
        <button
          type="button"
          @click="$emit('remove-course', index)"
          class="absolute top-3 right-3 p-1 text-gray-400 hover:text-red-500 transition-colors"
          title="Удалить курс"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>

        <div class="space-y-4 pr-8">
          <!-- Основная информация -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Название курса"
              :error="getCourseError(index, 'name')"
            >
              <BaseInput
                :model-value="course.name"
                @update:model-value="updateCourse(index, 'name', $event)"
                placeholder="Название курса или программы"
              />
            </FormField>

            <FormField
              label="Организация"
              :error="getCourseError(index, 'organization')"
            >
              <BaseInput
                :model-value="course.organization"
                @update:model-value="updateCourse(index, 'organization', $event)"
                placeholder="Учебный центр, школа, онлайн-платформа"
              />
            </FormField>

            <FormField
              label="Год прохождения"
              :error="getCourseError(index, 'year')"
            >
              <BaseInput
                :model-value="course.year"
                @update:model-value="updateCourse(index, 'year', $event)"
                type="number"
                placeholder="2023"
              />
            </FormField>

            <FormField
              label="Продолжительность"
              :error="getCourseError(index, 'duration')"
            >
              <BaseInput
                :model-value="course.duration"
                @update:model-value="updateCourse(index, 'duration', $event)"
                placeholder="40 часов, 3 месяца"
              />
            </FormField>
          </div>

          <!-- Описание -->
          <FormField
            label="Описание"
            hint="Что вы изучали, какие навыки получили"
            :error="getCourseError(index, 'description')"
          >
            <BaseTextarea
              :model-value="course.description"
              @update:model-value="updateCourse(index, 'description', $event)"
              :rows="3"
              placeholder="Описание изученного материала и полученных навыков"
            />
          </FormField>

          <!-- Номер сертификата -->
          <FormField
            label="Номер сертификата"
            hint="Номер сертификата (если есть)"
            :error="getCourseError(index, 'certificate_number')"
          >
            <BaseInput
              :model-value="course.certificate_number"
              @update:model-value="updateCourse(index, 'certificate_number', $event)"
              placeholder="ABC-123456"
            />
          </FormField>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import ActionButton from '@/Components/UI/Buttons/ActionButton.vue'

const props = defineProps({
  courses: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['add-course', 'remove-course', 'update-course'])

// Методы
const updateCourse = (index, field, value) => {
  emit('update-course', index, field, value)
}

const getCourseError = (index, field) => {
  const errorKey = `courses.${index}.${field}`
  return props.errors[errorKey] || ''
}
</script>