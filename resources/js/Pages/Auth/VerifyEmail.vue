<script setup lang="ts">
import { computed } from 'vue'
import { PrimaryButton } from '@/src/shared/ui/atoms';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface VerifyEmailProps {
  status?: string
}

const props = defineProps<VerifyEmailProps>()

const form = useForm({})

const submit = (): void => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed((): boolean => 
    props.status === 'verification-link-sent'
)
</script>

<template>
  <div>
    <Head title="Email Verification" />

    <div class="mb-4 text-sm text-gray-500">
      Thanks for signing up! Before getting started, could you verify your
      email address by clicking on the link we just emailed to you? If you
      didn't receive the email, we will gladly send you another.
    </div>

    <div
      v-if="verificationLinkSent"
      class="mb-4 text-sm font-medium text-green-600"
    >
      A new verification link has been sent to the email address you
      provided during registration.
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <PrimaryButton
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Resend Verification Email
        </PrimaryButton>

        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="rounded-md text-sm text-gray-500 underline hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
          Log Out
        </Link>
      </div>
    </form>
  </div>
</template>



