<!-- Bare layout for the login page: no nav bar, content centered on screen -->
<template>
  <v-main class="d-flex align-center justify-center auth-bg">
    <div class="w-100 pa-4" style="max-width: 420px">
      <div class="d-flex align-center justify-center ga-3 mb-8">
        <img src="~/assets/images/logo.svg" alt="My Movies logo" class="app-logo" style="height: 44px">
        <span class="brand text-h4">My <span class="text-primary">Movies</span></span>
      </div>
      <slot />
    </div>
  </v-main>
</template>
