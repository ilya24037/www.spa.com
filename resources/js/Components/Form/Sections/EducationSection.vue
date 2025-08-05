<template>
  <PageSection title="Образование и сертификаты">
    <p class="section-description">
      Укажите ваше образование, курсы и сертификаты. Это повысит доверие клиентов.
    </p>
    
    <div class="education-fields">
      <!-- Основное образование -->
      <div class="main-education">
        <h4 class="field-title">Основное образование</h4>
        
        <BaseSelect
          v-model="form.education_level"
          label="Уровень образования"
          placeholder="Выберите уровень"
          :options="educationLevelOptions"
          :error="errors.education_level"
        />

        <BaseInput
          v-model="form.university"
          label="Учебное заведение"
          placeholder="Название университета, колледжа или школы"
          :error="errors.university"
        />

        <BaseInput
          v-model="form.specialization"
          label="Специальность"
          placeholder="Ваша специальность или направление"
          :error="errors.specialization"
        />

        <BaseInput
          v-model="form.graduation_year"
          type="number"
          label="Год окончания"
          placeholder="2020"
          :error="errors.graduation_year"
        />
      </div>

      <!-- Курсы и дополнительное образование -->
      <div class="courses">
        <div class="courses-header">
          <h4 class="field-title">Курсы и дополнительное образование</h4>
          <button 
            type="button" 
            class="btn btn-primary"
            @click="addCourse"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить курс
          </button>
        </div>

        <div v-if="!form.courses || form.courses.length === 0" class="empty-courses">
          <p>Добавьте курсы повышения квалификации, которые вы проходили</p>
        </div>

        <div v-else class="courses-list">
          <div 
            v-for="(course, index) in form.courses"
            :key="course.id"
            class="course-item"
          >
            <div class="course-content">
              <div class="course-row">
                <BaseInput
                  v-model="course.name"
                  label="Название курса"
                  placeholder="Название курса или программы"
                  :error="getCourseError(index, 'name')"
                />
                <BaseInput
                  v-model="course.organization"
                  label="Организация"
                  placeholder="Название организации"
                  :error="getCourseError(index, 'organization')"
                />
              </div>
              
              <div class="course-row">
                <BaseInput
                  v-model="course.year"
                  label="Год"
                  type="number"
                  placeholder="2023"
                  :error="getCourseError(index, 'year')"
                />
                <BaseInput
                  v-model="course.duration"
                  label="Длительность"
                  placeholder="3 месяца, 72 часа"
                  :error="getCourseError(index, 'duration')"
                />
              </div>
              
              <BaseTextarea
                v-model="course.description"
                label="Описание"
                placeholder="Что вы изучали, какие навыки получили"
                :error="getCourseError(index, 'description')"
              />

              <BaseInput
                v-model="course.certificate_number"
                label="Номер сертификата"
                placeholder="Номер сертификата (если есть)"
                :error="getCourseError(index, 'certificate_number')"
              />
            </div>
            
            <button 
              type="button"
              class="remove-btn"
              @click="removeCourse(index)"
              title="Удалить курс"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Сертификаты -->
      <div class="certificates">
        <h4 class="field-title">Сертификаты</h4>
        
        <BaseCheckbox
          v-model="form.has_certificates"
          label="У меня есть сертификаты"
        />

        <div v-if="form.has_certificates" class="certificates-upload">
          <PhotoUploader
            v-model="form.certificate_photos"
            label="Фотографии сертификатов"
            :max-files="5"
            :error="errors.certificate_photos"
            hint="Загрузите фото ваших сертификатов для подтверждения квалификации"
          />
        </div>
      </div>

      <!-- Опыт работы -->
      <div class="work-experience">
        <h4 class="field-title">Опыт работы</h4>
        
        <BaseSelect
          v-model="form.experience_years"
          label="Общий стаж работы"
          placeholder="Выберите стаж"
          :options="experienceYearsOptions"
          :error="errors.experience_years"
        />

        <BaseTextarea
          v-model="form.work_history"
          label="История работы"
          placeholder="Опишите ваш опыт работы в данной сфере"
          :error="errors.work_history"
          hint="Расскажите о местах работы, должностях, достижениях"
        />
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/src/shared/ui/layout/PageSection/PageSection.vue'
import BaseInput from '@/src/shared/ui/atoms/TextInput/TextInput.vue'
import BaseTextarea from '@/src/shared/ui/atoms/Textarea/Textarea.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/Checkbox/Checkbox.vue'
import BaseSelect from '@/src/shared/ui/atoms/Select/Select.vue'
import PhotoUploader from '@/src/features/media/ui/PhotoUploader/PhotoUploader.vue'

export default {
  name: 'EducationSection',
  components: {
    PageSection,
    BaseInput,
    BaseTextarea,
    BaseCheckbox,
    BaseSelect,
    PhotoUploader
  },
  props: {
    form: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      educationLevelOptions: [
        { value: 'school', label: 'Среднее образование' },
        { value: 'college', label: 'Среднее специальное' },
        { value: 'bachelor', label: 'Высшее (бакалавриат)' },
        { value: 'master', label: 'Высшее (магистратура)' },
        { value: 'phd', label: 'Высшее (аспирантура)' },
        { value: 'other', label: 'Другое' }
      ],
      experienceYearsOptions: [
        { value: 'less_than_1', label: 'Менее 1 года' },
        { value: '1_2', label: '1-2 года' },
        { value: '3_5', label: '3-5 лет' },
        { value: '6_10', label: '6-10 лет' },
        { value: 'more_than_10', label: 'Более 10 лет' }
      ]
    }
  },
  methods: {
    addCourse() {
      const newCourse = {
        id: Date.now() + Math.random(),
        name: '',
        organization: '',
        year: '',
        duration: '',
        description: '',
        certificate_number: ''
      }
      
      if (!this.form.courses) {
        this.form.courses = []
      }
      
      this.form.courses.push(newCourse)
    },
    
    removeCourse(index) {
      this.form.courses.splice(index, 1)
    },
    
    getCourseError(index, field) {
      const errorKey = `courses.${index}.${field}`
      return this.errors[errorKey] || ''
    }
  }
}
</script>

<style scoped>
.section-description {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.education-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.main-education,
.courses,
.certificates,
.work-experience {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.field-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin: 0 0 16px 0;
}

.course-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}

.course-row .form-input {
  flex: 1;
}

.courses-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid #007bff;
  border-radius: 6px;
  background: #007bff;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover {
  background: #0056b3;
  border-color: #0056b3;
}

.btn svg {
  width: 16px;
  height: 16px;
}

.empty-courses {
  padding: 32px 16px;
  text-align: center;
  color: #666;
  font-size: 14px;
  background: white;
  border-radius: 8px;
  border: 2px dashed #ddd;
}

.courses-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.course-item {
  position: relative;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.course-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.remove-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.remove-btn:hover {
  background: #fff5f5;
}

.remove-btn svg {
  width: 14px;
  height: 14px;
}

.certificates-upload {
  margin-top: 16px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .course-row {
    flex-direction: column;
    gap: 8px;
  }
  
  .courses-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  
  .btn {
    justify-content: center;
  }
}
</style> 

