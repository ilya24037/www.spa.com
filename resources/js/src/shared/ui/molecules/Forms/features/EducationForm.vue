<template>
  <FormSection
    title="Образование"
    description="Укажите информацию о вашем образовании и профессиональной подготовке"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :collapsible="collapsible"
    :collapsed="collapsed"
    required
    @update:model-value="updateValue"
    @field-change="onFieldChange"
    @toggle="onToggle"
  >
    <!-- Основная информация об образовании -->
    <FormFieldGroup
      label="Основное образование"
      description="Информация о высшем образовании"
      layout="column"
    >
      <!-- Уровень образования -->
      <div class="form-field">
        <label class="form-field-label" :for="`${componentId}-education-level`">
          Уровень образования <span class="text-red-500">*</span>
        </label>
        <select
          :id="`${componentId}-education-level`"
          :value="formData?.education_level"
          :disabled="disabled || readonly"
          class="form-field-input"
          :class="{
            'border-red-300': hasError('education_level'),
            'bg-gray-500': readonly
          }"
          @change="updateField('education_level', ($event?.target as HTMLSelectElement).value)"
          @blur="touchField('education_level')"
        >
          <option value="">
            Выберите уровень образования
          </option>
          <option value="secondary">
            Среднее образование
          </option>
          <option value="secondary_vocational">
            Среднее профессиональное
          </option>
          <option value="higher_bachelor">
            Высшее (бакалавриат)
          </option>
          <option value="higher_specialist">
            Высшее (специалитет)
          </option>
          <option value="higher_master">
            Высшее (магистратура)
          </option>
          <option value="postgraduate">
            Аспирантура/докторантура
          </option>
        </select>
        <div v-if="hasError('education_level')" class="form-field-error">
          {{ getError('education_level') }}
        </div>
      </div>

      <!-- Университет/учебное заведение -->
      <div class="form-field">
        <label class="form-field-label" :for="`${componentId}-university`">
          Учебное заведение <span class="text-red-500">*</span>
        </label>
        <input
          :id="`${componentId}-university`"
          type="text"
          :value="formData?.university"
          :disabled="disabled || readonly"
          :readonly="readonly"
          placeholder="Название университета, института, колледжа"
          class="form-field-input"
          :class="{
            'border-red-300': hasError('university'),
            'bg-gray-500': readonly
          }"
          @input="updateField('university', ($event?.target as HTMLInputElement).value)"
          @blur="touchField('university')"
        >
        <div v-if="hasError('university')" class="form-field-error">
          {{ getError('university') }}
        </div>
      </div>

      <!-- Специальность -->
      <div class="form-field">
        <label class="form-field-label" :for="`${componentId}-specialization`">
          Специальность <span class="text-red-500">*</span>
        </label>
        <input
          :id="`${componentId}-specialization`"
          type="text"
          :value="formData?.specialization"
          :disabled="disabled || readonly"
          :readonly="readonly"
          placeholder="Специальность/направление подготовки"
          class="form-field-input"
          :class="{
            'border-red-300': hasError('specialization'),
            'bg-gray-500': readonly
          }"
          @input="updateField('specialization', ($event?.target as HTMLInputElement).value)"
          @blur="touchField('specialization')"
        >
        <div v-if="hasError('specialization')" class="form-field-error">
          {{ getError('specialization') }}
        </div>
      </div>

      <!-- Год окончания -->
      <FormFieldGroup layout="row" responsive>
        <div class="form-field">
          <label class="form-field-label" :for="`${componentId}-graduation-year`">
            Год окончания <span class="text-red-500">*</span>
          </label>
          <input
            :id="`${componentId}-graduation-year`"
            type="number"
            :value="formData?.graduation_year"
            :disabled="disabled || readonly"
            :readonly="readonly"
            :min="1950"
            :max="currentYear + 10"
            placeholder="2024"
            class="form-field-input"
            :class="{
              'border-red-300': hasError('graduation_year'),
              'bg-gray-500': readonly
            }"
            @input="updateField('graduation_year', parseInt(($event?.target as HTMLInputElement).value))"
            @blur="touchField('graduation_year')"
          >
          <div v-if="hasError('graduation_year')" class="form-field-error">
            {{ getError('graduation_year') }}
          </div>
        </div>

        <div class="form-field">
          <label class="form-field-label" :for="`${componentId}-experience-years`">
            Опыт работы <span class="text-red-500">*</span>
          </label>
          <select
            :id="`${componentId}-experience-years`"
            :value="formData?.experience_years"
            :disabled="disabled || readonly"
            class="form-field-input"
            :class="{
              'border-red-300': hasError('experience_years'),
              'bg-gray-500': readonly
            }"
            @change="updateField('experience_years', ($event?.target as HTMLSelectElement).value)"
            @blur="touchField('experience_years')"
          >
            <option value="">
              Выберите опыт
            </option>
            <option value="no_experience">
              Без опыта
            </option>
            <option value="less_1_year">
              Менее 1 года
            </option>
            <option value="1_3_years">
              1-3 года
            </option>
            <option value="3_5_years">
              3-5 лет
            </option>
            <option value="5_10_years">
              5-10 лет
            </option>
            <option value="more_10_years">
              Более 10 лет
            </option>
          </select>
          <div v-if="hasError('experience_years')" class="form-field-error">
            {{ getError('experience_years') }}
          </div>
        </div>
      </FormFieldGroup>
    </FormFieldGroup>

    <!-- Дополнительные курсы и сертификаты -->
    <FormFieldGroup
      label="Дополнительное обучение"
      description="Курсы повышения квалификации, сертификаты, тренинги"
      layout="column"
    >
      <DynamicFieldList
        v-model="formData?.courses"
        label="Курсы и сертификаты"
        :item-template="courseTemplate"
        :errors="coursesErrors"
        :disabled="disabled"
        :readonly="readonly"
        add-button-text="Добавить курс"
        remove-button-text="Удалить курс"
        empty-state-text="Добавьте информацию о пройденных курсах и полученных сертификатах"
        sortable
        allow-duplicate
        show-count
        :get-item-title="getCourseTitle"
        :get-item-key="(item, index) => item?.id || index"
        @item-add="onCourseAdd"
        @item-remove="onCourseRemove"
        @item-change="onCourseChange"
      >
        <template #default="{ item, index: _index, updateItem, getItemError }">
          <!-- Название курса -->
          <div class="form-field">
            <label class="form-field-label">
              Название курса <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              :value="item?.name"
              :disabled="disabled || readonly"
              :readonly="readonly"
              placeholder="Название курса/сертификата"
              class="form-field-input"
              :class="{ 'border-red-300': getItemError('name') }"
              @input="updateItem('name', ($event?.target as HTMLInputElement).value)"
            >
            <div v-if="getItemError('name')" class="form-field-error">
              {{ getItemError('name') }}
            </div>
          </div>

          <!-- Организация и год в одной строке -->
          <FormFieldGroup layout="row" responsive>
            <div class="form-field">
              <label class="form-field-label">
                Организация <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                :value="item?.organization"
                :disabled="disabled || readonly"
                :readonly="readonly"
                placeholder="Название организации"
                class="form-field-input"
                :class="{ 'border-red-300': getItemError('organization') }"
                @input="updateItem('organization', ($event?.target as HTMLInputElement).value)"
              >
              <div v-if="getItemError('organization')" class="form-field-error">
                {{ getItemError('organization') }}
              </div>
            </div>

            <div class="form-field">
              <label class="form-field-label">
                Год получения <span class="text-red-500">*</span>
              </label>
              <input
                type="number"
                :value="item?.year"
                :disabled="disabled || readonly"
                :readonly="readonly"
                :min="1980"
                :max="currentYear"
                placeholder="2024"
                class="form-field-input"
                :class="{ 'border-red-300': getItemError('year') }"
                @input="updateItem('year', parseInt(($event?.target as HTMLInputElement).value))"
              >
              <div v-if="getItemError('year')" class="form-field-error">
                {{ getItemError('year') }}
              </div>
            </div>
          </FormFieldGroup>

          <!-- Продолжительность и номер сертификата -->
          <FormFieldGroup layout="row" responsive>
            <div class="form-field">
              <label class="form-field-label">Продолжительность</label>
              <input
                type="text"
                :value="item?.duration"
                :disabled="disabled || readonly"
                :readonly="readonly"
                placeholder="40 часов, 2 недели, 6 месяцев"
                class="form-field-input"
                @input="updateItem('duration', ($event?.target as HTMLInputElement).value)"
              >
            </div>

            <div class="form-field">
              <label class="form-field-label">Номер сертификата</label>
              <input
                type="text"
                :value="item?.certificate_number"
                :disabled="disabled || readonly"
                :readonly="readonly"
                placeholder="Серия и номер документа"
                class="form-field-input"
                @input="updateItem('certificate_number', ($event?.target as HTMLInputElement).value)"
              >
            </div>
          </FormFieldGroup>

          <!-- Описание -->
          <div class="form-field">
            <label class="form-field-label">Описание</label>
            <textarea
              :value="item?.description"
              :disabled="disabled || readonly"
              :readonly="readonly"
              rows="3"
              placeholder="Краткое описание полученных знаний и навыков"
              class="form-field-input resize-none"
              @input="updateItem('description', ($event?.target as HTMLTextAreaElement).value)"
            />
          </div>
        </template>
      </DynamicFieldList>
    </FormFieldGroup>

    <!-- Сертификаты и дипломы -->
    <FormFieldGroup
      label="Подтверждающие документы"
      description="Загрузите фотографии дипломов и сертификатов"
      layout="column"
    >
      <!-- Наличие сертификатов -->
      <div class="form-field">
        <label class="form-field-label">
          <input
            type="checkbox"
            :checked="formData?.has_certificates"
            :disabled="disabled || readonly"
            class="form-field-checkbox mr-2"
            @change="updateField('has_certificates', ($event?.target as HTMLInputElement).checked)"
          >
          У меня есть сертификаты и дипломы для подтверждения квалификации
        </label>
      </div>

      <!-- Загрузка фотографий сертификатов -->
      <div v-if="formData?.has_certificates" class="form-field">
        <label class="form-field-label">
          Фотографии документов
        </label>
        <div
          class="upload-area" 
          :class="{ 'border-dashed border-2 border-blue-300 bg-blue-50': isDragOver }"
          @drop?.prevent="handleFileDrop"
          @dragover?.prevent="isDragOver = true"
          @dragleave="isDragOver = false"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            accept="image/*"
            :disabled="disabled || readonly"
            class="hidden"
            @change="handleFileSelect"
          >
          
          <div v-if="!formData?.certificate_photos?.length" class="upload-empty-state">
            <svg
              class="w-12 h-12 text-gray-500 mx-auto mb-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 16a4 4 0 01-.88-7?.903A5 5 0 1115?.9 6L16 6a5 5 0 011 9?.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
            <p class="text-gray-500 mb-2">
              Перетащите файлы сюда или
            </p>
            <button
              type="button"
              :disabled="disabled || readonly"
              class="btn-primary"
              @click="($refs?.fileInput as HTMLInputElement)?.click()"
            >
              Выберите файлы
            </button>
            <p class="text-xs text-gray-500 mt-2">
              Поддерживаются: JPG, PNG, GIF. Максимум 5 МБ на файл
            </p>
          </div>

          <div v-else class="upload-preview-grid">
            <div
              v-for="(photo, index) in formData?.certificate_photos"
              :key="index"
              class="upload-preview-item"
            >
              <img
                :src="getFilePreview(photo)"
                :alt="`Сертификат ${index + 1}`"
                class="upload-preview-image"
                @error="handleImageError"
              >
              <button
                type="button"
                :disabled="disabled || readonly"
                class="upload-preview-remove"
                @click="removePhoto(index)"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
            
            <button
              type="button"
              :disabled="disabled || readonly || (formData?.certificate_photos?.length >= maxPhotos)"
              class="upload-add-more"
              @click="($refs?.fileInput as HTMLInputElement)?.click()"
            >
              <svg
                class="w-8 h-8 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                />
              </svg>
              <span class="text-sm text-gray-500">Добавить</span>
            </button>
          </div>
        </div>
        <div v-if="hasError('certificate_photos')" class="form-field-error">
          {{ getError('certificate_photos') }}
        </div>
      </div>
    </FormFieldGroup>

    <!-- Дополнительная информация -->
    <FormFieldGroup
      label="Опыт работы"
      description="Краткое описание профессионального опыта"
      layout="column"
    >
      <div class="form-field">
        <label class="form-field-label" :for="`${componentId}-work-history`">
          Описание опыта работы
        </label>
        <textarea
          :id="`${componentId}-work-history`"
          :value="formData?.work_history"
          :disabled="disabled || readonly"
          :readonly="readonly"
          rows="5"
          placeholder="Опишите ваш профессиональный опыт, ключевые достижения, специализацию..."
          class="form-field-input resize-none"
          :class="{
            'border-red-300': hasError('work_history'),
            'bg-gray-500': readonly
          }"
          @input="updateField('work_history', ($event?.target as HTMLTextAreaElement).value)"
          @blur="touchField('work_history')"
        />
        <div v-if="hasError('work_history')" class="form-field-error">
          {{ getError('work_history') }}
        </div>
        <p class="text-xs text-gray-500 mt-1">
          Расскажите о своем опыте работы в сфере массажа и смежных областях
        </p>
      </div>
    </FormFieldGroup>

    <template #footer>
      <div class="flex justify-between items-center text-sm text-gray-500">
        <span>Все поля помеченные * обязательны для заполнения</span>
        <span v-if="formData">Заполнено: {{ completionPercentage }}%</span>
      </div>
    </template>
  </FormSection>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import FormSection from '../FormSection.vue'
