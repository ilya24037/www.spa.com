<template>
  <AuthenticatedLayout :title="title">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Логи действий администраторов
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Фильтры -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Фильтры</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <!-- Фильтр по админу -->
              <div>
                <label for="admin" class="block text-sm font-medium text-gray-700 mb-1">
                  Администратор
                </label>
                <select
                  id="admin"
                  v-model="filters.admin_id"
                  @change="applyFilters"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">Все администраторы</option>
                  <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                    {{ admin.name || admin.email }}
                  </option>
                </select>
              </div>

              <!-- Фильтр по действию -->
              <div>
                <label for="action" class="block text-sm font-medium text-gray-700 mb-1">
                  Действие
                </label>
                <select
                  id="action"
                  v-model="filters.action"
                  @change="applyFilters"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">Все действия</option>
                  <option v-for="action in actions" :key="action" :value="action">
                    {{ getActionLabel(action) }}
                  </option>
                </select>
              </div>

              <!-- Фильтр по дате от -->
              <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">
                  Дата от
                </label>
                <input
                  id="from"
                  type="date"
                  v-model="filters.from"
                  @change="applyFilters"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <!-- Фильтр по дате до -->
              <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">
                  Дата до
                </label>
                <input
                  id="to"
                  type="date"
                  v-model="filters.to"
                  @change="applyFilters"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Кнопка сброса фильтров -->
            <div class="mt-4">
              <button
                @click="resetFilters"
                class="px-4 py-2 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600 transition-colors"
              >
                Сбросить фильтры
              </button>
            </div>
          </div>
        </div>

        <!-- Таблица логов -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Журнал действий</h3>

            <!-- Если есть логи -->
            <div v-if="logs.data && logs.data.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Дата и время
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Администратор
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Действие
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Объект
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Детали
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="log in logs.data" :key="log.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      #{{ log.id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(log.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ log.admin?.name || log.admin?.email || 'Неизвестно' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="getActionClass(log.action)"
                      >
                        {{ getActionLabel(log.action) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ getModelLabel(log.model_type) }} #{{ log.model_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <button
                        v-if="log.metadata"
                        @click="showDetails(log)"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        Показать детали
                      </button>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Если логов нет -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">Нет записей</h3>
              <p class="mt-1 text-sm text-gray-500">Записи в журнале отсутствуют</p>
            </div>

            <!-- Пагинация -->
            <div v-if="logs.links && logs.links.length > 3" class="mt-6">
              <nav class="flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                  <div>
                    <p class="text-sm text-gray-700">
                      Показано с
                      <span class="font-medium">{{ logs.from }}</span>
                      по
                      <span class="font-medium">{{ logs.to }}</span>
                      из
                      <span class="font-medium">{{ logs.total }}</span>
                      записей
                    </p>
                  </div>
                  <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                      <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                          link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                          'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                        ]"
                        v-html="link.label"
                      />
                    </nav>
                  </div>
                </div>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно для деталей -->
    <TransitionRoot :show="showDetailsModal" as="template">
      <Dialog @close="showDetailsModal = false">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <DialogOverlay class="fixed inset-0 bg-black/30" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                  Детали действия #{{ selectedLog?.id }}
                </DialogTitle>

                <div class="mt-4">
                  <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(selectedLog?.metadata, null, 2) }}</pre>
                  </div>
                </div>

                <div class="mt-6">
                  <button
                    type="button"
                    @click="showDetailsModal = false"
                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                  >
                    Закрыть
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Dialog,
  DialogOverlay,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

interface AdminLog {
  id: number
  admin_id: number
  action: string
  model_type: string
  model_id: number
  metadata: any
  created_at: string
  admin?: {
    id: number
    name?: string
    email: string
  }
}

interface Props {
  logs: {
    data: AdminLog[]
    links: any[]
    from: number
    to: number
    total: number
  }
  admins: any[]
  actions: string[]
  filters: {
    admin_id?: string
    action?: string
    from?: string
    to?: string
  }
}

const props = defineProps<Props>()

const title = 'Журнал администраторов'
const filters = reactive({ ...props.filters })
const showDetailsModal = ref(false)
const selectedLog = ref<AdminLog | null>(null)

// Применить фильтры
const applyFilters = () => {
  router.get('/profile/admin/logs', filters, {
    preserveState: true,
    preserveScroll: true
  })
}

// Сбросить фильтры
const resetFilters = () => {
  filters.admin_id = ''
  filters.action = ''
  filters.from = ''
  filters.to = ''
  applyFilters()
}

// Показать детали
const showDetails = (log: AdminLog) => {
  selectedLog.value = log
  showDetailsModal.value = true
}

// Форматирование даты
const formatDate = (date: string) => {
  return new Date(date).toLocaleString('ru-RU')
}

// Получить метку действия
const getActionLabel = (action: string) => {
  const labels: Record<string, string> = {
    'approve': 'Одобрение',
    'reject': 'Отклонение',
    'block': 'Блокировка',
    'unblock': 'Разблокировка',
    'delete': 'Удаление',
    'edit': 'Редактирование',
    'bulk_approve': 'Массовое одобрение',
    'bulk_reject': 'Массовое отклонение',
    'bulk_block': 'Массовая блокировка',
    'bulk_delete': 'Массовое удаление',
    'bulk_archive': 'Массовое архивирование'
  }
  return labels[action] || action
}

// Получить класс для действия
const getActionClass = (action: string) => {
  if (action.includes('approve')) return 'bg-green-100 text-green-800'
  if (action.includes('reject') || action.includes('block')) return 'bg-red-100 text-red-800'
  if (action.includes('delete')) return 'bg-red-100 text-red-800'
  if (action.includes('edit')) return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
}

// Получить метку модели
const getModelLabel = (modelType: string) => {
  const labels: Record<string, string> = {
    'App\\Domain\\Ad\\Models\\Ad': 'Объявление',
    'App\\Domain\\User\\Models\\User': 'Пользователь',
    'App\\Domain\\Review\\Models\\Review': 'Отзыв',
    'App\\Domain\\Master\\Models\\MasterProfile': 'Мастер'
  }
  return labels[modelType] || modelType.split('\\').pop() || 'Объект'
}
</script>