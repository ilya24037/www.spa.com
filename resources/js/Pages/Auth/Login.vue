<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { route as _route } from 'ziggy-js'
import { AuthModal } from '@/src/features/auth/ui/AuthModal'

interface LoginForm {
  email: string
  password: string
  remember: boolean
}

const _form = useForm<LoginForm>({
  email: '',
  password: '',
  remember: false,
})

const showModal = ref<boolean>(true)

// const submit = (): void => {
//   form.post(route('login'), {
//     onFinish: () => form.reset('password'),
//   })
// }

const closeModal = (): void => {
  showModal.value = false
  // РџРµСЂРµРЅР°РїСЂР°РІР»СЏРµРј РЅР° РіР»Р°РІРЅСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
  window.location.href = '/'
}
</script>

<template>
    <Head title="Р’С…РѕРґ" />

    <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ Р°РІС‚РѕСЂРёР·Р°С†РёРё -->
    <AuthModal 
        :show="showModal" 
        @close="closeModal"
    />
</template>
