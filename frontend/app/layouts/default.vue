<script setup lang="ts">
const { user, clear } = useAuth()
const toast = useToast()

const links = [
  { to: '/', label: 'Watched', icon: 'mdi-check-circle-outline' },
  { to: '/movies', label: 'All movies', icon: 'mdi-movie-search-outline' },
  { to: '/want', label: 'To watch', icon: 'mdi-bookmark-outline' },
  { to: '/folders', label: 'Folders', icon: 'mdi-folder-multiple-outline' },
  { to: '/recommended', label: 'For you', icon: 'mdi-star-shooting-outline' },
]

async function logout() {
  await clear() // ends the session on the API
  clearNuxtData(['watched', 'want', 'folders']) // forget this user's cached lists
  await navigateTo('/login')
}
</script>

<template>
  <v-app-bar flat class="app-bar">
    <template #prepend>
      <NuxtLink to="/" class="ml-2">
        <!-- ~/assets/... is processed by Vite: in production it becomes a hashed URL (or is inlined if tiny) -->
        <img src="~/assets/images/logo.svg" alt="My Movies logo" class="app-logo">
      </NuxtLink>
    </template>
    <!-- Hide the text title on phones; the logo still links home -->
    <v-app-bar-title class="brand d-none d-sm-flex">My <span class="text-primary">Movies</span></v-app-bar-title>
    <!-- Labels from 960px up; below that only the icons fit (the title still names each one) -->
    <v-btn
      v-for="link in links"
      :key="link.to"
      :to="link.to"
      :exact="link.to === '/'"
      :title="link.label"
      :prepend-icon="link.icon"
      variant="text"
      active-color="primary"
      class="nav-link"
    >
      <span class="d-none d-md-inline">{{ link.label }}</span>
    </v-btn>

    <!-- Logged-out visitors only get here through a public folder link -->
    <v-btn v-if="!user" to="/login" color="primary" variant="flat" class="mx-2">Log in</v-btn>
    <v-menu v-else location="bottom end">
      <template #activator="{ props }">
        <v-btn v-bind="props" icon class="mx-2" :title="user.username">
          <v-avatar color="primary" size="34">
            <span class="font-weight-bold">{{ user.username[0]?.toUpperCase() }}</span>
          </v-avatar>
        </v-btn>
      </template>
      <v-list density="compact" min-width="180">
        <v-list-item :title="user.username" subtitle="Signed in" prepend-icon="mdi-account-circle-outline" />
        <v-divider class="my-1" />
        <v-list-item title="Log out" prepend-icon="mdi-logout" @click="logout" />
      </v-list>
    </v-menu>
  </v-app-bar>
  <!-- Vuetify measures the app bar as 0px during SSR, so reserve its height explicitly -->
  <v-main style="padding-top: 64px">
    <slot />
  </v-main>

  <v-snackbar :model-value="!!toast.message.value" timeout="3000" @update:model-value="toast.show('')">{{ toast.message.value }}</v-snackbar>
</template>
