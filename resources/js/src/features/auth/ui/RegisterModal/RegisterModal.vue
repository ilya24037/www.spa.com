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
          Регистрация
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

      <!-- Форма регистрации -->
      <div class="p-6">
        <form class="space-y-6" @submit.prevent="submit">
          <!-- Имя -->
          <BaseInput
            v-model="form.name"
            type="text"
            label="Имя"
            placeholder="Введите ваше имя"
            autocomplete="name"
            required
            :error="form.errors.name"
          />

          <!-- Email -->
          <BaseInput
            v-model="form.email"
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
            type="password"
            label="Пароль"
            placeholder="Создайте пароль"
            autocomplete="new-password"
            required
            :error="form.errors.password"
          />

          <!-- Подтверждение пароля -->
          <BaseInput
            v-model="form.password_confirmation"
            type="password"
            label="Подтвердите пароль"
            placeholder="Повторите пароль"
            autocomplete="new-password"
            required
            :error="form.errors.password_confirmation"
          />

          <!-- Кнопка регистрации -->
          <PrimaryButton
            type="submit"
            :disabled="form.processing"
            :loading="form.processing"
            class="w-full"
          >
            {{ form.processing ? 'Регистрация...' : 'Зарегистрироваться' }}
          </PrimaryButton>
        </form>

        <!-- Ссылка на вход -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-500">
            Уже есть аккаунт? 
            <Link 
              :href="route('login')" 
              class="font-medium text-blue-600 hover:text-blue-500"
            >
              Войти
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
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
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

interface RegisterForm {
  name: string
  email: string
  password: string
  password_confirmation: string
}

const form = useForm<RegisterForm>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = (): void => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onSuccess: () => {
            closeModal()
        }
    })
}

const closeModal = (): void => {
    emit('close')
}
</script>

