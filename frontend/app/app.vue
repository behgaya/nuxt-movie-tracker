<script setup lang="ts">
// Pages set their own title with useSeoMeta(); this wraps it, e.g. "Dune · Movie Tracker"
useHead({
  titleTemplate: title => title ? `${title} · Movie Tracker` : 'Movie Tracker',
})
</script>

<template>
  <v-app>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </v-app>
</template>