import FormFieldGroup from '../components/FormFieldGroup.vue'
import DynamicFieldList from '../components/DynamicFieldList.vue'
import { useForm as _useForm } from '../composables/useForm'
import type { EducationFormData, Course, FormErrors } from '../types/form.types'

// Уникальный ID для экземпляра компонента
const componentId = `education-form-${Math.random().toString(36).substr(2, 9)}`

interface Props {
  modelValue: EducationFormData
  errors?: FormErrors
  disabled?: boolean
  readonly?: boolean
  collapsible?: boolean
  collapsed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    disabled: false,
    readonly: false,
    collapsible: false,
    collapsed: false
})

const emit = defineEmits<{
  'update:modelValue': [value: EducationFormData]
  'field-change': [fieldName: string, value: any]
  'toggle': [collapsed: boolean]
  'course-add': [course: Course]
  'course-remove': [course: Course, index: number]
  'file-upload': [files: File[]]
}>()

// Локальное состояние
const isDragOver = ref(false)
const fileInput = ref<HTMLInputElement>()
const maxPhotos = 10

// Вычисляемые свойства
const currentYear = new Date().getFullYear()

const formData = computed(() => props?.modelValue)

const coursesErrors = computed(() => {
    const errors: Record<string, string> = {}
    Object?.keys(props?.errors).forEach(key => {
        if (key?.startsWith('courses.')) {
            errors[key?.replace('courses.', '')] = props?.errors[key] as string
        }
    })
    return errors
})

