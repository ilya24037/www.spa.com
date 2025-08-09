<template>
  <div 
    :class="skeletonClasses"
    :style="customStyles"
    :aria-busy="true"
    :aria-label="ariaLabel"
    role="status"
  >
    <!-- Shimmer Р°РЅРёРјР°С†РёСЏ -->
    <div v-if="animated" class="skeleton-shimmer" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { SkeletonProps } from './Skeleton.types'

const props = withDefaults(defineProps<SkeletonProps>(), {
    variant: 'text',
    size: 'medium',
    animated: true,
    rounded: false
})

const skeletonClasses = computed(() => [
    'skeleton',
    `skeleton--${props.variant}`,
    `skeleton--${props.size}`,
    {
        'skeleton--animated': props.animated,
        'skeleton--rounded': props.rounded,
        'skeleton--circular': props.variant === 'circular'
    },
    props.customClass
])

const customStyles = computed(() => {
    const styles: Record<string, string> = {}
  
    if (props.width) {
        styles.width = typeof props.width === 'number' ? `${props.width}px` : props.width
    }
  
    if (props.height) {
        styles.height = typeof props.height === 'number' ? `${props.height}px` : props.height
    }
  
    return styles
})

const ariaLabel = computed(() => 
    props.ariaLabel || `Р—Р°РіСЂСѓР·РєР° ${props.variant === 'text' ? 'С‚РµРєСЃС‚Р°' : 'РєРѕРЅС‚РµРЅС‚Р°'}...`
)
</script>

<style scoped>
.skeleton {
  background: #f0f0f0;
  position: relative;
  overflow: hidden;
  display: block;
}

/* Р’Р°СЂРёР°РЅС‚С‹ СЃРєРµР»РµС‚РѕРЅРѕРІ */
.skeleton--text {
  height: 1em;
  margin-bottom: 0.5em;
  border-radius: 4px;
}

.skeleton--heading {
  height: 1.5em;
  margin-bottom: 0.75em;
  border-radius: 4px;
}

.skeleton--paragraph {
  height: 1em;
  margin-bottom: 0.5em;
  border-radius: 4px;
}

.skeleton--button {
  height: 40px;
  width: 120px;
  border-radius: 6px;
}

.skeleton--avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.skeleton--image {
  width: 100%;
  height: 200px;
  border-radius: 8px;
}

.skeleton--card {
  width: 100%;
  height: 300px;
  border-radius: 8px;
}

.skeleton--circular {
  border-radius: 50%;
}

.skeleton--rounded {
  border-radius: 12px;
}

/* Р Р°Р·РјРµСЂС‹ */
.skeleton--small.skeleton--text {
  height: 0.75em;
}

.skeleton--small.skeleton--heading {
  height: 1.25em;
}

.skeleton--small.skeleton--button {
  height: 32px;
  width: 100px;
}

.skeleton--small.skeleton--avatar {
  width: 32px;
  height: 32px;
}

.skeleton--large.skeleton--text {
  height: 1.25em;
}

.skeleton--large.skeleton--heading {
  height: 2em;
}

.skeleton--large.skeleton--button {
  height: 48px;
  width: 140px;
}

.skeleton--large.skeleton--avatar {
  width: 56px;
  height: 56px;
}

/* РђРЅРёРјР°С†РёСЏ */
.skeleton--animated {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

.skeleton-shimmer {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.4),
    transparent
  );
  animation: skeleton-shimmer 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@keyframes skeleton-shimmer {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .skeleton {
    background: #2a2a2a;
  }
  
  .skeleton--animated {
    background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
    background-size: 200% 100%;
  }
  
  .skeleton-shimmer {
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.1),
      transparent
    );
  }
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .skeleton--image {
    height: 150px;
  }
  
  .skeleton--card {
    height: 250px;
  }
  
  .skeleton--button {
    height: 36px;
    width: 100px;
  }
}

/* Р”Р»СЏ СЃРЅРёР¶РµРЅРёСЏ Р°РЅРёРјР°С†РёРё РїСЂРё РЅР°СЃС‚СЂРѕР№РєР°С… РґРѕСЃС‚СѓРїРЅРѕСЃС‚Рё */
@media (prefers-reduced-motion: reduce) {
  .skeleton--animated {
    animation: none;
    background: #f0f0f0;
  }
  
  .skeleton-shimmer {
    animation: none;
    display: none;
  }
}
</style>
