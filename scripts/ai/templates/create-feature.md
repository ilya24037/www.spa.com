# Шаблон создания новой feature

## Задача: Создать feature {FEATURE_NAME}

### Контекст (помни):
- Структура FSD: resources/js/src/features/{feature-name}/
- TypeScript обязателен
- Pinia для state management

### Структура feature:
```
features/{feature-name}/
├── model/
│   ├── {feature}.store.ts    # Pinia store
│   ├── types.ts              # TypeScript типы
│   └── index.ts              # Экспорты
├── ui/
│   └── {FeatureName}/
│       ├── {FeatureName}.vue
│       └── index.ts
└── index.ts                  # Публичное API
```

### План создания:
1. Создай структуру папок
2. Определи TypeScript интерфейсы в types.ts
3. Создай Pinia store с типизацией
4. Реализуй UI компонент с composables
5. Добавь обработку всех состояний
6. Экспортируй публичное API

### КРИТИЧЕСКИ ВАЖНО:
- Используй композицию, НЕ наследование
- ВСЕ props должны иметь типы и defaults
- ОБЯЗАТЕЛЬНО добавь loading и error состояния