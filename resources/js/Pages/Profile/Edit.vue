<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  mustVerifyEmail: Boolean,
  status: String,
})
</script>

<template>
  <Head title="Profile" />

  <!-- Обертка с правильными отступами как на главной -->
  <div class="py-6 lg:py-8">
    <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
      <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
        <UpdateProfileInformationForm
          :must-verify-email="mustVerifyEmail"
          :status="status"
          class="max-w-xl"
        />
      </div>

      <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
        <UpdatePasswordForm class="max-w-xl" />
      </div>

      <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
        <DeleteUserForm class="max-w-xl" />
      </div>
    </div>
  </div>
</template>
