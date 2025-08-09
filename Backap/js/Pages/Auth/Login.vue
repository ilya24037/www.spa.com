<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthModal from '@/Components/Auth/AuthModal.vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showModal = ref(true)

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const closeModal = () => {
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
