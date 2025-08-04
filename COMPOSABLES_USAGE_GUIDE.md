# 📚 РУКОВОДСТВО ПО ИСПОЛЬЗОВАНИЮ COMPOSABLES

## Быстрый старт

```typescript
// В любом Vue компоненте
import { useToast, useModal, useLoadingState } from '@/src/shared/composables'
```

## 🔄 ЗАМЕНА alert() НА КРАСИВЫЕ УВЕДОМЛЕНИЯ

### ❌ Было (плохо)
```javascript
alert('Телефон будет доступен после записи')
```

### ✅ Стало (хорошо)
```vue
<script setup lang="ts">
import { useToast, useModal } from '@/src/shared/composables'

const toast = useToast()
const modal = useModal()

// Простое уведомление
const showPhone = () => {
  toast.info('Телефон будет доступен после записи')
}

// Или модальное окно
const showPhoneModal = () => {
  modal.info('Телефон будет доступен после записи к мастеру')
}

// Подтверждение удаления
const deleteItem = async () => {
  const confirmed = await modal.confirm('Удалить объявление?')
  if (confirmed) {
    // Удаляем
    toast.success('Объявление удалено')
  }
}
</script>
```

## 📋 ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### 1. useLoadingState - Управление загрузкой
```vue
<template>
  <div>
    <!-- Состояние загрузки -->
    <div v-if="isLoading" class="skeleton-loader">
      Загрузка...
    </div>
    
    <!-- Состояние ошибки -->
    <div v-else-if="error" class="error-state">
      <p>{{ error.message }}</p>
      <button @click="retry">Повторить</button>
    </div>
    
    <!-- Данные -->
    <div v-else-if="data">
      <MasterCard v-for="master in data" :key="master.id" :master="master" />
    </div>
    
    <!-- Пустое состояние -->
    <div v-else class="empty-state">
      Ничего не найдено
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useLoadingState } from '@/src/shared/composables'
import { api } from '@/src/shared/api'

const { isLoading, error, data, execute } = useLoadingState<Master[]>()

const fetchMasters = () => {
  execute(() => api.masters.getAll())
}

const retry = () => {
  fetchMasters()
}

onMounted(() => {
  fetchMasters()
})
</script>
```

### 2. useForm - Управление формами
```vue
<template>
  <form @submit.prevent="form.submit">
    <!-- Email поле -->
    <div>
      <input 
        v-model="form.values.email"
        @blur="form.touch('email')"
        type="email"
        placeholder="Email"
      />
      <span v-if="form.errors.email" class="error">
        {{ form.errors.email }}
      </span>
    </div>
    
    <!-- Password поле -->
    <div>
      <input 
        v-model="form.values.password"
        @blur="form.touch('password')"
        type="password"
        placeholder="Пароль"
      />
      <span v-if="form.errors.password" class="error">
        {{ form.errors.password }}
      </span>
    </div>
    
    <!-- Кнопка отправки -->
    <button 
      type="submit" 
      :disabled="form.loading || form.hasErrors"
    >
      {{ form.loading ? 'Отправка...' : 'Войти' }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { useForm, useToast } from '@/src/shared/composables'
import { api } from '@/src/shared/api'
import { router } from '@inertiajs/vue3'

const toast = useToast()

const form = useForm({
  initialValues: {
    email: '',
    password: ''
  },
  validate: (values) => {
    const errors: Record<string, string> = {}
    
    if (!values.email) {
      errors.email = 'Email обязателен'
    } else if (!/\S+@\S+\.\S+/.test(values.email)) {
      errors.email = 'Неверный формат email'
    }
    
    if (!values.password) {
      errors.password = 'Пароль обязателен'
    } else if (values.password.length < 6) {
      errors.password = 'Минимум 6 символов'
    }
    
    return Object.keys(errors).length ? errors : null
  },
  onSubmit: async (values) => {
    await api.auth.login(values)
    toast.success('Вы успешно вошли!')
    router.visit('/dashboard')
  }
})
</script>
```

### 3. useAsyncAction - Асинхронные действия
```vue
<template>
  <button 
    @click="addToFavorites"
    :disabled="loading"
    class="favorite-btn"
  >
    <HeartIcon :filled="isFavorite" />
    {{ loading ? 'Сохранение...' : 'В избранное' }}
  </button>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAsyncAction } from '@/src/shared/composables'
import { api } from '@/src/shared/api'

const props = defineProps<{
  masterId: number
}>()

const isFavorite = ref(false)

const { execute, loading } = useAsyncAction()

const addToFavorites = () => {
  execute(
    () => api.favorites.toggle(props.masterId),
    {
      successMessage: isFavorite.value 
        ? 'Удалено из избранного' 
        : 'Добавлено в избранное',
      onSuccess: () => {
        isFavorite.value = !isFavorite.value
      }
    }
  )
}
</script>
```

