# 🧪 TEST CASE TC-003: Тесты производительности

## 📋 Метаданные
- **ID:** TC-003
- **Компоненты:** AdForm, PhotoUploadZone
- **Приоритет:** 🟡 СРЕДНИЙ
- **Тип:** Performance test
- **Автоматизация:** Lighthouse, Jest Performance

## 📝 Описание
Проверка производительности критических компонентов под нагрузкой.

## 🔧 Предусловия
1. Production build
2. Chrome DevTools Performance
3. Lighthouse CLI установлен
4. Стабильное окружение для тестов

## 📊 Метрики и пороговые значения
```javascript
const performanceThresholds = {
  FCP: 1800,        // First Contentful Paint < 1.8s
  LCP: 2500,        // Largest Contentful Paint < 2.5s
  FID: 100,         // First Input Delay < 100ms
  CLS: 0.1,         // Cumulative Layout Shift < 0.1
  TTI: 3800,        // Time to Interactive < 3.8s
  bundleSize: 500,  // KB for main chunk
  memoryLeak: 10    // MB increase after 100 operations
}
```

## 🔄 Тестовые сценарии

### Тест 1: Начальная загрузка формы
**Метрика:** Time to Interactive (TTI)
1. **Действие:** Открыть страницу создания объявления
2. **Измерение:**
   - FCP < 1.8s
   - LCP < 2.5s
   - TTI < 3.8s
3. **Ожидание:** Все метрики в пределах нормы

### Тест 2: Рендеринг большого количества услуг
**Метрика:** Rendering Performance
1. **Действие:** Отрендерить форму с 100+ услугами
2. **Измерение:**
   - Initial render < 500ms
   - Re-render при выборе < 16ms
   - No layout thrashing
3. **Ожидание:** Плавная работа без подтормаживаний

### Тест 3: Загрузка множественных фото
**Метрика:** Memory Usage
1. **Действие:** Загрузить 10 фото по 5MB каждое
2. **Измерение:**
   - Memory usage < 200MB
   - No memory leaks
   - Smooth upload progress
3. **Ожидание:** Стабильное потребление памяти

### Тест 4: Валидация формы с 50+ полями
**Метрика:** JavaScript Execution Time
1. **Действие:** Запустить валидацию всех полей
2. **Измерение:**
   - Validation time < 100ms
   - No blocking main thread > 50ms
3. **Ожидание:** Мгновенная валидация

### Тест 5: Переключение между секциями
**Метрика:** Animation Performance
1. **Действие:** Быстро переключать 10 секций
2. **Измерение:**
   - 60 FPS animations
   - No frame drops
   - CLS < 0.1
3. **Ожидание:** Плавные анимации

### Тест 6: Автосохранение при вводе
**Метрика:** Network Performance
1. **Действие:** Непрерывный ввод текста 60 секунд
2. **Измерение:**
   - Debounce работает (не > 1 запрос/сек)
   - Response time < 200ms
   - No request queuing
3. **Ожидание:** Оптимальная частота сохранения

### Тест 7: Bundle Size Analysis
**Метрика:** Code Splitting
```bash
npm run build -- --analyze
```
**Ожидание:**
- Main bundle < 500KB
- Lazy loaded chunks < 200KB each
- No duplicate dependencies

### Тест 8: Memory Leak Detection
**Сценарий:** 100 циклов создания/удаления формы
```javascript
for (let i = 0; i < 100; i++) {
  mountComponent()
  fillForm()
  unmountComponent()
  measureMemory()
}
```
**Ожидание:** Memory increase < 10MB

### Тест 9: Mobile Performance
**Устройство:** Moto G4 (Chrome DevTools)
1. **CPU Throttling:** 4x slowdown
2. **Network:** Slow 3G
3. **Ожидание:**
   - TTI < 10s
   - FCP < 3s
   - Usable interface

### Тест 10: Lighthouse Audit
```bash
lighthouse https://spa.com/ad/create \
  --only-categories=performance \
  --throttling-method=simulate \
  --output=json
```
**Ожидание:**
- Performance score > 85
- No critical issues
- All metrics green/yellow

## ✅ Критерии прохождения
- Все Core Web Vitals в зеленой зоне
- Нет memory leaks
- 60 FPS для анимаций
- Bundle size оптимизирован
- Mobile performance acceptable

## 🚨 Текущие проблемы производительности
1. **Deep watchers** в AdForm без необходимости
2. **1100+ строк** в одном компоненте
3. **Отсутствие мемоизации** computed свойств
4. **Прямые DOM манипуляции** вместо Vue

## 📊 Инструменты мониторинга
- Chrome DevTools Performance
- Lighthouse CLI
- Bundle Analyzer
- Vue DevTools Profiler
- Memory Profiler

## 📝 Рекомендации по оптимизации
1. Разбить AdForm на lazy-loaded chunks
2. Использовать virtual scrolling для длинных списков
3. Оптимизировать изображения (WebP, lazy loading)
4. Добавить service worker для кеширования
5. Использовать Web Workers для тяжелых вычислений