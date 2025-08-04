<template>
    <div v-show="message" class="mt-1">
        <p class="text-sm text-red-600">{{ message }}</p>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: '',
    },
});
</script>