const completionPercentage = computed(() => {
    const requiredFields = [
        'education_level', 'university', 'specialization', 
        'graduation_year', 'experience_years'
    ]
    const completed = requiredFields?.filter(field => !!formData?.value[field as keyof EducationFormData]).length
    return Math?.round((completed / requiredFields?.length) * 100)
})

// Методы
const updateValue = (value: any) => {
    emit('update:modelValue', value)
}

const updateField = (fieldName: string, value: any) => {
    const newData = { ...formData?.value, [fieldName]: value }
    emit('update:modelValue', newData)
    emit('field-change', fieldName, value)
}

const touchField = (fieldName: string) => {
    // Эмулируем touch для валидации
}

const hasError = (fieldName: string): boolean => {
    return !!props?.errors[fieldName]
}

const getError = (fieldName: string): string => {
    const error = props?.errors[fieldName]
    if (Array.isArray(error)) {
        return error[0] || ''
    }
    return typeof error === 'string' ? error : ''
}

const onFieldChange = (fieldName: string, value: any) => {
    emit('field-change', fieldName, value)
}

const onToggle = (collapsed: boolean) => {
    emit('toggle', collapsed)
}

// Методы для курсов
const courseTemplate = (): Course => ({
    id: Date?.now() + Math?.random(),
    name: '',
    organization: '',
    year: currentYear,
    duration: '',
    description: '',
    certificate_number: ''
})

