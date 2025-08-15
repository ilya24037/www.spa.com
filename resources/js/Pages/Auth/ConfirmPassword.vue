<script setup lang="ts">
import { route } from 'ziggy-js'

import { InputError, InputLabel, PrimaryButton, TextInput } from '@/src/shared/ui/atoms'
import { Head, useForm } from '@inertiajs/vue3';

interface ConfirmPasswordForm {
  password: string
}

const form = useForm<ConfirmPasswordForm>({
    password: '',
})

const submit = (): void => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
  <div>
    <Head title="Confirm Password" />

    <div class="mb-4 text-sm text-gray-500">
      This is a secure area of the application. Please confirm your
      password before continuing.
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="confirm-password" value="Password" />
        <TextInput
          id="confirm-password"
          v-model="form.password"
          type="password"
          class="mt-1 block w-full"
          required
          autocomplete="current-password"
          autofocus
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="mt-4 flex justify-end">
        <PrimaryButton
          class="ms-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Confirm
        </PrimaryButton>
      </div>
    </form>
  </div>
</template>