### 4. usePagination - Пагинация
```vue
<template>
  <div>
    <!-- Список -->
    <div class="grid grid-cols-3 gap-4">
      <MasterCard 
        v-for="master in masters" 
        :key="master.id" 
        :master="master" 
      />
    </div>
    
    <!-- Пагинация -->
    <div class="pagination">
      <button 
        @click="pagination.prev()" 
        :disabled="!pagination.hasPrev"
      >
        Назад
      </button>
      
      <button 
        v-for="page in pagination.pageNumbers" 
        :key="page"
        @click="pagination.goToPage(page)"
        :class="{ active: page === pagination.currentPage }"
      >
        {{ page }}
      </button>
      
      <button 
        @click="pagination.next()" 
        :disabled="!pagination.hasNext"
      >
        Вперёд
      </button>
      
      <span>
        Показано {{ pagination.from }}-{{ pagination.to }} из {{ pagination.total }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePagination } from '@/src/shared/composables'

const props = defineProps<{
  masters: {
    data: Master[]
    current_page: number
    total: number
    per_page: number
  }
}>()

const pagination = usePagination({
  page: props.masters.current_page,
  total: props.masters.total,
  perPage: props.masters.per_page
})
</script>
```

### 5. useDebounce - Отложенный поиск
```vue
<template>
  <div>
    <input 
      v-model="searchQuery"
      placeholder="Поиск мастеров..."
    />
    
    <div v-if="searching">Поиск...</div>
    
    <div v-else>
      <MasterCard 
        v-for="master in results" 
        :key="master.id" 
        :master="master" 
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useDebounce } from '@/src/shared/composables'
import { api } from '@/src/shared/api'

const searchQuery = ref('')
const debouncedQuery = useDebounce(searchQuery, 500)
const searching = ref(false)
const results = ref<Master[]>([])

// Поиск выполнится через 500ms после последнего ввода
watch(debouncedQuery, async (query) => {
  if (!query) {
    results.value = []
    return
  }
  
  searching.value = true
  try {
    results.value = await api.masters.search(query)
  } finally {
    searching.value = false
  }
})
</script>
```

### 6. useLocalStorage - Сохранение настроек
```vue
<template>
  <div>
    <!-- Переключатель темы -->
    <button @click="toggleTheme">
      {{ theme === 'dark' ? '🌙' : '☀️' }}
    </button>
    
    <!-- Избранные мастера -->
    <div>
      <h3>Избранное ({{ favorites.length }})</h3>
      <button @click="addToFavorites(123)">
        Добавить в избранное
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useLocalStorage } from '@/src/shared/composables'

// Автоматически сохраняется в localStorage
const theme = useLocalStorage('theme', 'light')
const favorites = useLocalStorage<number[]>('favorites', [])

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  // Автоматически сохранится!
}

const addToFavorites = (id: number) => {
  if (!favorites.value.includes(id)) {
    favorites.value.push(id)
    // Автоматически сохранится!
  }
}
</script>
```

## 🎯 ПЛАН МИГРАЦИИ

### Шаг 1: Замена alert()
```javascript
// Найти все:
alert('...')

// Заменить на:
const toast = useToast()
toast.info('...')
```

### Шаг 2: Добавление loading состояний
```vue
// Было:
<template>
  <div>
    <MasterCard v-for="master in masters" />
  </div>
</template>

// Стало:
<template>
  <div>
    <div v-if="isLoading">Загрузка...</div>
    <div v-else-if="error">Ошибка: {{ error.message }}</div>
    <div v-else-if="!data?.length">Ничего не найдено</div>
    <MasterCard v-else v-for="master in data" />
  </div>
</template>
```

### Шаг 3: Типизация props
```typescript
// Было:
const props = defineProps({
  master: Object
})

// Стало:
interface Props {
  master: Master
}
const props = defineProps<Props>()
```

## 📦 УСТАНОВКА В КОМПОНЕНТ

### Минимальный пример с TypeScript
```vue
<template>
  <div>
    <button @click="handleClick" :disabled="loading">
      {{ loading ? 'Загрузка...' : 'Нажми меня' }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { useToast, useAsyncAction } from '@/src/shared/composables'

const toast = useToast()
const { execute, loading } = useAsyncAction()

const handleClick = () => {
  execute(
    async () => {
      // Ваш API вызов
      await new Promise(resolve => setTimeout(resolve, 1000))
      return { success: true }
    },
    {
      successMessage: 'Успешно выполнено!',
      errorMessage: 'Произошла ошибка'
    }
  )
}
</script>
```

## ✅ ПРЕИМУЩЕСТВА

1. **Убрали alert()** - красивые уведомления
2. **Типизация** - автодополнение и проверка типов
3. **Переиспользование** - один код для всех компонентов
4. **Обработка ошибок** - централизованно
5. **Loading состояния** - автоматически
6. **Валидация форм** - встроенная
7. **Сохранение настроек** - в localStorage

---

Готово к использованию во всех компонентах!