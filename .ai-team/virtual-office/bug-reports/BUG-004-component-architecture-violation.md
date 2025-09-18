# 🐛 BUG-004: Нарушение FSD архитектуры - компонент-монолит

## 📋 Метаданные
- **ID:** BUG-004
- **Приоритет:** 🟠 ВЫСОКИЙ (техдолг)
- **Компонент:** AdForm.vue
- **Категория:** Architecture / Code Quality
- **Статус:** Открыт
- **Дата обнаружения:** 2025-09-17

## 📝 Описание проблемы
Компонент AdForm.vue содержит 1100+ строк кода, нарушая принципы Single Responsibility и FSD архитектуры. Бизнес-логика смешана с UI, отсутствуют композаблы.

## 📍 Локализация
```
resources/js/src/features/ad-creation/ui/AdForm.vue
- 1102 строки кода
- 10+ computed свойств
- 5+ watchers
- 200+ строк логики валидации
- Прямые DOM манипуляции
```

## 🔄 Как обнаружено
1. Анализ размера файла: 1102 строки
2. Подсчет ответственностей: UI + валидация + скролл + сохранение
3. Проверка на соответствие FSD: не соответствует

## ❗ Ожидаемое поведение
- Компоненты не более 200 строк
- Логика в композаблах
- Разделение на подкомпоненты
- Соответствие FSD структуре

## ⚡ Фактическое поведение
- Монолитный компонент 1100+ строк
- Вся логика внутри компонента
- Сложность поддержки и тестирования

## 🎯 Влияние на систему
- **Maintainability:** Очень низкая
- **Testability:** Сложно тестировать
- **Performance:** Лишние ре-рендеры
- **Team Work:** Конфликты при мерже
- **Onboarding:** Сложно для новичков

## ✅ Предлагаемое решение

### 1. Разбить на композаблы:
```typescript
// composables/useAdFormValidation.ts
export const useAdFormValidation = () => {
  const validateSection = (section: string) => { /* ... */ }
  const scrollToError = () => { /* ... */ }
  return { validateSection, scrollToError }
}

// composables/useAdFormData.ts
export const useAdFormData = (initialData?: AdData) => {
  const form = reactive({ /* ... */ })
  const resetForm = () => { /* ... */ }
  return { form, resetForm }
}

// composables/useAdFormSections.ts
export const useAdFormSections = () => {
  const sections = ref([/* ... */])
  const currentSection = ref(0)
  return { sections, currentSection }
}
```

### 2. Создать подкомпоненты:
```
features/ad-creation/ui/
├── AdForm.vue (оркестратор, ~200 строк)
├── components/
│   ├── AdFormHeader.vue
│   ├── AdFormSections.vue
│   ├── AdFormServices.vue
│   ├── AdFormGeo.vue
│   ├── AdFormMedia.vue
│   └── AdFormActions.vue
└── composables/
    ├── useAdFormValidation.ts
    ├── useAdFormData.ts
    └── useAdFormSections.ts
```

### 3. Вынести типы:
```typescript
// model/types.ts
export interface AdFormData {
  title: string
  description: string
  services: Record<string, boolean>
  // ...
}
```

## 🧪 Тест-кейсы для проверки исправления
1. Каждый композабл должен иметь unit-тесты
2. Каждый подкомпонент < 200 строк
3. Главный компонент только orchestration
4. Все типы строго типизированы

## 📊 Метрики для мониторинга
- Размер компонентов (max 200 строк)
- Цикломатическая сложность (max 10)
- Test coverage (min 80%)
- Количество ответственностей на компонент (max 1)