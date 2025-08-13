<template>
  <div class="modal-examples p-8 space-y-6">
    <h1 class="text-2xl font-bold mb-8">
      Примеры модальных окон
    </h1>
    
    <!-- Кнопки для демонстрации -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- BaseModal -->
      <div class="space-y-2">
        <h3 class="font-semibold">
          BaseModal
        </h3>
        <button
          class="w-full btn btn-primary"
          @click="baseModal.open()"
        >
          Открыть BaseModal
        </button>
      </div>
      
      <!-- ConfirmModal -->
      <div class="space-y-2">
        <h3 class="font-semibold">
          ConfirmModal
        </h3>
        <button
          class="w-full btn btn-warning"
          @click="confirmModal.open()"
        >
          Открыть ConfirmModal
        </button>
      </div>
      

      
      <!-- Программные модалки -->
      <div class="space-y-2">
        <h3 class="font-semibold">
          Программный Confirm
        </h3>
        <button
          class="w-full btn btn-danger"
          @click="showProgrammaticConfirm"
        >
          Подтвердить удаление
        </button>
      </div>
      
      <div class="space-y-2">
        <h3 class="font-semibold">
          Программный Alert
        </h3>
        <button
          class="w-full btn btn-success"
          @click="showProgrammaticAlert"
        >
          Показать уведомление
        </button>
      </div>
      
      <div class="space-y-2">
        <h3 class="font-semibold">
          С подтверждением
        </h3>
        <button
          class="w-full btn btn-danger"
          @click="showConfirmationModal"
        >
          Удалить с подтверждением
        </button>
      </div>
    </div>
    
    <!-- Результаты действий -->
    <div v-if="lastAction" class="mt-8 p-4 bg-gray-500 rounded-lg">
      <p><strong>Последнее действие:</strong> {{ lastAction }}</p>
    </div>
    
    <!-- BaseModal Example -->
    <BaseModal
      v-model="baseModal.isOpen.value"
      title="Базовое модальное окно"
      size="md"
      :show-footer="true"
      :show-cancel-button="true"
      :show-confirm-button="true"
      @confirm="handleBaseConfirm"
      @cancel="handleBaseCancel"
    >
      <div class="space-y-4">
        <p>Это пример базового модального окна с полной функциональностью.</p>
        <p class="text-sm text-gray-500">
          Включает управление фокусом, закрытие по Escape, backdrop click и полную accessibility поддержку.
        </p>
        <div class="bg-blue-50 p-3 rounded">
          <p class="text-sm text-blue-800">
            <strong>Возможности:</strong> Focus trap, ARIA атрибуты, анимации, адаптивность
          </p>
        </div>
      </div>
    </BaseModal>
    
    <!-- ConfirmModal Example -->
    <ConfirmModal
      v-model="confirmModal.isOpen.value"
      title="Подтвердите действие"
      message="Вы действительно хотите продолжить?"
      description="Это действие нельзя будет отменить."
      variant="warning"
      confirm-text="Да, продолжить"
      cancel-text="Отмена"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
    
    <!-- AlertModal Example - теперь только программный вызов -->
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
    BaseModal,
    ConfirmModal,
    useModal,
    useConfirm,
    useAlert
} from '../index'

// Модальные окна
const baseModal = useModal()
const confirmModal = useModal()

// Программные модалки
const { confirm } = useConfirm()
const { alert } = useAlert()

// Состояние
const lastAction = ref('')

// Обработчики событий
const handleBaseConfirm = () => {
    lastAction.value = 'BaseModal: Подтверждено'
}

const handleBaseCancel = () => {
    lastAction.value = 'BaseModal: Отменено'
}

const handleConfirm = () => {
    lastAction.value = 'ConfirmModal: Пользователь подтвердил действие'
}

const handleCancel = () => {
    lastAction.value = 'ConfirmModal: Пользователь отменил действие'
}



// Программные модальные окна
const showProgrammaticConfirm = async () => {
    const result = await confirm({
        title: 'Удаление элемента',
        message: 'Вы уверены, что хотите удалить этот элемент?',
        description: 'Это действие необратимо. Все связанные данные будут потеряны.',
        variant: 'danger',
        confirmText: 'Удалить',
        cancelText: 'Оставить'
    })
  
    lastAction.value = `Программный Confirm: ${result.confirmed ? 'Подтверждено' : 'Отменено'}`
}

const showProgrammaticAlert = async () => {
    // ✅ Используем alert из useAlert вместо toast.alert
    await alert({
        title: 'Поздравляем!',
        message: 'Ваш профиль успешно обновлен.',
        variant: 'success',
        buttonText: 'Отлично!'
    })
  
    lastAction.value = 'Программный Alert: Показан и закрыт'
}

const showConfirmationModal = async () => {
    const result = await confirm({
        title: 'Критическое действие',
        message: 'Для подтверждения введите "УДАЛИТЬ"',
        description: 'Это действие удалит все данные безвозвратно.',
        variant: 'danger',
        requiresConfirmation: true,
        confirmationText: 'УДАЛИТЬ',
        confirmText: 'Подтвердить удаление'
    })
  
    lastAction.value = `Модалка с подтверждением: ${result.confirmed ? 'Подтверждено' : 'Отменено'}`
}
</script>

<style scoped>
.btn {
  @apply px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn-warning {
  @apply bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500;
}

.btn-info {
  @apply bg-gray-500 text-white hover:bg-gray-500 focus:ring-gray-500;
}

.btn-danger {
  @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
}

.btn-success {
  @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500;
}
</style>