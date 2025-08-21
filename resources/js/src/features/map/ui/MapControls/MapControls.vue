<!-- resources/js/src/features/map/ui/UniversalMap/MapControls.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Р—СѓРј + -->
    <button
      :disabled="!canZoomIn"
      :class="getControlButtonClasses()"
      title="РџСЂРёР±Р»РёР·РёС‚СЊ"
      @click="$emit('zoom-in')"
    >
      <svg
        :class="ICON_CLASSES"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 6v6m0 0v6m0-6h6m-6 0H6"
        />
      </svg>
    </button>

    <!-- Р—СѓРј - -->
    <button
      :disabled="!canZoomOut"
      :class="getControlButtonClasses()"
      title="РћС‚РґР°Р»РёС‚СЊ"
      @click="$emit('zoom-out')"
    >
      <svg
        :class="ICON_CLASSES"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M18 12H6"
        />
      </svg>
    </button>

    <!-- РњРѕСЏ Р»РѕРєР°С†РёСЏ -->
    <button
      :class="getControlButtonClasses()"
      title="РњРѕСЏ Р»РѕРєР°С†РёСЏ"
      @click="$emit('my-location')"
    >
      <svg
        :class="ICON_CLASSES"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
    </button>

    <!-- РџРѕР»РЅС‹Р№ СЌРєСЂР°РЅ -->
    <button
      :class="getControlButtonClasses()"
      title="РџРѕР»РЅС‹Р№ СЌРєСЂР°РЅ"
      @click="$emit('fullscreen')"
    >
      <svg
        :class="ICON_CLASSES"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
        />
      </svg>
    </button>

            <!-- Показатель зума -->
    <div :class="ZOOM_INDICATOR_CLASSES">
      {{ zoom }}x
    </div>
  </div>
</template>

<script setup>
// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'absolute top-4 right-4 flex flex-col gap-2 z-20'
const CONTROL_BUTTON_BASE_CLASSES = 'bg-white p-2 rounded shadow hover:bg-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed'
const ICON_CLASSES = 'w-4 h-4'
const ZOOM_INDICATOR_CLASSES = 'bg-white px-2 py-1 rounded shadow text-xs font-medium text-gray-500 text-center'

const props = defineProps({
    zoom: {
        type: Number,
        default: 10
    },
    canZoomIn: {
        type: Boolean,
        default: true
    },
    canZoomOut: {
        type: Boolean,
        default: true
    }
})

defineEmits(['zoom-in', 'zoom-out', 'my-location', 'fullscreen'])

// РњРµС‚РѕРґС‹
const getControlButtonClasses = () => {
    return CONTROL_BUTTON_BASE_CLASSES
}
</script>
