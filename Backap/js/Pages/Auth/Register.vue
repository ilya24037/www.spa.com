<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import RegisterModal from '@/Components/Auth/RegisterModal.vue'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showModal = ref(true)

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const closeModal = () => {
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
