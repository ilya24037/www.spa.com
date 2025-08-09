<template>
  <!-- Затемнение фона -->
  <div 
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="closeModal"
  >
    <!-- Модальное окно -->
    <div 
      class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4"
      @click.stop
    >
      <!-- Заголовок с кнопкой закрытия -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">Регистрация</h2>
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Форма регистрации -->
      <div class="p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Имя -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Имя
            </label>
            <input
              type="text"
              v-model="form.name"
              required
              autocomplete="name"
              placeholder="Введите ваше имя"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
              {{ form.errors.name }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email
            </label>
            <input
              type="email"
              v-model="form.email"
              required
              autocomplete="username"
              placeholder="Введите ваш email"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :class="{ 'border-red-500': form.errors.email }"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Пароль -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Пароль
            </label>
            <input
              type="password"
              v-model="form.password"
              required
              autocomplete="new-password"
              placeholder="Создайте пароль"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :class="{ 'border-red-500': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Подтверждение пароля -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Подтвердите пароль
            </label>
            <input
              type="password"
              v-model="form.password_confirmation"
              required
              autocomplete="new-password"
              placeholder="Повторите пароль"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :class="{ 'border-red-500': form.errors.password_confirmation }"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <!-- Кнопка регистрации -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="form.processing">Регистрация...</span>
            <span v-else>Зарегистрироваться</span>
          </button>
        </form>

        <!-- Ссылка на вход -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
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

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
    onSuccess: () => {
      closeModal()
    }
  })
}

const closeModal = () => {
  emit('close')
}
</script> 