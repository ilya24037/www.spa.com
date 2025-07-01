<template>
  <AppLayout>
    <Head title="Личный кабинет" />

    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 py-6">

        <!-- Главное условие: профиль пользователя -->
        <div class="flex gap-6">
          <!-- Боковая панель и остальной контент, как раньше... -->
          <aside v-if="counts" class="w-80 flex-shrink-0">
            <!-- ... -->
          </aside>

          <main class="flex-1">
            <div class="bg-white rounded-lg shadow-sm">
              <!-- Вкладки -->
              <div v-if="tabs && tabs.length" class="border-b">
                <!-- ...Табуляция и вкладки... -->
              </div>
              <!-- Список анкет по активной вкладке -->
              <div class="p-6">
                <template v-if="activeTab === 'active' && activeProfiles.length">
                  <ProfileCard
                    v-for="profile in activeProfiles"
                    :key="profile.id"
                    :profile="profile"
                    @edit="editProfile"
                    @delete="deleteProfile"
                    @toggle="toggleProfile"
                  />
                </template>
                <div v-else-if="activeTab === 'active'">
                  <EmptyState title="Нет активных анкет" description="Создайте свою первую анкету мастера" />
                </div>

                <template v-if="activeTab === 'draft' && draftProfiles.length">
                  <ProfileCard
                    v-for="profile in draftProfiles"
                    :key="profile.id"
                    :profile="profile"
                    :is-draft="true"
                    @edit="editProfile"
                    @delete="deleteProfile"
                    @publish="publishProfile"
                  />
                </template>
                <div v-else-if="activeTab === 'draft'">
                  <EmptyState title="Нет черновиков" description="Здесь будут отображаться незавершённые анкеты" />
                </div>

                <template v-if="activeTab === 'archive' && archivedProfiles.length">
                  <ProfileCard
                    v-for="profile in archivedProfiles"
                    :key="profile.id"
                    :profile="profile"
                    :is-archived="true"
                    @restore="restoreProfile"
                    @delete="deleteProfile"
                  />
                </template>
                <div v-else-if="activeTab === 'archive'">
                  <EmptyState title="Архив пуст" description="Здесь будут отображаться архивированные анкеты" />
                </div>
              </div>
            </div>
          </main>
        </div>

        <!-- v-else — fallback для всех случаев, когда нет данных или ошибка -->
        <div v-else class="w-full bg-white rounded-lg p-10 text-center text-gray-400 text-lg">
          Не удалось загрузить данные.<br>
          Пожалуйста, войдите в систему или попробуйте позже.
        </div>

      </div>
    </div>
  </AppLayout>
</template>
