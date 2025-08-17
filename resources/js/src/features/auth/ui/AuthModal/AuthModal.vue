<template>
  <!-- Затемнение фона -->
  <div 
    v-if="props.show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="closeModal"
  >
    <!-- Модальное окно -->
    <div 
      class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4"
      @click.stop
    >
      <!-- Заголовок с кнопкой закрытия -->
      <div class="flex items-center justify-between p-6 border-b border-gray-500">
        <h2 class="text-2xl font-bold text-gray-500">
          Вход
        </h2>
        <button 
          class="text-gray-500 hover:text-gray-500 transition-colors"
          @click="closeModal"
        >
          <svg
            class="w-6 h-6"
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

      <!-- Форма авторизации -->
      <div class="p-6">
        <form class="space-y-6" @submit.prevent="submit">
          <!-- Email -->
          <BaseInput
            v-model="form.email"
            name="email"
            type="email"
            label="Email"
            placeholder="Введите ваш email"
            autocomplete="username"
            required
            :error="form.errors.email"
          />

          <!-- Пароль -->
          <BaseInput
            v-model="form.password"
            name="password"
            type="password"
            label="Пароль"
            placeholder="Введите пароль"
            autocomplete="current-password"
            required
            :error="form.errors.password"
          />

          <!-- Запомнить пароль и забыли пароль -->
          <div class="flex items-center justify-between">
            <BaseCheckbox
              v-model="form.remember"
              name="remember"
              label="Запомнить пароль"
            />
            <Link
              v-if="route().has('password.request')"
              :href="route('password.request')"
              class="text-sm text-blue-600 hover:text-blue-500"
            >
              Забыли пароль?
            </Link>
          </div>

          <!-- Кнопка входа -->
          <PrimaryButton
            type="submit"
            :disabled="form.processing"
            :loading="form.processing"
            class="w-full"
          >
            {{ form.processing ? 'Вход...' : 'Войти' }}
          </PrimaryButton>
        </form>

        <!-- Ссылка на регистрацию -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-500">
            Нет аккаунта? 
            <Link 
              :href="route('register')" 
              class="font-medium text-blue-600 hover:text-blue-500"
            >
              Зарегистрироваться
            </Link>
          </p>
        </div>

        <!-- Юридическая информация -->
        <p class="mt-4 text-xs text-gray-500 text-center">
          При регистрации и входе вы соглашаетесь с 
          <a href="/terms" class="text-blue-600 hover:text-blue-500">условиями использования</a> 
          и 
          <a href="/privacy" class="text-blue-600 hover:text-blue-500">политикой конфиденциальности</a>.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { onMounted } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'

interface Props {
  show?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    show: false
})

interface Emits {
  close: []
}

const emit = defineEmits<Emits>()

interface LoginForm {
  email: string
  password: string
  remember: boolean
}

const form = useForm<LoginForm>({
    email: '',
    password: '',
    remember: false,
})

// Получаем CSRF токен при монтировании компонента
onMounted(async () => {
    if (window.axios) {
        try {
            await window.axios.get('/sanctum/csrf-cookie')
        } catch (error) {
            console.error('Failed to get CSRF token:', error)
        }
    }
})

const submit = async (): Promise<void> => {
    // Получаем CSRF токен перед отправкой формы
    if (window.axios) {
        try {
            await window.axios.get('/sanctum/csrf-cookie')
        } catch (error) {
            console.error('Failed to get CSRF token:', error)
        }
    }
    
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            closeModal()
        }
    })
}

const closeModal = (): void => {
    emit('close')
}
</script>

