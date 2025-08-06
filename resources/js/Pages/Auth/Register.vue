<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { route as _route } from 'ziggy-js'
import { RegisterModal } from '@/src/features/auth/ui/RegisterModal'

// interface RegisterForm {
//   name: string
//   email: string
//   password: string
//   password_confirmation: string
// }

// Form не используется, но может потребоваться для регистрации
// const form = useForm<RegisterForm>({
//   name: '',
//   email: '',
//   password: '',
//   password_confirmation: '',
// })

const showModal = ref<boolean>(true)

// const submit = (): void => {
//   form.post(route('register'), {
//     onFinish: () => form.reset('password', 'password_confirmation'),
//   })
// }

const closeModal = (): void => {
  showModal.value = false
  // РџРµСЂРµРЅР°РїСЂР°РІР»СЏРµРј РЅР° РіР»Р°РІРЅСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
  (window as any).(window as any).location.href = '/'
}
</script>

<template>
    <Head title="Р РµРіРёСЃС‚СЂР°С†РёСЏ" />

    <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ СЂРµРіРёСЃС‚СЂР°С†РёРё -->
    <RegisterModal 
        :show="showModal" 
        @close="closeModal"
    />
</template>
