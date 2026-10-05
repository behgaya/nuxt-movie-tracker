<script setup lang="ts">
const { user, clear } = useUserSession()

async function logout() {
  await clear() // deletes the session cookie
  clearNuxtData('watched') // forget this user's cached list
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
    <v-btn to="/" variant="text" active-color="primary" prepend-icon="mdi-check-circle-outline" exact>Watched</v-btn>
    <v-btn to="/movies" variant="text" active-color="primary" prepend-icon="mdi-movie-search-outline" >All movies</v-btn>

    <v-menu v-if="user" location="bottom end">
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
</template>
