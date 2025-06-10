<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const statsData = computed(() => [
    {
        name: 'Всего проектов',
        value: props.stats.total || 0,
        icon: 'folder',
        color: 'bg-gray-500',
        bgColor: 'bg-gray-100',
    },
    {
        name: 'Активные',
        value: props.stats.active || 0,
        icon: 'play',
        color: 'bg-green-500',
        bgColor: 'bg-green-100',
    },
    {
        name: 'Завершенные',
        value: props.stats.completed || 0,
        icon: 'check-circle',
        color: 'bg-blue-500',
        bgColor: 'bg-blue-100',
    },
    {
        name: 'Приостановлены',
        value: props.stats.on_hold || 0,
        icon: 'pause',
        color: 'bg-yellow-500',
        bgColor: 'bg-yellow-100',
    },
]);

const getIcon = (type) => {
    const icons = {
        folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        play: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        pause: 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return icons[type] || icons.folder;
};
</script>

<template>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div
            v-for="stat in statsData"
            :key="stat.name"
            class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6"
        >
            <dt>
                <div :class="[stat.bgColor, 'absolute rounded-md p-3']">
                    <svg
                        class="h-6 w-6 text-white"
                        :class="stat.color.replace('bg-', 'text-').replace('500', '600')"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="getIcon(stat.icon)"
                        />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">
                    {{ stat.name }}
                </p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">
                    {{ stat.value }}
                </p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <div class="h-1 w-full bg-gray-200 rounded">
                            <div
                                :class="stat.color"
                                class="h-1 rounded transition-all duration-500"
                                :style="`width: ${props.stats.total > 0 ? (stat.value / props.stats.total) * 100 : 0}%`"
                            />
                        </div>
                        <span class="text-gray-600 text-xs mt-1 block">
                            {{ props.stats.total > 0 ? Math.round((stat.value / props.stats.total) * 100) : 0 }}% от общего
                        </span>
                    </div>
                </div>
            </dd>
        </div>
    </div>
</template>