const getCourseTitle = (course: Course): string => {
    return course?.name || `Курс ${course?.organization}`
}

const onCourseAdd = (course: Course) => {
    emit('course-add', course)
}

const onCourseRemove = (course: Course, index: number) => {
    emit('course-remove', course, index)
}

const onCourseChange = (index: number, field: string, value: any) => {
    emit('field-change', `courses.${index}.${field}`, value)
}

// Методы для файлов
const handleFileSelect = (event: Event) => {
    const target = event?.target as HTMLInputElement
    const files = Array?.from(target?.files || [])
    addPhotos(files)
}

const handleFileDrop = (event: DragEvent) => {
    if (isDragOver.value !== undefined) {
        isDragOver.value = false
    }
    const files = Array?.from(event?.dataTransfer?.files || [])
    addPhotos(files?.filter(file => file?.type.startsWith('image/')))
}

const addPhotos = (files: File[]) => {
    const currentPhotos = formData?.value.certificate_photos || []
    const availableSlots = maxPhotos - currentPhotos?.length
    const filesToAdd = files?.slice(0, availableSlots)
  
    // Проверка размера файлов
    const validFiles = filesToAdd?.filter(file => {
        if (file?.size > 5 * 1024 * 1024) { // 5MB
            console?.warn(`Файл ${file?.name} слишком большой`)
            return false
        }
        return true
    })

    if (validFiles?.length > 0) {
        const newPhotos = [...currentPhotos, ...validFiles]
        updateField('certificate_photos', newPhotos)
        emit('file-upload', validFiles)
    }
}

