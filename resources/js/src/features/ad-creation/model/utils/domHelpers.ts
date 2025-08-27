import type { Ref } from 'vue'

// Типы для refs
export interface FormFieldRefs {
  titleInputRef: Ref<HTMLInputElement | null>
  priceInputRef: Ref<HTMLInputElement | null>
  phoneInputRef: Ref<HTMLInputElement | null>
  citySelectRef: Ref<HTMLSelectElement | null>
  clientsSectionRef: Ref<HTMLDivElement | null>
}

export type FieldName = 'title' | 'price' | 'phone' | 'city' | 'clients'

// Единое маппирование путей к полям
const FIELD_MAPPING: Record<string, FieldName> = {
  'parameters.title': 'title',
  'price': 'price',
  'contacts.phone': 'phone',
  'geo.city': 'city',
  'clients': 'clients'
}

// Классы для ошибок
const ERROR_CLASSES = ['border-2', 'border-red-500', 'ring-2', 'ring-red-200']

// Получение ref по имени поля
const getRef = (field: FieldName, refs: FormFieldRefs) => {
  return refs[`${field}InputRef` as keyof FormFieldRefs] || 
         refs[`${field}SelectRef` as keyof FormFieldRefs] || 
         refs[`${field}SectionRef` as keyof FormFieldRefs]
}

// Подсветка всех полей с ошибками
export function highlightAllErrors(errors: Record<string, any>, refs: FormFieldRefs): void {
  Object.entries(FIELD_MAPPING).forEach(([key, field]) => {
    if (errors[key]) {
      const ref = getRef(field, refs)
      if (ref?.value && ref.value.classList) {
        ref.value.classList.add(...ERROR_CLASSES)
      }
    }
  })
}

// Очистка подсветки
export function clearAllHighlights(refs: FormFieldRefs): void {
  Object.values(refs).forEach(ref => {
    if (ref?.value && ref.value.classList) {
      ref.value.classList.remove(...ERROR_CLASSES)
    }
  })
}

export function clearHighlight(field: FieldName, refs: FormFieldRefs): void {
  const ref = getRef(field, refs)
  if (ref?.value && ref.value.classList) {
    ref.value.classList.remove(...ERROR_CLASSES)
  }
}

// Прокрутка к первой ошибке
export function scrollToFirstError(errors: Record<string, any>, refs: FormFieldRefs): void {
  const firstErrorKey = Object.keys(errors).find(key => FIELD_MAPPING[key])
  if (firstErrorKey) {
    const field = FIELD_MAPPING[firstErrorKey]
    const ref = getRef(field, refs)
    if (ref?.value && typeof ref.value.scrollIntoView === 'function') {
      setTimeout(() => ref.value.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100)
    }
  }
}