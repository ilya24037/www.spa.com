# 📖 Руководство по доступности (Web Accessibility)

## 🎯 Что такое веб-доступность?

Веб-доступность (Web Accessibility, a11y) — это практика создания веб-сайтов и приложений, которыми могут пользоваться все люди, включая людей с ограниченными возможностями:

- **Нарушения зрения** — слепота, слабое зрение, дальтонизм
- **Нарушения слуха** — глухота, тугоухость  
- **Двигательные нарушения** — невозможность использовать мышь, тремор рук
- **Когнитивные нарушения** — дислексия, СДВГ, аутизм

## 🤔 Зачем это нужно?

1. **Социальная ответственность** — равный доступ к информации для всех
2. **Юридические требования** — во многих странах есть законы о доступности
3. **Расширение аудитории** — 15% населения имеют ограничения
4. **Улучшение UX** — доступные сайты удобнее для всех
5. **SEO преимущества** — поисковики лучше индексируют доступные сайты

## 🔧 Автоматическая доступность в проекте

### Базовые компоненты с встроенной доступностью

#### BaseInput
```vue
<BaseInput
  v-model="email"
  name="email"
  label="Email адрес"
  type="email"
  required
/>
```
✅ Автоматически добавляет:
- Уникальный `id` 
- Атрибут `name` (генерируется из label если не указан)
- ARIA атрибуты (`aria-invalid`, `aria-describedby`)
- Связь с label

#### BaseCheckbox
```vue
<BaseCheckbox
  v-model="agreed"
  name="terms"
  label="Я согласен с условиями"
/>
```
✅ Автоматически добавляет:
- Уникальный `id`
- Атрибут `name` (генерируется из label)
- `aria-checked` состояние
- Связь с label

#### BaseRadio
```vue
<BaseRadio
  v-model="selectedOption"
  name="delivery"
  value="courier"
  label="Доставка курьером"
/>
```
✅ Автоматически добавляет:
- Уникальный `id`
- Группировку через `name`
- `aria-checked` состояние
- Связь с label

#### FormField (обертка)
```vue
<FormField
  label="Ваше имя"
  :error="errors.name"
  required
>
  <template #default="{ id, name, ariaInvalid, ariaDescribedBy }">
    <BaseInput
      v-model="form.name"
      :id="id"
      :name="name"
      :aria-invalid="ariaInvalid"
      :aria-describedby="ariaDescribedBy"
    />
  </template>
</FormField>
```
✅ Автоматически управляет:
- Связью label и поля
- Отображением ошибок
- ARIA атрибутами
- Обязательными полями

## 📋 Чек-лист доступности

### Формы
- [ ] Все поля имеют `label` или `aria-label`
- [ ] Используются семантические типы (`type="email"`, `type="tel"`)
- [ ] Ошибки связаны с полями через `aria-describedby`
- [ ] Обязательные поля помечены `required` и визуально
- [ ] Группы полей обернуты в `fieldset` с `legend`

### Навигация
- [ ] Можно использовать только клавиатуру (Tab, Enter, Escape)
- [ ] Фокус виден и логично перемещается
- [ ] Ссылки имеют понятный текст (не "Читать далее")
- [ ] Текущая страница помечена `aria-current="page"`

### Изображения и медиа
- [ ] Все изображения имеют `alt` текст
- [ ] Декоративные изображения имеют пустой `alt=""`
- [ ] Видео имеют субтитры
- [ ] Аудио имеет транскрипцию

### Интерактивные элементы
- [ ] Кнопки имеют понятный текст или `aria-label`
- [ ] Модальные окна имеют `role="dialog"` и `aria-modal="true"`
- [ ] Уведомления используют `role="alert"` или `aria-live`
- [ ] Состояния загрузки имеют `role="status"`

## 🛠️ Инструменты для тестирования

### Браузерные расширения
- **axe DevTools** — автоматическое сканирование
- **WAVE** — визуальный анализ доступности
- **Lighthouse** — встроен в Chrome DevTools

### Скринридеры для тестирования
- **NVDA** (Windows) — бесплатный
- **JAWS** (Windows) — платный, самый популярный
- **VoiceOver** (macOS/iOS) — встроенный
- **TalkBack** (Android) — встроенный