const removePhoto = (index: number) => {
    const currentPhotos = [...(formData?.value.certificate_photos || [])]
    currentPhotos?.splice(index, 1)
    updateField('certificate_photos', currentPhotos)
}

const getFilePreview = (file: File): string => {
    return URL?.createObjectURL(file)
}

const handleImageError = (event: Event) => {
    const img = event?.target as HTMLImageElement
    if (img && img.style) {
        img.style.display = 'none'
    }
}

// Очистка URL объектов при размонтировании
watch(() => formData?.value.certificate_photos, (newPhotos, oldPhotos) => {
    if (oldPhotos) {
        oldPhotos?.forEach(file => {
            if (file instanceof File) {
                URL?.revokeObjectURL(URL?.createObjectURL(file))
            }
        })
    }
})
</script>

<style scoped>
/* Базовые стили полей формы */
.form-field {
  @apply space-y-1;
}

.form-field-label {
  @apply block text-sm font-medium text-gray-500;
}

.form-field-input {
  @apply block w-full px-3 py-2 border border-gray-500 rounded-md shadow-sm 
         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         disabled:bg-gray-500 disabled:cursor-not-allowed;
}

.form-field-checkbox {
  @apply rounded border-gray-500 text-blue-600 focus:ring-blue-500;
}

.form-field-error {
  @apply text-sm text-red-600;
}

/* Стили для загрузки файлов */
.upload-area {
  @apply border-2 border-gray-500 border-dashed rounded-lg p-6 transition-colors;
}

.upload-empty-state {
  @apply text-center;
}

.upload-preview-grid {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4;
}

.upload-preview-item {
  @apply relative group;
}

.upload-preview-image {
  @apply w-full h-24 object-cover rounded border border-gray-500;
}

.upload-preview-remove {
  @apply absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full
         flex items-center justify-center opacity-0 group-hover:opacity-100 
         transition-opacity hover:bg-red-600;
}

.upload-add-more {
  @apply flex flex-col items-center justify-center h-24 border-2 border-gray-500 
         border-dashed rounded hover:border-gray-500 hover:bg-gray-500 
         transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Кнопки */
.btn-primary {
  @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium 
         rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 
         focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Адаптивность */
@media (max-width: 640px) {
  .upload-preview-grid {
    @apply grid-cols-2 gap-3;
  }
  
  .upload-preview-image {
    @apply h-20;
  }
  
  .upload-add-more {
    @apply h-20;
  }
}
</style>