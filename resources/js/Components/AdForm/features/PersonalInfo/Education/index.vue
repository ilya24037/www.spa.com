<template>
  <FormSection
    title="Образование и сертификаты"
    hint="Укажите ваше образование, курсы и сертификаты. Это повысит доверие клиентов."
    :errors="errors"
    :error-keys="['education_level', 'university', 'specialization', 'graduation_year']"
  >
    <div class="space-y-8">
      <!-- Основное образование -->
      <div class="space-y-4">
        <h4 class="text-lg font-medium text-gray-900">Основное образование</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField
            label="Уровень образования"
            :error="errors.education_level"
          >
            <BaseSelect
              :model-value="educationLevel"
              @update:model-value="updateField('education_level', $event)"
              :options="educationLevelOptions"
              placeholder="Выберите уровень"
            />
          </FormField>

          <FormField
            label="Год окончания"
            :error="errors.graduation_year"
          >
            <BaseInput
              :model-value="graduationYear"
              @update:model-value="updateField('graduation_year', $event)"
              type="number"
              placeholder="2020"
            />
          </FormField>
        </div>

        <FormField
          label="Учебное заведение"
          :error="errors.university"
        >
          <BaseInput
            :model-value="university"
            @update:model-value="updateField('university', $event)"
            placeholder="Название университета, колледжа или школы"
          />
        </FormField>

        <FormField
          label="Специальность"
          :error="errors.specialization"
        >
          <BaseInput
            :model-value="specialization"
            @update:model-value="updateField('specialization', $event)"
            placeholder="Ваша специальность или направление"
          />
        </FormField>
      </div>

      <!-- Курсы и дополнительное образование -->
      <CoursesSection
        :courses="courses"
        :errors="errors"
        @add-course="addCourse"
        @remove-course="removeCourse"
        @update-course="updateCourse"
      />

      <!-- Сертификаты -->
      <CertificatesSection
        :has-certificates="hasCertificates"
        :certificate-photos="certificatePhotos"
        :errors="errors"
        @update:has-certificates="updateField('has_certificates', $event)"
        @update:certificate-photos="updateField('certificate_photos', $event)"
      />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Подкомпоненты
import CoursesSection from './components/CoursesSection.vue'
import CertificatesSection from './components/CertificatesSection.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const educationLevel = computed(() => store.formData.education_level || '')
const university = computed(() => store.formData.university || '')
const specialization = computed(() => store.formData.specialization || '')
const graduationYear = computed(() => store.formData.graduation_year || '')
const courses = computed(() => store.formData.courses || [])
const hasCertificates = computed(() => store.formData.has_certificates || false)
const certificatePhotos = computed(() => store.formData.certificate_photos || [])

// Опции для выбора
const educationLevelOptions = [
  { value: 'school', label: 'Среднее образование' },
  { value: 'college', label: 'Среднее специальное' },
  { value: 'bachelor', label: 'Высшее (бакалавриат)' },
  { value: 'master', label: 'Высшее (магистратура)' },
  { value: 'phd', label: 'Высшее (аспирантура)' },
  { value: 'other', label: 'Другое' }
]

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateField = (field, value) => {
  console.log('updateField called:', field, value)
  store.updateField(field, value)
}

const addCourse = () => {
  const newCourse = {
    id: Date.now() + Math.random(),
    name: '',
    organization: '',
    year: '',
    duration: '',
    description: '',
    certificate_number: ''
  }
  
  const currentCourses = [...courses.value]
  currentCourses.push(newCourse)
  
  console.log('addCourse called, new courses:', currentCourses)
  store.updateField('courses', currentCourses)
}

const removeCourse = (index) => {
  const currentCourses = [...courses.value]
  currentCourses.splice(index, 1)
  
  console.log('removeCourse called, index:', index, 'new courses:', currentCourses)
  store.updateField('courses', currentCourses)
}

const updateCourse = (index, field, value) => {
  const currentCourses = [...courses.value]
  currentCourses[index] = { ...currentCourses[index], [field]: value }
  
  console.log('updateCourse called:', index, field, value)
  store.updateField('courses', currentCourses)
}
</script>