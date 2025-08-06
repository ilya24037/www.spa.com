<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

// TypeScript интерфейс для props
interface DropdownProps {
  align?: 'left' | 'right' | 'center'
  width?: string
  contentClasses?: string
  open?: boolean
}

const props = withDefaults(defineProps<DropdownProps>(), {
  align: 'right',
  width: '48',
  contentClasses: 'py-1 bg-white',
  open: false
});

// Reactive состояние
const open = ref<boolean>(false);

// Обработчик закрытия по Escape
const closeOnEscape = (e: KeyboardEvent): void => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

// Lifecycle hooks
onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

// Computed свойства с типизацией
const widthClass = computed((): string => {
    const widthMap: Record<string, string> = {
        48: 'w-48',
    };
    return widthMap[props.width] || 'w-48';
});

const alignmentClasses = computed((): string => {
    switch (props.align) {
        case 'left':
            return 'ltr:origin-top-left rtl:origin-top-right start-0';
        case 'right':
            return 'ltr:origin-top-right rtl:origin-top-left end-0';
        case 'center':
        default:
            return 'origin-top';
    }
});
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
                @click="open = false"
            >
                <div
                    class="rounded-md ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
