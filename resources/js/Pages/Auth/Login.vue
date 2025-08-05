<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthModal from '@/Components/Auth/AuthModal.vue'

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

const showModal = ref<boolean>(true)

const submit = (): void => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}

const closeModal = (): void => {
  showModal.value = false
  // Перенаправляем на главную страницу
  window.location.href = '/'
}
</script>

<template>
    <Head title="Вход" />

    <!-- Модальное окно авторизации -->
    <AuthModal 
        :show="showModal" 
        @close="closeModal"
    />
</template>