### ESLint правила
Проект настроен с `eslint-plugin-vuejs-accessibility`:
```bash
npm run lint        # Исправить автоматически
npm run lint:check  # Только проверка
```

## 🎨 CSS классы для доступности

### Скрытие элементов только визуально
```css
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
```
Используйте для скрытых label и вспомогательного текста:
```vue
<label for="search" class="sr-only">Поиск</label>
<input id="search" type="search" placeholder="Поиск...">
```

### Индикатор фокуса
```css
:focus-visible {
  outline: 2px solid #2196f3;
  outline-offset: 2px;
}
```

## 📚 Примеры использования

### Доступная форма
```vue
<template>
  <form @submit.prevent="handleSubmit" novalidate>
    <FormField
      label="Email"
      :error="errors.email"
      required
    >
      <template #default="slotProps">
        <BaseInput
          v-model="form.email"
          v-bind="slotProps"
          type="email"
          placeholder="example@mail.com"
        />
      </template>
    </FormField>

    <FormField
      label="Пароль"
      :error="errors.password"
      hint="Минимум 8 символов"
      required
    >
      <template #default="slotProps">
        <BaseInput
          v-model="form.password"
          v-bind="slotProps"
          type="password"
        />
      </template>
    </FormField>

    <BaseCheckbox
      v-model="form.remember"
      name="remember"
      label="Запомнить меня"
    />

    <button
      type="submit"
      :disabled="isSubmitting"
      :aria-busy="isSubmitting"
    >
      {{ isSubmitting ? 'Вход...' : 'Войти' }}
    </button>
  </form>
</template>
```

### Доступное модальное окно
```vue
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="modal-overlay"
        @click.self="close"
      >
        <div
          role="dialog"
          :aria-modal="true"
          :aria-labelledby="`${id}-title`"
          class="modal"
          @keydown.escape="close"
        >
          <h2 :id="`${id}-title`">{{ title }}</h2>
          
          <button
            type="button"
            @click="close"
            aria-label="Закрыть диалог"
          >
            ✕
          </button>
          
          <div class="modal-content">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
```

### Доступная таблица
```vue
<template>
  <table>
    <caption>Список мастеров</caption>
    <thead>
      <tr>
        <th scope="col">Имя</th>
        <th scope="col">Специализация</th>
        <th scope="col">Рейтинг</th>
        <th scope="col">Действия</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="master in masters" :key="master.id">
        <td>{{ master.name }}</td>
        <td>{{ master.specialization }}</td>
        <td>
          <span :aria-label="`Рейтинг ${master.rating} из 5`">
            ⭐ {{ master.rating }}
          </span>
        </td>
        <td>
          <button
            type="button"
            :aria-label="`Просмотреть профиль ${master.name}`"
          >
            Подробнее
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</template>
```

## 🚀 VS Code сниппеты

Используйте готовые сниппеты для быстрого создания доступных компонентов:

- `vfield` — FormField обертка
- `vinput` — Доступный input
- `vcheckbox` — Доступный checkbox
- `vradio` — Группа radio кнопок
- `vform` — Доступная форма
- `vmodal` — Модальное окно
- `vtable` — Доступная таблица
- `vloading` — Индикатор загрузки
- `verror` — Сообщение об ошибке
- `vsr` — Текст для скринридера

## 📖 Полезные ресурсы

- [WCAG 2.1 Руководство](https://www.w3.org/WAI/WCAG21/quickref/) — стандарт доступности
- [MDN Accessibility](https://developer.mozilla.org/ru/docs/Web/Accessibility) — документация
- [A11y Project](https://www.a11yproject.com/) — чек-листы и паттерны
- [WebAIM](https://webaim.org/) — инструменты и обучение
- [Inclusive Components](https://inclusive-components.design/) — примеры компонентов

## ❓ Частые вопросы

**Q: Это усложнит проект?**
A: Нет, базовые компоненты уже настроены. Просто используйте их вместо обычных HTML элементов.

**Q: Как проверить доступность?**
A: Запустите `npm run lint` и используйте расширение axe DevTools в браузере.

**Q: Обязательно ли это делать?**
A: Да, это стандарт индустрии и требование во многих странах.

**Q: Влияет ли на производительность?**
A: Нет, дополнительные атрибуты не влияют на производительность.

## 🎯 Цель

Сделать приложение доступным для всех пользователей автоматически, без дополнительных усилий со стороны разработчиков.