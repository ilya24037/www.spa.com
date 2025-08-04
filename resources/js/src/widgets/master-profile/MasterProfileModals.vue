<!-- resources/js/src/widgets/master-profile/MasterProfileModals.vue -->
<template>
  <!-- Модальное окно "Поделиться" -->
  <div v-if="showShare" :class="MODAL_OVERLAY_CLASSES" @click="handleOverlayClick('share')">
    <div :class="MODAL_CONTAINER_CLASSES">
      <div :class="MODAL_HEADER_CLASSES">
        <h3 :class="MODAL_TITLE_CLASSES">Поделиться профилем</h3>
        <button
          @click="$emit('close-share')"
          :class="CLOSE_BUTTON_CLASSES"
        >
          <svg :class="CLOSE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div :class="MODAL_CONTENT_CLASSES">
        <!-- URL для копирования -->
        <div :class="URL_SECTION_CLASSES">
          <label :class="URL_LABEL_CLASSES">Ссылка на профиль</label>
          <div :class="URL_INPUT_CONTAINER_CLASSES">
            <input
              ref="urlInput"
              :value="masterUrl"
              readonly
              :class="URL_INPUT_CLASSES"
            >
            <button
              @click="copyUrl"
              :class="COPY_BUTTON_CLASSES"
            >
              {{ urlCopied ? 'Скопировано!' : 'Копировать' }}
            </button>
          </div>
        </div>

        <!-- Социальные сети -->
        <div :class="SOCIAL_SECTION_CLASSES">
          <h4 :class="SOCIAL_TITLE_CLASSES">Поделиться в соцсетях</h4>
          <div :class="SOCIAL_BUTTONS_CLASSES">
            <button
              @click="shareToVK"
              :class="getSocialButtonClasses('vk')"
            >
              <svg :class="SOCIAL_ICON_CLASSES" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.785 16.241s.288-.032.436-.194c.136-.148.132-.426.132-.426s-.02-1.302.577-1.495c.588-.19 1.341.259 2.138 1.011.6.566 1.058.882 1.058.882l2.124-.03s1.11-.07.584-.951c-.043-.072-.306-.651-1.575-1.84-1.33-1.244-1.153-.043.45-1.3.978-1.154 1.732-1.856 1.732-1.856s.612-.46.136-.516l-2.423-.034s-.18-.024-.312.056c-.128.076-.21.253-.21.253s-.378.101-.742 2.29c-.769 2.254-1.095 2.371-1.223 2.23-.298-.327-.223-1.314-.223-2.016 0-2.19.328-3.097-.64-3.333-.32-.078-.556-.129-1.375-.137-.105-.01-1.879-.015-2.37.6-.32.4-.024.622.169.654.238.04.779.147.064 1.373-.102.55-.409 1.792-.588 2.052-.306.445-.511.372-.511-.237 0-.54.023-1.633.023-2.346 0-1.62.27-2.215-.525-2.376-.263-.053-.458-.08-.934-.085-.742-.008-1.357 0-1.71.176-.236.118-.419.38-.307.395.137.019.448.084.612.31.213.293.205.95.205.95s.122 2.58-.285 2.9c-.28.218-.664-.227-1.49-2.266-.423-.578-.741-1.218-.741-1.218s-.061-.152-.171-.234c-.133-.099-.32-.13-.32-.13l-2.301.015s-.345.01-.471.161c-.112.134-.009.41-.009.41s1.777 4.194 3.788 6.307c1.843 1.938 3.931 1.811 3.931 1.811h.952z"/>
              </svg>
              ВКонтакте
            </button>

            <button
              @click="shareToTelegram"
              :class="getSocialButtonClasses('telegram')"
            >
              <svg :class="SOCIAL_ICON_CLASSES" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.58 7.44c-.12.532-.432.66-.864.42l-2.388-1.764-1.152 1.116c-.128.128-.236.236-.48.236l.168-2.388 4.332-3.912c.192-.168-.04-.264-.3-.096l-5.364 3.384-2.304-.72c-.504-.156-.516-.504.108-.744l8.964-3.456c.42-.156.792.096.66.54z"/>
              </svg>
              Telegram
            </button>

            <button
              @click="shareToWhatsApp"
              :class="getSocialButtonClasses('whatsapp')"
            >
              <svg :class="SOCIAL_ICON_CLASSES" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.886 3.488"/>
              </svg>
              WhatsApp
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно "Пожаловаться" -->
  <div v-if="showReport" :class="MODAL_OVERLAY_CLASSES" @click="handleOverlayClick('report')">
    <div :class="MODAL_CONTAINER_CLASSES">
      <div :class="MODAL_HEADER_CLASSES">
        <h3 :class="MODAL_TITLE_CLASSES">Пожаловаться на мастера</h3>
        <button
          @click="$emit('close-report')"
          :class="CLOSE_BUTTON_CLASSES"
        >
          <svg :class="CLOSE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div :class="MODAL_CONTENT_CLASSES">
        <form @submit.prevent="submitReport">
          <!-- Причина жалобы -->
          <div :class="FORM_GROUP_CLASSES">
            <label :class="FORM_LABEL_CLASSES">Причина жалобы</label>
            <div :class="RADIO_GROUP_CLASSES">
              <label
                v-for="reason in reportReasons"
                :key="reason.value"
                :class="RADIO_LABEL_CLASSES"
              >
                <input
                  v-model="reportForm.reason"
                  type="radio"
                  :value="reason.value"
                  :class="RADIO_INPUT_CLASSES"
                >
                <span>{{ reason.label }}</span>
              </label>
            </div>
          </div>

          <!-- Дополнительные комментарии -->
          <div :class="FORM_GROUP_CLASSES">
            <label :class="FORM_LABEL_CLASSES">Дополнительная информация</label>
            <textarea
              v-model="reportForm.comment"
              :class="TEXTAREA_CLASSES"
              rows="4"
              placeholder="Опишите проблему подробнее (необязательно)"
            ></textarea>
          </div>

          <!-- Кнопки -->
          <div :class="FORM_ACTIONS_CLASSES">
            <button
              type="button"
              @click="$emit('close-report')"
              :class="CANCEL_BUTTON_CLASSES"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="!reportForm.reason || reportSubmitting"
              :class="SUBMIT_BUTTON_CLASSES"
            >
              {{ reportSubmitting ? 'Отправка...' : 'Отправить жалобу' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const MODAL_OVERLAY_CLASSES = 'fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4'
const MODAL_CONTAINER_CLASSES = 'bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden'
const MODAL_HEADER_CLASSES = 'flex items-center justify-between p-6 border-b border-gray-200'
const MODAL_TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-100 rounded-lg transition-colors'
const CLOSE_ICON_CLASSES = 'w-5 h-5 text-gray-500'
const MODAL_CONTENT_CLASSES = 'p-6 space-y-6'
const URL_SECTION_CLASSES = 'space-y-2'
const URL_LABEL_CLASSES = 'text-sm font-medium text-gray-700'
const URL_INPUT_CONTAINER_CLASSES = 'flex gap-2'
const URL_INPUT_CLASSES = 'flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm'
const COPY_BUTTON_CLASSES = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors'
const SOCIAL_SECTION_CLASSES = 'space-y-3'
const SOCIAL_TITLE_CLASSES = 'text-sm font-medium text-gray-700'
const SOCIAL_BUTTONS_CLASSES = 'space-y-2'
const SOCIAL_BUTTON_BASE_CLASSES = 'w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-colors'
const SOCIAL_ICON_CLASSES = 'w-5 h-5'
const FORM_GROUP_CLASSES = 'space-y-2'
const FORM_LABEL_CLASSES = 'text-sm font-medium text-gray-700'
const RADIO_GROUP_CLASSES = 'space-y-2'
const RADIO_LABEL_CLASSES = 'flex items-center gap-2 cursor-pointer'
const RADIO_INPUT_CLASSES = 'text-blue-600 focus:ring-blue-500'
const TEXTAREA_CLASSES = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
const FORM_ACTIONS_CLASSES = 'flex gap-3 pt-4'
const CANCEL_BUTTON_CLASSES = 'flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors'
const SUBMIT_BUTTON_CLASSES = 'flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 text-white rounded-lg font-medium transition-colors'

const props = defineProps({
  showShare: {
    type: Boolean,
    default: false
  },
  showReport: {
    type: Boolean,
    default: false
  },
  master: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close-share', 'close-report', 'report-sent'])

// Состояние
const urlInput = ref(null)
const urlCopied = ref(false)
const reportSubmitting = ref(false)
const reportForm = ref({
  reason: '',
  comment: ''
})

// Причины для жалоб
const reportReasons = [
  { value: 'fake_profile', label: 'Поддельный профиль' },
  { value: 'inappropriate_content', label: 'Неподходящий контент' },
  { value: 'spam', label: 'Спам' },
  { value: 'fraud', label: 'Мошенничество' },
  { value: 'other', label: 'Другое' }
]

// Вычисляемые свойства
const masterUrl = computed(() => {
  return `${window.location.origin}/masters/${props.master.id}`
})

// Методы
const getSocialButtonClasses = (platform) => {
  const platformStyles = {
    vk: 'bg-blue-600 hover:bg-blue-700 text-white',
    telegram: 'bg-blue-500 hover:bg-blue-600 text-white',
    whatsapp: 'bg-green-500 hover:bg-green-600 text-white'
  }
  
  return [SOCIAL_BUTTON_BASE_CLASSES, platformStyles[platform]].join(' ')
}

const handleOverlayClick = (modal) => {
  if (modal === 'share') {
    emit('close-share')
  } else if (modal === 'report') {
    emit('close-report')
  }
}

const copyUrl = async () => {
  try {
    await navigator.clipboard.writeText(masterUrl.value)
    urlCopied.value = true
    setTimeout(() => {
      urlCopied.value = false
    }, 2000)
  } catch (error) {
    // Fallback для старых браузеров
    if (urlInput.value) {
      urlInput.value.select()
      document.execCommand('copy')
      urlCopied.value = true
      setTimeout(() => {
        urlCopied.value = false
      }, 2000)
    }
  }
}

const shareToVK = () => {
  const url = encodeURIComponent(masterUrl.value)
  const title = encodeURIComponent(`Мастер массажа ${props.master.name}`)
  window.open(`https://vk.com/share.php?url=${url}&title=${title}`, '_blank')
}

const shareToTelegram = () => {
  const url = encodeURIComponent(masterUrl.value)
  const text = encodeURIComponent(`Мастер массажа ${props.master.name}`)
  window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank')
}

const shareToWhatsApp = () => {
  const text = encodeURIComponent(`Мастер массажа ${props.master.name} ${masterUrl.value}`)
  window.open(`https://wa.me/?text=${text}`, '_blank')
}

const submitReport = async () => {
  if (!reportForm.value.reason || reportSubmitting.value) return
  
  reportSubmitting.value = true
  
  try {
    // Здесь бы был API вызов
    await new Promise(resolve => setTimeout(resolve, 1000))
    
      masterId: props.master.id,
      reason: reportForm.value.reason,
      comment: reportForm.value.comment
    })
    
    emit('report-sent')
    emit('close-report')
    
    // Сбрасываем форму
    reportForm.value = {
      reason: '',
      comment: ''
    }
  } catch (error) {
  } finally {
    reportSubmitting.value = false
  }
}
</script>