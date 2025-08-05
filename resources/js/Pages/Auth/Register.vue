<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import RegisterModal from '@/Components/Auth/RegisterModal.vue'

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

const showModal = ref<boolean>(true)

const submit = (): void => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}

const closeModal = (): void => {
  showModal.value = false
  // Перенаправляем на главную страницу
  window.location.href = '/'
}
</script>

<template>
    <Head title="Регистрация" />

    <!-- Модальное окно регистрации -->
    <RegisterModal 
        :show="showModal" 
        @close="closeModal"
    />
</template>
